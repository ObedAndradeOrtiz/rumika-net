<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyPlan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'monthly_price',
        'currency',
        'features',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'is_active' => 'boolean',
            'monthly_price' => 'decimal:2',
        ];
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }
}
