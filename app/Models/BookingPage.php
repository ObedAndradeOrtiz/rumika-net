<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
        'promotional_image_path',
        'font_family',
        'icon_shape',
        'available_from',
        'available_to',
        'slot_interval_minutes',
        'max_appointments_per_slot',
        'default_duration_minutes',
        'min_days_ahead',
        'max_days_ahead',
        'show_prices',
        'show_branch_cards',
        'show_service_duration',
        'show_company_logo',
        'require_identity',
        'require_email',
        'publish_all_services',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'slot_interval_minutes' => 'integer',
            'max_appointments_per_slot' => 'integer',
            'default_duration_minutes' => 'integer',
            'min_days_ahead' => 'integer',
            'max_days_ahead' => 'integer',
            'show_prices' => 'boolean',
            'show_branch_cards' => 'boolean',
            'show_service_duration' => 'boolean',
            'show_company_logo' => 'boolean',
            'require_identity' => 'boolean',
            'require_email' => 'boolean',
            'publish_all_services' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'booking_page_services')
            ->withPivot(['promotional_price', 'is_promoted'])
            ->withTimestamps();
    }
}
