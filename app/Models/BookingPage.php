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
        'hero_label',
        'button_label',
        'success_message',
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
        'min_days_ahead',
        'max_days_ahead',
        'show_prices',
        'show_branch_cards',
        'show_service_duration',
        'show_company_logo',
        'require_identity',
        'require_email',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'slot_interval_minutes' => 'integer',
            'default_duration_minutes' => 'integer',
            'min_days_ahead' => 'integer',
            'max_days_ahead' => 'integer',
            'show_prices' => 'boolean',
            'show_branch_cards' => 'boolean',
            'show_service_duration' => 'boolean',
            'show_company_logo' => 'boolean',
            'require_identity' => 'boolean',
            'require_email' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
