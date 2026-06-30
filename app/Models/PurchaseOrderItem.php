<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    protected $fillable = ['purchase_order_id', 'product_id', 'unit_id', 'qty', 'harga_satuan'];

    protected function casts(): array
    {
        return [
            'qty' => 'decimal:2',
            'harga_satuan' => 'decimal:2',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class, 'unit_id');
    }

    /** Total sudah diterima dari semua GoodsReceipt */
    public function totalReceived(): float
    {
        return (float) $this->hasMany(GoodsReceiptItem::class, 'purchase_order_item_id')->sum('qty_received');
    }
}
