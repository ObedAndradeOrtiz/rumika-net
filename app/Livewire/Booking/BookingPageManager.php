<?php

namespace App\Livewire\Booking;

use App\Models\BookingPage;
use App\Models\Company;
use App\Support\RumikaAccess;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class BookingPageManager extends Component
{
    use WithFileUploads;

    public string $bookingSlug = '';
    public string $bookingTitle = '';
    public string $bookingSubtitle = '';
    public string $bookingHeroLabel = 'Reserva online';
    public string $bookingButtonLabel = 'Agendar cita';
    public string $bookingSuccessMessage = '';
    public string $bookingTemplate = 'clean';
    public string $bookingMode = 'general';
    public string $bookingPrimaryColor = '#008b7d';
    public string $bookingAccentColor = '#dff7f2';
    public string $bookingBackgroundColor = '#f6f8fb';
    public string $bookingFontFamily = 'Figtree';
    public string $bookingIconShape = 'rounded';
    public string $bookingAvailableFrom = '09:00';
    public string $bookingAvailableTo = '18:00';
    public string $bookingSlotIntervalMinutes = '30';
    public string $bookingDefaultDurationMinutes = '60';
    public string $bookingMinDaysAhead = '0';
    public string $bookingMaxDaysAhead = '30';
    public bool $bookingShowPrices = true;
    public bool $bookingShowBranchCards = true;
    public bool $bookingShowServiceDuration = true;
    public bool $bookingShowCompanyLogo = true;
    public bool $bookingRequireIdentity = false;
    public bool $bookingRequireEmail = false;
    public bool $bookingIsActive = true;
    public string $currentBookingBackgroundPath = '';
    public ?bool $bookingSlugAvailable = null;
    public $bookingBackgroundImage = null;

    public function mount(): void
    {
        abort_unless($this->isCompanyAdmin(), 403);

        $this->loadBookingForm();
    }

    public function updatedBookingSlug(): void
    {
        $this->bookingSlug = Str::slug($this->bookingSlug);
        $this->bookingSlugAvailable = $this->isBookingSlugAvailable();
    }

    public function applyTemplate(string $template): void
    {
        if (! array_key_exists($template, $this->bookingTemplates())) {
            return;
        }

        $preset = $this->bookingTemplates()[$template];
        $this->bookingTemplate = $template;
        $this->bookingPrimaryColor = $preset['primary'];
        $this->bookingAccentColor = $preset['accent'];
        $this->bookingBackgroundColor = $preset['background'];
        $this->bookingFontFamily = $preset['font'];
        $this->bookingIconShape = $preset['shape'];
    }

    public function saveBookingPage(): void
    {
        $company = $this->company();
        $pageId = $company->bookingPage?->id;
        $this->bookingSlug = Str::slug($this->bookingSlug ?: $company->name);

        $validated = $this->validate([
            'bookingSlug' => ['required', 'alpha_dash', 'min:3', 'max:80', Rule::unique('booking_pages', 'slug')->ignore($pageId)],
            'bookingTitle' => ['nullable', 'string', 'max:120'],
            'bookingSubtitle' => ['nullable', 'string', 'max:220'],
            'bookingHeroLabel' => ['nullable', 'string', 'max:60'],
            'bookingButtonLabel' => ['nullable', 'string', 'max:40'],
            'bookingSuccessMessage' => ['nullable', 'string', 'max:220'],
            'bookingTemplate' => ['required', Rule::in(array_keys($this->bookingTemplates()))],
            'bookingMode' => ['required', Rule::in(['general', 'branch'])],
            'bookingPrimaryColor' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'bookingAccentColor' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'bookingBackgroundColor' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'bookingFontFamily' => ['required', Rule::in(['Figtree', 'Inter', 'Nunito', 'Poppins'])],
            'bookingIconShape' => ['required', Rule::in(['rounded', 'circle', 'soft'])],
            'bookingAvailableFrom' => ['required', 'date_format:H:i'],
            'bookingAvailableTo' => ['required', 'date_format:H:i', 'after:bookingAvailableFrom'],
            'bookingSlotIntervalMinutes' => ['required', 'integer', 'min:10', 'max:120'],
            'bookingDefaultDurationMinutes' => ['required', 'integer', 'min:10', 'max:480'],
            'bookingMinDaysAhead' => ['required', 'integer', 'min:0', 'max:365'],
            'bookingMaxDaysAhead' => ['required', 'integer', 'min:1', 'max:365'],
            'bookingShowPrices' => ['boolean'],
            'bookingShowBranchCards' => ['boolean'],
            'bookingShowServiceDuration' => ['boolean'],
            'bookingShowCompanyLogo' => ['boolean'],
            'bookingRequireIdentity' => ['boolean'],
            'bookingRequireEmail' => ['boolean'],
            'bookingIsActive' => ['boolean'],
            'bookingBackgroundImage' => ['nullable', 'image', 'max:4096'],
        ]);

        if ((int) $validated['bookingMaxDaysAhead'] < (int) $validated['bookingMinDaysAhead']) {
            $this->addError('bookingMaxDaysAhead', 'Debe ser mayor o igual al minimo.');
            return;
        }

        $page = $company->bookingPage ?: new BookingPage(['company_id' => $company->id]);
        $page->fill([
            'slug' => $validated['bookingSlug'],
            'title' => trim((string) $validated['bookingTitle']) ?: null,
            'subtitle' => trim((string) $validated['bookingSubtitle']) ?: null,
            'hero_label' => trim((string) $validated['bookingHeroLabel']) ?: null,
            'button_label' => trim((string) $validated['bookingButtonLabel']) ?: null,
            'success_message' => trim((string) $validated['bookingSuccessMessage']) ?: null,
            'template' => $validated['bookingTemplate'],
            'mode' => $validated['bookingMode'],
            'primary_color' => $validated['bookingPrimaryColor'],
            'accent_color' => $validated['bookingAccentColor'],
            'background_color' => $validated['bookingBackgroundColor'],
            'font_family' => $validated['bookingFontFamily'],
            'icon_shape' => $validated['bookingIconShape'],
            'available_from' => $validated['bookingAvailableFrom'],
            'available_to' => $validated['bookingAvailableTo'],
            'slot_interval_minutes' => (int) $validated['bookingSlotIntervalMinutes'],
            'default_duration_minutes' => (int) $validated['bookingDefaultDurationMinutes'],
            'min_days_ahead' => (int) $validated['bookingMinDaysAhead'],
            'max_days_ahead' => (int) $validated['bookingMaxDaysAhead'],
            'show_prices' => (bool) $validated['bookingShowPrices'],
            'show_branch_cards' => (bool) $validated['bookingShowBranchCards'],
            'show_service_duration' => (bool) $validated['bookingShowServiceDuration'],
            'show_company_logo' => (bool) $validated['bookingShowCompanyLogo'],
            'require_identity' => (bool) $validated['bookingRequireIdentity'],
            'require_email' => (bool) $validated['bookingRequireEmail'],
            'is_active' => (bool) $validated['bookingIsActive'],
        ]);

        if ($this->bookingBackgroundImage) {
            if ($page->background_image_path) {
                Storage::disk('public')->delete($page->background_image_path);
            }

            $page->background_image_path = $this->bookingBackgroundImage->store('booking-backgrounds', 'public');
        }

        $page->save();

        $this->reset('bookingBackgroundImage');
        $this->loadBookingForm();
        $this->bookingSlugAvailable = true;
        $this->dispatch('booking-page-saved');
    }

    public function render()
    {
        $company = $this->company()->load(['branches' => fn ($query) => $query->where('status', 'active')->orderBy('name')]);

        return view('livewire.booking.booking-page-manager', [
            'company' => $company,
            'bookingPage' => $company->bookingPage,
            'bookingTemplates' => $this->bookingTemplates(),
            'bookingGeneralUrl' => $this->bookingSlug ? route('booking.public', $this->bookingSlug) : null,
            'bookingBranchLinks' => $this->bookingBranchLinks($company),
            'previewServices' => $company->services()->where('status', 'active')->orderBy('name')->limit(5)->get(),
        ]);
    }

    private function loadBookingForm(): void
    {
        $company = $this->company();
        $page = $company->bookingPage;

        $this->bookingSlug = $page?->slug ?? Str::slug($company->name);
        $this->bookingTitle = $page?->title ?? 'Agenda tu cita en '.$company->name;
        $this->bookingSubtitle = $page?->subtitle ?? 'Elige tu sucursal, tratamiento y horario disponible.';
        $this->bookingHeroLabel = $page?->hero_label ?? 'Reserva online';
        $this->bookingButtonLabel = $page?->button_label ?? 'Agendar cita';
        $this->bookingSuccessMessage = $page?->success_message ?? 'Tu cita fue agendada correctamente. Te esperamos en la sucursal seleccionada.';
        $this->bookingTemplate = $page?->template ?? 'clean';
        $this->bookingMode = $page?->mode ?? 'general';
        $this->bookingPrimaryColor = $page?->primary_color ?? '#008b7d';
        $this->bookingAccentColor = $page?->accent_color ?? '#dff7f2';
        $this->bookingBackgroundColor = $page?->background_color ?? '#f6f8fb';
        $this->bookingFontFamily = $page?->font_family ?? 'Figtree';
        $this->bookingIconShape = $page?->icon_shape ?? 'rounded';
        $this->bookingAvailableFrom = $page?->available_from ? substr((string) $page->available_from, 0, 5) : '09:00';
        $this->bookingAvailableTo = $page?->available_to ? substr((string) $page->available_to, 0, 5) : '18:00';
        $this->bookingSlotIntervalMinutes = (string) ($page?->slot_interval_minutes ?? 30);
        $this->bookingDefaultDurationMinutes = (string) ($page?->default_duration_minutes ?? 60);
        $this->bookingMinDaysAhead = (string) ($page?->min_days_ahead ?? 0);
        $this->bookingMaxDaysAhead = (string) ($page?->max_days_ahead ?? 30);
        $this->bookingShowPrices = (bool) ($page?->show_prices ?? true);
        $this->bookingShowBranchCards = (bool) ($page?->show_branch_cards ?? true);
        $this->bookingShowServiceDuration = (bool) ($page?->show_service_duration ?? true);
        $this->bookingShowCompanyLogo = (bool) ($page?->show_company_logo ?? true);
        $this->bookingRequireIdentity = (bool) ($page?->require_identity ?? false);
        $this->bookingRequireEmail = (bool) ($page?->require_email ?? false);
        $this->bookingIsActive = (bool) ($page?->is_active ?? true);
        $this->currentBookingBackgroundPath = $page?->background_image_path ?? '';
        $this->bookingSlugAvailable = $this->isBookingSlugAvailable();
    }

    private function isBookingSlugAvailable(): bool
    {
        $company = $this->company();
        $slug = Str::slug($this->bookingSlug);

        if ($slug === '') {
            return false;
        }

        return ! BookingPage::query()
            ->where('slug', $slug)
            ->where('company_id', '!=', $company->id)
            ->exists();
    }

    private function bookingTemplates(): array
    {
        return [
            'clean' => [
                'name' => 'Simple',
                'description' => 'Clara, rapida y directa para cualquier rubro.',
                'primary' => '#008b7d',
                'accent' => '#dff7f2',
                'background' => '#f6f8fb',
                'font' => 'Figtree',
                'shape' => 'rounded',
            ],
            'medical' => [
                'name' => 'Clinica',
                'description' => 'Seria para centros medicos, odontologos y consultorios.',
                'primary' => '#0f766e',
                'accent' => '#e0f2fe',
                'background' => '#f8fafc',
                'font' => 'Inter',
                'shape' => 'soft',
            ],
            'wellness' => [
                'name' => 'Bienestar',
                'description' => 'Suave para spas, estetica, belleza y atencion personal.',
                'primary' => '#7c3aed',
                'accent' => '#f3e8ff',
                'background' => '#fff7ed',
                'font' => 'Nunito',
                'shape' => 'circle',
            ],
        ];
    }

    private function bookingBranchLinks(Company $company): array
    {
        if ($this->bookingSlug === '') {
            return [];
        }

        return $company->branches
            ->map(fn ($branch) => [
                'id' => $branch->id,
                'name' => $branch->name,
                'url' => route('booking.public', ['slug' => $this->bookingSlug, 'sucursal' => $branch->id]),
            ])
            ->all();
    }

    private function company(): Company
    {
        return Auth::user()->companies()->with(['plan', 'bookingPage'])->firstOrFail();
    }

    private function isCompanyAdmin(): bool
    {
        $user = Auth::user();
        $company = $user?->companies()->first();

        if (! $user || ! $company) {
            return false;
        }

        $role = $user->companies()
            ->where('companies.id', $company->id)
            ->value('company_user.role');

        if (in_array($role, RumikaAccess::ADMIN_ROLES, true)) {
            return true;
        }

        return $user->branches()
            ->where('branches.company_id', $company->id)
            ->leftJoin('roles', 'roles.id', '=', 'branch_user.role_id')
            ->whereIn('roles.slug', RumikaAccess::ADMIN_ROLES)
            ->exists();
    }
}
