<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryCountItem extends Model
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'inventory_count_id',
        'inventory_product_id',
        'inventory_brand_id',
        'inventory_use_area_id',
        'opening_quantity',
        'movement_in_quantity',
        'movement_out_quantity',
        'expected_quantity',
        'closed_quantity',
        'difference_quantity',
        'unit_cost',
        'stock_value',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'opening_quantity' => 'decimal:2',
            'movement_in_quantity' => 'decimal:2',
            'movement_out_quantity' => 'decimal:2',
            'expected_quantity' => 'decimal:2',
            'closed_quantity' => 'decimal:2',
            'difference_quantity' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'stock_value' => 'decimal:2',
        ];
    }

    public function count(): BelongsTo
    {
        return $this->belongsTo(InventoryCount::class, 'inventory_count_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(InventoryProduct::class, 'inventory_product_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(InventoryBrand::class, 'inventory_brand_id');
    }

    public function useArea(): BelongsTo
    {
        return $this->belongsTo(InventoryUseArea::class, 'inventory_use_area_id');
    }
}
