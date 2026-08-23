<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClinicalRecord extends Model
{
    protected $fillable = [
        'company_id',
        'client_id',
        'appointment_id',
        'appointment_service_id',
        'service_id',
        'clinical_template_id',
        'created_by_user_id',
        'title',
        'type',
        'content',
        'data',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function appointmentService(): BelongsTo
    {
        return $this->belongsTo(AppointmentService::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ClinicalTemplate::class, 'clinical_template_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ClinicalDocument::class);
    }
}
