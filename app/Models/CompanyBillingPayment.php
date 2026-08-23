<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyBillingPayment extends Model
{
    protected $fillable = [
        'company_id',
        'company_plan_id',
        'paid_at',
        'period_starts_at',
        'period_ends_at',
        'amount',
        'currency',
        'reference',
        'notes',
        'recorded_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'period_starts_at' => 'date',
            'period_ends_at' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(CompanyPlan::class, 'company_plan_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
