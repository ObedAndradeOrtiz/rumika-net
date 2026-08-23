<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmMessage extends Model
{
    protected $fillable = [
        'company_id',
        'crm_conversation_id',
        'whatsapp_channel_id',
        'crm_contact_id',
        'wamid',
        'direction',
        'type',
        'body',
        'status',
        'media_id',
        'media_url',
        'media_mime_type',
        'media_filename',
        'raw_payload',
        'message_at',
        'is_read',
        'reply_to_wamid',
        'reply_preview',
    ];

    protected function casts(): array
    {
        return [
            'raw_payload' => 'array',
            'message_at' => 'datetime',
            'is_read' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(CrmConversation::class, 'crm_conversation_id');
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(WhatsappChannel::class, 'whatsapp_channel_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(CrmContact::class, 'crm_contact_id');
    }
}
