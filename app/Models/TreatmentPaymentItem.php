<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TreatmentPaymentItem extends Model
{
    protected $fillable = [
        'treatment_payment_id',
        'client_charge_id',
        'appointment_service_id',
        'inventory_product_id',
        'inventory_product_batch_id',
        'sold_by_user_id',
        'type',
        'name',
        'quantity',
        'unit_price',
        'charged_total',
        'total',
        'commission_percent',
        'commission_amount',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'charged_total' => 'decimal:2',
            'total' => 'decimal:2',
            'commission_percent' => 'decimal:2',
            'commission_amount' => 'decimal:2',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(TreatmentPayment::class, 'treatment_payment_id');
    }

    public function appointmentService(): BelongsTo
    {
        return $this->belongsTo(AppointmentService::class);
    }

    public function charge(): BelongsTo
    {
        return $this->belongsTo(ClientCharge::class, 'client_charge_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(InventoryProduct::class, 'inventory_product_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryProductBatch::class, 'inventory_product_batch_id');
    }

    public function soldBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sold_by_user_id');
    }
}
