<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductSale extends Model
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'buyer_id',
        'sold_by_user_id',
        'received_by_user_id',
        'buyer_name',
        'buyer_nit',
        'buyer_phone',
        'buyer_email',
        'subtotal',
        'paid_amount',
        'cash_amount',
        'qr_amount',
        'method',
        'invoice_requested',
        'reference',
        'notes',
        'sold_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'cash_amount' => 'decimal:2',
            'qr_amount' => 'decimal:2',
            'invoice_requested' => 'boolean',
            'sold_at' => 'datetime',
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

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class);
    }

    public function soldBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sold_by_user_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProductSaleItem::class);
    }
}
