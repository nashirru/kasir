<?php

namespace App\Services;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function __construct(
        private StockService $stockService,
        private UnitConversionService $unitConversionService,
    ) {}

    /**
     * Create a purchase order.
     */
    public function createPurchaseOrder(array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($data) {
            $total = 0;
            $items = $data['items'] ?? [];
            unset($data['items']);

            $data['created_by'] = Auth::id();
            $data['status'] = 'draft';

            $po = PurchaseOrder::create($data);

            foreach ($items as $item) {
                $subtotal = (float) $item['qty'] * (float) $item['harga_satuan'];
                $total += $subtotal;

                $po->items()->create([
                    'product_id' => $item['product_id'],
                    'unit_id' => $item['unit_id'] ?? $item['product']['base_unit_id'],
                    'qty' => $item['qty'],
                    'harga_satuan' => $item['harga_satuan'],
                ]);
            }

            $po->update(['total' => $total]);

            return $po->fresh(['items', 'supplier', 'warehouse']);
        });
    }

    /**
     * Receive goods for a purchase order (partial or full).
     */
    public function receiveGoods(PurchaseOrder $po, array $items, ?string $notes = null): GoodsReceipt
    {
        return DB::transaction(function () use ($po, $items, $notes) {
            $receipt = GoodsReceipt::create([
                'purchase_order_id' => $po->id,
                'received_by' => Auth::id(),
                'received_at' => now(),
                'notes' => $notes,
            ]);

            $productModel = new \App\Models\Product();

            foreach ($items as $item) {
                $poItem = PurchaseOrderItem::findOrFail($item['purchase_order_item_id']);
                $alreadyReceived = $poItem->totalReceived();
                $orderedQty = (float) $poItem->qty;

                $qtyReceiving = (float) $item['qty_received'];

                // Validate: can't receive more than remaining
                if ($qtyReceiving > ($orderedQty - $alreadyReceived)) {
                    throw new \RuntimeException(
                        "Qty diterima melebihi sisa PO untuk item {$poItem->product_id}. " .
                        "Sisa: " . ($orderedQty - $alreadyReceived)
                    );
                }

                $receiptItem = $receipt->items()->create([
                    'purchase_order_item_id' => $poItem->id,
                    'qty_received' => $qtyReceiving,
                    'batch_number' => $item['batch_number'] ?? null,
                    'expired_date' => $item['expired_date'] ?? null,
                ]);

                // Convert to base unit then add stock
                $product = $poItem->product;
                $warehouse = $po->warehouse;

                $baseQty = $this->unitConversionService->toBaseUnit(
                    $product,
                    $poItem->unit,
                    $qtyReceiving
                );

                $this->stockService->addStock(
                    product: $product,
                    warehouse: $warehouse,
                    qty: $baseQty,
                    batchNumber: $item['batch_number'] ?? null,
                    expiredDate: $item['expired_date'] ?? null,
                    notes: "Penerimaan PO #{$po->id}",
                    reference: $receiptItem,
                );
            }

            // Update PO status
            $allItems = $po->items;
            $allReceived = true;
            $partialReceived = false;

            foreach ($allItems as $pi) {
                $remaining = (float) $pi->qty - $pi->totalReceived();
                if ($remaining > 0) {
                    $allReceived = false;
                    if ($pi->totalReceived() > 0) {
                        $partialReceived = true;
                    }
                }
            }

            if ($allReceived) {
                $po->update(['status' => 'completed']);
            } elseif ($partialReceived) {
                $po->update(['status' => 'partial']);
            } else {
                $po->update(['status' => 'ordered']);
            }

            return $receipt->fresh(['items', 'purchaseOrder']);
        });
    }
}
