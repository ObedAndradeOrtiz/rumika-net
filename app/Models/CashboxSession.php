<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashboxSession extends Model
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'business_date',
        'shift_number',
        'status',
        'opening_amount',
        'opened_by_user_id',
        'closed_by_user_id',
        'opened_at',
        'closed_at',
        'opening_notes',
        'closing_notes',
        'expected_cash_amount',
        'counted_cash_amount',
        'cash_difference',
        'cash_total',
        'qr_total',
        'expense_total',
        'net_total',
    ];

    protected function casts(): array
    {
        return [
            'business_date' => 'date',
            'opening_amount' => 'decimal:2',
            'expected_cash_amount' => 'decimal:2',
            'counted_cash_amount' => 'decimal:2',
            'cash_difference' => 'decimal:2',
            'cash_total' => 'decimal:2',
            'qr_total' => 'decimal:2',
            'expense_total' => 'decimal:2',
            'net_total' => 'decimal:2',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
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

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by_user_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(CashboxTicket::class);
    }
}
