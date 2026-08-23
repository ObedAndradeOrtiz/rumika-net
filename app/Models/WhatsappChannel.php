<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsappChannel extends Model
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'name',
        'phone_number',
        'phone_number_id',
        'waba_id',
        'api_version',
        'access_token',
        'verify_token',
        'audio_converter_api_key',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'verify_token' => 'encrypted',
            'audio_converter_api_key' => 'encrypted',
            'is_active' => 'boolean',
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

    public function conversations(): HasMany
    {
        return $this->hasMany(CrmConversation::class, 'whatsapp_channel_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CrmMessage::class, 'whatsapp_channel_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_whatsapp_channel')
            ->withPivot(['assigned_at'])
            ->withTimestamps();
    }
}
