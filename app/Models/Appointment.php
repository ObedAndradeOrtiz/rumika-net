<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Appointment extends Model
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'client_id',
        'attended_by_user_id',
        'treatment_plan_id',
        'rescheduled_from_id',
        'scheduled_at',
        'duration_minutes',
        'status',
        'attended',
        'locked_by_payment',
        'clinical_notes',
        'reschedule_reason',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'duration_minutes' => 'integer',
            'attended' => 'boolean',
            'locked_by_payment' => 'boolean',
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

    public function attendedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attended_by_user_id');
    }

    public function treatmentPlan(): BelongsTo
    {
        return $this->belongsTo(TreatmentPlan::class);
    }

    public function rescheduledFrom(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'rescheduled_from_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(AppointmentService::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(TreatmentPayment::class);
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

    public function rescheduledAppointments()
    {
        return $this->hasMany(Appointment::class, 'rescheduled_from_id');
    }
}
