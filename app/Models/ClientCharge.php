<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClientCharge extends Model
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'client_id',
        'appointment_id',
        'appointment_service_id',
        'inventory_product_id',
        'inventory_product_batch_id',
        'sold_by_user_id',
        'type',
        'name',
        'quantity',
        'unit_price',
        'total_amount',
        'paid_amount',
        'balance_amount',
        'status',
        'charged_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'balance_amount' => 'decimal:2',
            'charged_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ClientChargePayment::class);
    }

    public function soldBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sold_by_user_id');
    }
}
