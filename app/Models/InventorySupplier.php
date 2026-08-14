<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventorySupplier extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'contact_name',
        'phone',
        'email',
        'status',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function brands(): HasMany
    {
        return $this->hasMany(InventoryBrand::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(InventoryProduct::class);
    }
}
