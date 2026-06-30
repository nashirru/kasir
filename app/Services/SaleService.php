<?php

namespace App\Services;

use App\Models\CashRegister;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SaleService
{
    public function __construct(
        private StockService $stockService,
        private UnitConversionService $unitConversionService,
    ) {}

    /**
     * Create a sale (POS transaction).
     */
    public function createSale(array $data): Sale
    {
        return DB::transaction(function () use ($data) {
            $cashRegister = CashRegister::findOrFail($data['cash_register_id']);

            if ($cashRegister->status !== 'open') {
                throw new \RuntimeException('Cash register harus dalam status open.');
            }

            $warehouse = Warehouse::findOrFail($data['warehouse_id']);
            $outletId = $data['outlet_id'];
            $items = $data['items'];

            // Generate unique invoice number (concurrency-safe)
            $invoiceNumber = 'INV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

            $subtotal = 0;
            $saleItems = [];

            foreach ($items as $item) {
                $product = Product::with('conversions')->findOrFail($item['product_id']);
                $unitId = $item['unit_id'] ?? $product->base_unit_id;
                $unit = \App\Models\ProductUnit::findOrFail($unitId);
                $qty = (float) $item['qty'];
                $price = (float) ($item['harga_satuan'] ?? $product->harga_jual);

                // Convert to base unit for stock deduction
                $baseQty = $this->unitConversionService->toBaseUnit($product, $unit, $qty);

                // Deduct stock with FEFO
                $this->stockService->deductStock(
                    product: $product,
                    warehouse: $warehouse,
                    qty: $baseQty,
                    notes: "Penjualan {$invoiceNumber}",
                );

                $lineSubtotal = $qty * $price;
                $subtotal += $lineSubtotal;

                $saleItems[] = [
                    'product_id' => $product->id,
                    'unit_id' => $unit->id,
                    'qty' => $qty,
                    'harga_satuan' => $price,
                    'subtotal' => $lineSubtotal,
                ];
            }

            $discount = (float) ($data['discount'] ?? 0);
            $tax = (float) ($data['tax'] ?? 0);
            $total = $subtotal - $discount + $tax;

            $sale = Sale::create([
                'outlet_id' => $outletId,
                'warehouse_id' => $warehouse->id,
                'cash_register_id' => $cashRegister->id,
                'customer_id' => $data['customer_id'] ?? null,
                'invoice_number' => $invoiceNumber,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'total' => $total,
                'status' => 'completed',
                'created_by' => Auth::id(),
            ]);

            // Create sale items
            foreach ($saleItems as $saleItem) {
                $sale->items()->create($saleItem);
            }

            // Create payments (support split payment)
            foreach ($data['payments'] as $payment) {
                $sale->payments()->create([
                    'payment_method' => $payment['method'],
                    'amount' => (float) $payment['amount'],
                ]);
            }

            return $sale->fresh(['items', 'payments', 'customer']);
        });
    }

    /**
     * Void a sale and restore stock.
     */
    public function voidSale(Sale $sale): void
    {
        DB::transaction(function () use ($sale) {
            if ($sale->status === 'void') {
                throw new \RuntimeException('Transaksi sudah di-void sebelumnya.');
            }

            $warehouse = Warehouse::findOrFail($sale->warehouse_id);

            $sale->load('items.unit', 'items.product.baseUnit');

            foreach ($sale->items as $item) {
                $product = $item->product;
                $unit = $item->unit ?? $product->baseUnit;

                $baseQty = $this->unitConversionService->toBaseUnit($product, $unit, (float) $item->qty);

                $this->stockService->addStock(
                    product: $product,
                    warehouse: $warehouse,
                    qty: $baseQty,
                    notes: "Void transaksi {$sale->invoice_number}",
                    reference: $sale,
                );
            }

            $sale->update(['status' => 'void']);
        });
    }

    /**
     * Process a sale return.
     */
    public function processReturn(Sale $sale, array $items, ?string $reason = null): SaleReturn
    {
        return DB::transaction(function () use ($sale, $items, $reason) {
            $warehouse = Warehouse::findOrFail($sale->warehouse_id);
            $totalRefund = 0;

            $saleReturn = SaleReturn::create([
                'sale_id' => $sale->id,
                'reason' => $reason,
                'total_refund' => 0,
                'created_by' => Auth::id(),
            ]);

            foreach ($items as $item) {
                $saleItem = SaleItem::with('product.baseUnit', 'unit')->findOrFail($item['sale_item_id']);
                $qtyReturn = (float) $item['qty'];
                $unit = $saleItem->unit ?? $saleItem->product->baseUnit;

                $baseQty = $this->unitConversionService->toBaseUnit(
                    $saleItem->product,
                    $unit,
                    $qtyReturn
                );

                // Restore stock
                $this->stockService->addStock(
                    product: $saleItem->product,
                    warehouse: $warehouse,
                    qty: $baseQty,
                    notes: "Retur {$sale->invoice_number}: {$reason}",
                    reference: $saleReturn,
                );

                $subtotal = $qtyReturn * (float) $saleItem->harga_satuan;
                $totalRefund += $subtotal;

                $saleReturn->items()->create([
                    'sale_item_id' => $saleItem->id,
                    'product_id' => $saleItem->product_id,
                    'qty' => $qtyReturn,
                    'harga_satuan' => $saleItem->harga_satuan,
                    'subtotal' => $subtotal,
                ]);
            }

            $saleReturn->update(['total_refund' => $totalRefund]);

            return $saleReturn->fresh(['items', 'sale']);
        });
    }
}
