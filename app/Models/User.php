<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'firebase_uid',
        'auth_provider',
        'profile_photo_path',
        'status',
        'requires_face_verification',
        'tracks_attendance',
        'can_use_system_outside_schedule',
        'face_descriptor',
        'face_registered_at',
        'last_face_verified_at',
        'last_face_verified_ip',
        'is_saas_admin',
        'terms_accepted_at',
        'terms_version',
        'terms_accepted_ip',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'requires_face_verification' => 'boolean',
            'tracks_attendance' => 'boolean',
            'can_use_system_outside_schedule' => 'boolean',
            'face_descriptor' => 'array',
            'face_registered_at' => 'datetime',
            'last_face_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_saas_admin' => 'boolean',
        ];
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class)
            ->withPivot(['role', 'joined_at'])
            ->withTimestamps();
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class)
            ->withPivot(['role_id', 'assigned_at'])
            ->withTimestamps();
    }

    public function transferDestinationBranches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_transfer_permissions', 'user_id', 'to_branch_id')
            ->withPivot(['company_id', 'from_branch_id', 'granted_by_user_id'])
            ->withTimestamps();
    }

    public function clinicalSpecialties(): BelongsToMany
    {
        return $this->belongsToMany(ClinicalSpecialty::class, 'clinical_specialty_user')->withTimestamps();
    }

    public function clinicalPatientAccesses(): HasMany
    {
        return $this->hasMany(ClinicalPatientAccess::class);
    }

    public function whatsappChannels(): BelongsToMany
    {
        return $this->belongsToMany(WhatsappChannel::class, 'user_whatsapp_channel')
            ->withPivot(['assigned_at'])
            ->withTimestamps();
    }

    public function staffSchedules(): HasMany
    {
        return $this->hasMany(StaffSchedule::class);
    }

    public function staffAttendanceRecords(): HasMany
    {
        return $this->hasMany(StaffAttendanceRecord::class);
    }
}
