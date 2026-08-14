<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientChargePayment extends Model
{
    protected $fillable = [
        'client_charge_id',
        'treatment_payment_id',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function charge(): BelongsTo
    {
        return $this->belongsTo(ClientCharge::class, 'client_charge_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(TreatmentPayment::class, 'treatment_payment_id');
    }
}
