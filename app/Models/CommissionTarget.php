<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionTarget extends Model
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'user_id',
        'period_type',
        'starts_at',
        'ends_at',
        'minimum_sales_amount',
        'minimum_commission_amount',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'minimum_sales_amount' => 'decimal:2',
            'minimum_commission_amount' => 'decimal:2',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
