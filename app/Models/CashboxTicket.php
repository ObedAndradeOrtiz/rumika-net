<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashboxTicket extends Model
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'cashbox_session_id',
        'treatment_payment_id',
        'expense_id',
        'type',
        'ticket_number',
        'title',
        'payload',
        'printed_by_user_id',
        'printed_at',
        'reprint_count',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'printed_at' => 'datetime',
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

    public function session(): BelongsTo
    {
        return $this->belongsTo(CashboxSession::class, 'cashbox_session_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(TreatmentPayment::class, 'treatment_payment_id');
    }

    public function printedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'printed_by_user_id');
    }
}
