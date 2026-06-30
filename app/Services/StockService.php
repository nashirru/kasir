<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Add stock to a warehouse.
     */
    public function addStock(
        Product $product,
        Warehouse $warehouse,
        float $qty,
        ?string $batchNumber = null,
        ?string $expiredDate = null,
        ?string $notes = null,
        ?object $reference = null,
    ): Stock {
        return DB::transaction(function () use ($product, $warehouse, $qty, $batchNumber, $expiredDate, $notes, $reference) {
            // Update or create stock record
            $stock = Stock::updateOrCreate(
                ['product_id' => $product->id, 'warehouse_id' => $warehouse->id],
                ['qty' => DB::raw("qty + {$qty}")]
            );

            // Create or update batch
            if ($batchNumber) {
                ProductBatch::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'warehouse_id' => $warehouse->id,
                        'batch_number' => $batchNumber,
                    ],
                    [
                        'expired_date' => $expiredDate,
                        'qty' => DB::raw("qty + {$qty}"),
                    ]
                );
            }

            // Record movement
            StockMovement::create([
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'type' => 'in',
                'qty' => $qty,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference?->id,
                'notes' => $notes,
                'created_by' => Auth::id(),
            ]);

            return $stock->fresh();
        });
    }

    /**
     * Deduct stock with FEFO logic.
     */
    public function deductStock(
        Product $product,
        Warehouse $warehouse,
        float $qty,
        ?string $notes = null,
        ?object $reference = null,
    ): void {
        DB::transaction(function () use ($product, $warehouse, $qty, $notes, $reference) {
            $stock = Stock::where('product_id', $product->id)
                ->where('warehouse_id', $warehouse->id)
               ->lockForUpdate()
                ->first();

            if (! $stock || (float) $stock->qty < $qty) {
                throw new \RuntimeException(
                    "Stok tidak mencukupi untuk produk {$product->nama}. Tersedia: " . ($stock->qty ?? 0) . ", diminta: {$qty}"
                );
            }

            // FEFO: get batches ordered by expired_date ASC
            $batches = ProductBatch::where('product_id', $product->id)
                ->where('warehouse_id', $warehouse->id)
                ->where('qty', '>', 0)
                ->orderBy('expired_date', 'asc')
                ->orderBy('created_at', 'asc')
                ->lockForUpdate()
                ->get();

            $remaining = $qty;

            foreach ($batches as $batch) {
                if ($remaining <= 0) {
                    break;
                }

                $batchQty = (float) $batch->qty;
                $deductFromBatch = min($batchQty, $remaining);

                $batch->decrement('qty', $deductFromBatch);
                $remaining -= $deductFromBatch;

                // Record movement per batch
                StockMovement::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'type' => 'out',
                    'qty' => $deductFromBatch,
                    'reference_type' => $reference ? get_class($reference) : null,
                    'reference_id' => $reference?->id,
                    'notes' => $notes ? "{$notes} (batch: {$batch->batch_number})" : "Batch: {$batch->batch_number}",
                    'created_by' => Auth::id(),
                ]);
            }

            if ($remaining > 0) {
                throw new \RuntimeException(
                    "Stok tidak mencukupi (inkonsistensi data). Sisa: {$remaining}"
                );
            }

            // Update total stock
            $stock->decrement('qty', $qty);
        });
    }

    /**
     * Get current stock for a product in a warehouse.
     */
    public function getStock(Product $product, Warehouse $warehouse): float
    {
        $stock = Stock::where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->first();

        return $stock ? (float) $stock->qty : 0;
    }
}
