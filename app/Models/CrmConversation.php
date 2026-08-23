<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmConversation extends Model
{
    protected $fillable = [
        'company_id',
        'whatsapp_channel_id',
        'crm_contact_id',
        'client_id',
        'status',
        'unread_count',
        'last_message',
        'last_message_at',
        'last_customer_message_at',
        'is_demo',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'last_customer_message_at' => 'datetime',
            'is_demo' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(WhatsappChannel::class, 'whatsapp_channel_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(CrmContact::class, 'crm_contact_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CrmMessage::class)->oldest('message_at')->oldest('id');
    }
}
