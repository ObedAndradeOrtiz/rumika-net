<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryProductBatch extends Model
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'inventory_product_id',
        'lot_code',
        'expires_at',
        'received_at',
        'initial_quantity',
        'current_quantity',
        'unit_cost',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'date',
            'received_at' => 'date',
            'initial_quantity' => 'decimal:2',
            'current_quantity' => 'decimal:2',
            'unit_cost' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(InventoryProduct::class, 'inventory_product_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }
}
