<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSaleItem extends Model
{
    protected $fillable = [
        'product_sale_id',
        'inventory_product_id',
        'inventory_product_batch_id',
        'name',
        'lot_code',
        'quantity',
        'stock_quantity',
        'pending_quantity',
        'unit_price',
        'total',
        'missing_reason',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'stock_quantity' => 'decimal:2',
            'pending_quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(ProductSale::class, 'product_sale_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(InventoryProduct::class, 'inventory_product_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryProductBatch::class, 'inventory_product_batch_id');
    }
}
