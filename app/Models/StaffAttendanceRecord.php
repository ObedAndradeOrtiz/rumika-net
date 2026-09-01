<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffAttendanceRecord extends Model
{
    protected $fillable = [
        'company_id',
        'user_id',
        'work_date',
        'status',
        'check_in_at',
        'check_in_branch_id',
        'check_in_latitude',
        'check_in_longitude',
        'check_in_distance_meters',
        'check_in_face_similarity',
        'check_in_photo_path',
        'check_out_at',
        'check_out_branch_id',
        'check_out_latitude',
        'check_out_longitude',
        'check_out_distance_meters',
        'check_out_face_similarity',
        'check_out_photo_path',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function checkInBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'check_in_branch_id');
    }

    public function checkOutBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'check_out_branch_id');
    }
}
