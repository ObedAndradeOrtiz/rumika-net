<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingPage extends Model
{
    protected $fillable = [
        'company_id',
        'slug',
        'title',
        'subtitle',
        'template',
        'mode',
        'primary_color',
        'accent_color',
        'background_color',
        'background_image_path',
        'font_family',
        'icon_shape',
        'available_from',
        'available_to',
        'slot_interval_minutes',
        'default_duration_minutes',
        'show_prices',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'slot_interval_minutes' => 'integer',
            'default_duration_minutes' => 'integer',
            'show_prices' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
