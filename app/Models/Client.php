<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Client extends Model
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'full_name',
        'identity_number',
        'phone',
        'email',
        'birth_date',
        'clinical_notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
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

    public function treatmentPlans(): HasMany
    {
        return $this->hasMany(TreatmentPlan::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(TreatmentPayment::class);
    }

    public function phones(): HasMany
    {
        return $this->hasMany(ClientPhone::class);
    }

    public function primaryPhone(): HasOne
    {
        return $this->hasOne(ClientPhone::class)->where('is_primary', true);
    }

    public function displayPhone(): ?string
    {
        return $this->primaryPhone?->phone ?? $this->phone;
    }

    public function displayContact(): ?string
    {
        $phone = $this->displayPhone();

        if ($phone) {
            return $phone;
        }

        return $this->identity_number ? 'CI '.$this->identity_number : null;
    }

    public function charges(): HasMany
    {
        return $this->hasMany(ClientCharge::class);
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

    public function clinicalAccesses(): HasMany
    {
        return $this->hasMany(ClinicalPatientAccess::class);
    }
}
