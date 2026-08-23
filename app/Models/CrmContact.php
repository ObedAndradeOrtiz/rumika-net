<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmContact extends Model
{
    protected $fillable = [
        'company_id',
        'client_id',
        'name',
        'phone',
        'email',
        'notes',
        'last_interaction_at',
    ];

    protected function casts(): array
    {
        return [
            'last_interaction_at' => 'datetime',
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

    public function conversations(): HasMany
    {
        return $this->hasMany(CrmConversation::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CrmMessage::class);
    }
}
