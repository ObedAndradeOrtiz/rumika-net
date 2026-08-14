<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryAsset extends Model
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'code',
        'name',
        'category',
        'purchase_amount',
        'purchase_date',
        'status',
        'wasted_at',
        'waste_reason',
        'repair_total',
    ];

    protected function casts(): array
    {
        return [
            'purchase_amount' => 'decimal:2',
            'purchase_date' => 'date',
            'wasted_at' => 'date',
            'repair_total' => 'decimal:2',
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

    public function repairs(): HasMany
    {
        return $this->hasMany(InventoryAssetRepair::class);
    }
}
