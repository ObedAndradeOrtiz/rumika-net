<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TreatmentPayment extends Model
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'client_id',
        'appointment_id',
        'treatment_plan_id',
        'received_by_user_id',
        'performed_by_user_id',
        'amount',
        'method',
        'invoice_requested',
        'reference',
        'notes',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'invoice_requested' => 'boolean',
            'paid_at' => 'datetime',
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

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function treatmentPlan(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlan::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_user_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_user_id');
    }

    public function splits(): HasMany
    {
        return $this->hasMany(TreatmentPaymentSplit::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TreatmentPaymentItem::class);
    }

    public function chargePayments(): HasMany
    {
        return $this->hasMany(ClientChargePayment::class);
    }
}
