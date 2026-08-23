<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappTemplate extends Model
{
    protected $fillable = [
        'company_id',
        'whatsapp_channel_id',
        'name',
        'category',
        'language',
        'body',
        'status',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(WhatsappChannel::class, 'whatsapp_channel_id');
    }
}
