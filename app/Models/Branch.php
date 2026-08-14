<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = [
        'company_id',
        'business_type_id',
        'name',
        'slug',
        'phone',
        'address',
        'logo_path',
        'uses_ticket_printer',
        'printer_name',
        'printer_bridge_url',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'uses_ticket_printer' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function businessType(): BelongsTo
    {
        return $this->belongsTo(BusinessType::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role_id', 'assigned_at'])
            ->withTimestamps();
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function treatmentPlans(): HasMany
    {
        return $this->hasMany(TreatmentPlan::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function treatmentPayments(): HasMany
    {
        return $this->hasMany(TreatmentPayment::class);
    }

    public function cashboxSessions(): HasMany
    {
        return $this->hasMany(CashboxSession::class);
    }

    public function cashboxTickets(): HasMany
    {
        return $this->hasMany(CashboxTicket::class);
    }

    public function servicePackages(): HasMany
    {
        return $this->hasMany(ServicePackage::class);
    }

    public function inventoryBatches(): HasMany
    {
        return $this->hasMany(InventoryProductBatch::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function inventoryCounts(): HasMany
    {
        return $this->hasMany(InventoryCount::class);
    }

    public function inventoryAssets(): HasMany
    {
        return $this->hasMany(InventoryAsset::class);
    }
}
