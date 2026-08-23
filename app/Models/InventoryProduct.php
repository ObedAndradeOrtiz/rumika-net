<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryProduct extends Model
{
    protected $fillable = [
        'company_id',
        'inventory_supplier_id',
        'inventory_brand_id',
        'inventory_use_area_id',
        'code',
        'name',
        'description',
        'image_path',
        'unit_name',
        'package_name',
        'units_per_package',
        'purchase_cost',
        'minimum_stock',
        'commission_enabled',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'units_per_package' => 'integer',
            'purchase_cost' => 'decimal:2',
            'minimum_stock' => 'integer',
            'commission_enabled' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(InventorySupplier::class, 'inventory_supplier_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(InventoryBrand::class, 'inventory_brand_id');
    }

    public function useArea(): BelongsTo
    {
        return $this->belongsTo(InventoryUseArea::class, 'inventory_use_area_id');
    }

    public function batches(): HasMany
    {
        return $this->hasMany(InventoryProductBatch::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }
}
