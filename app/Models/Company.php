<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'name',
        'legal_name',
        'slug',
        'logo_path',
        'company_plan_id',
        'timezone',
        'status',
        'onboarding_completed_at',
        'trial_ends_at',
        'access_expires_at',
        'last_paid_at',
        'next_payment_due_at',
        'billing_status',
        'billing_notes',
    ];

    protected function casts(): array
    {
        return [
            'onboarding_completed_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'access_expires_at' => 'datetime',
            'last_paid_at' => 'datetime',
            'next_payment_due_at' => 'date',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(CompanyPlan::class, 'company_plan_id');
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
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

    public function clientCharges(): HasMany
    {
        return $this->hasMany(ClientCharge::class);
    }

    public function servicePackages(): HasMany
    {
        return $this->hasMany(ServicePackage::class);
    }

    public function inventorySuppliers(): HasMany
    {
        return $this->hasMany(InventorySupplier::class);
    }

    public function inventoryBrands(): HasMany
    {
        return $this->hasMany(InventoryBrand::class);
    }

    public function inventoryUseAreas(): HasMany
    {
        return $this->hasMany(InventoryUseArea::class);
    }

    public function inventoryProducts(): HasMany
    {
        return $this->hasMany(InventoryProduct::class);
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

    public function inventoryCountItems(): HasMany
    {
        return $this->hasMany(InventoryCountItem::class);
    }

    public function inventoryAssets(): HasMany
    {
        return $this->hasMany(InventoryAsset::class);
    }

    public function expenseTypes(): HasMany
    {
        return $this->hasMany(ExpenseType::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function cashboxSessions(): HasMany
    {
        return $this->hasMany(CashboxSession::class);
    }

    public function cashboxTickets(): HasMany
    {
        return $this->hasMany(CashboxTicket::class);
    }

    public function clinicalSpecialties(): HasMany
    {
        return $this->hasMany(ClinicalSpecialty::class);
    }

    public function clinicalTemplates(): HasMany
    {
        return $this->hasMany(ClinicalTemplate::class);
    }

    public function clinicalRecords(): HasMany
    {
        return $this->hasMany(ClinicalRecord::class);
    }

    public function clinicalDocuments(): HasMany
    {
        return $this->hasMany(ClinicalDocument::class);
    }

    public function clinicalPrescriptions(): HasMany
    {
        return $this->hasMany(ClinicalPrescription::class);
    }

    public function clinicalPatientAccesses(): HasMany
    {
        return $this->hasMany(ClinicalPatientAccess::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role', 'joined_at'])
            ->withTimestamps();
    }
}
