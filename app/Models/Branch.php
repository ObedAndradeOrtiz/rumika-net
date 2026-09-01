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
        'country_code',
        'currency_code',
        'currency_symbol',
        'phone',
        'address',
        'attendance_latitude',
        'attendance_longitude',
        'attendance_radius_meters',
        'logo_path',
        'uses_ticket_printer',
        'printer_name',
        'printer_bridge_url',
        'product_commission_percent',
        'product_commission_min_sale',
        'service_commission_percent',
        'service_commission_min_sale',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'uses_ticket_printer' => 'boolean',
            'product_commission_percent' => 'decimal:2',
            'product_commission_min_sale' => 'decimal:2',
            'service_commission_percent' => 'decimal:2',
            'service_commission_min_sale' => 'decimal:2',
            'attendance_latitude' => 'decimal:7',
            'attendance_longitude' => 'decimal:7',
            'attendance_radius_meters' => 'integer',
        ];
    }

    public function moneySymbol(): string
    {
        return $this->currency_symbol ?: 'Bs';
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

    public function staffSchedules(): HasMany
    {
        return $this->hasMany(StaffSchedule::class);
    }

    public function staffCheckIns(): HasMany
    {
        return $this->hasMany(StaffAttendanceRecord::class, 'check_in_branch_id');
    }

    public function staffCheckOuts(): HasMany
    {
        return $this->hasMany(StaffAttendanceRecord::class, 'check_out_branch_id');
    }
}
