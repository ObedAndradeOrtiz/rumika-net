<?php

namespace App\Livewire\Settings;

use App\Models\Company;
use App\Models\CompanyPlan;
use App\Models\BookingPage;
use App\Support\CompanyPlanLimits;
use App\Support\RumikaAccess;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class CompanySystemManager extends Component
{
    use WithFileUploads;

    public ?string $requestedPlanSlug = null;

    public string $companyName = '';

    public string $companyLegalName = '';

    public string $currentCompanyLogoPath = '';

    public $companyLogo = null;

    public string $bookingSlug = '';

    public string $bookingTitle = '';

    public string $bookingSubtitle = '';

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

    public bool $bookingShowPrices = true;

    public bool $bookingIsActive = true;

    public string $currentBookingBackgroundPath = '';

    public ?bool $bookingSlugAvailable = null;

    public $bookingBackgroundImage = null;

    public function mount(): void
    {
        abort_unless($this->isCompanyAdmin(), 403);

        $this->loadBrandForm();
        $this->loadBookingForm();
    }

    public function requestPlan(string $slug): void
    {
        $plan = CompanyPlan::query()->where('slug', $slug)->first();

        if (! $plan || ! $this->canRequestPlan($plan)) {
            return;
        }

        $this->requestedPlanSlug = $slug;
    }

    public function closeRequest(): void
    {
        $this->requestedPlanSlug = null;
    }

    public function saveBrand(): void
    {
        $company = $this->company();

        $validated = $this->validate([
            'companyName' => ['required', 'string', 'max:120'],
            'companyLegalName' => ['nullable', 'string', 'max:160'],
            'companyLogo' => ['nullable', 'image', 'max:2048'],
        ]);

        $company->name = trim($validated['companyName']);
        $company->legal_name = trim((string) $validated['companyLegalName']) ?: null;

        if ($this->companyLogo) {
            if ($company->logo_path) {
                Storage::disk('public')->delete($company->logo_path);
            }

            $company->logo_path = $this->companyLogo->store('company-logos', 'public');
        }

        $company->save();

        $this->reset('companyLogo');
        $this->loadBrandForm();
        $this->dispatch('company-brand-saved');
    }

    public function updatedBookingSlug(): void
    {
        $this->bookingSlug = Str::slug($this->bookingSlug);
        $this->bookingSlugAvailable = $this->isBookingSlugAvailable();
    }

    public function saveBookingPage(): void
    {
        $company = $this->company();
        $pageId = $company->bookingPage?->id;
        $this->bookingSlug = Str::slug($this->bookingSlug ?: $company->name);

        $validated = $this->validate([
            'bookingSlug' => [
                'required',
                'alpha_dash',
                'min:3',
                'max:80',
                Rule::unique('booking_pages', 'slug')->ignore($pageId),
            ],
            'bookingTitle' => ['nullable', 'string', 'max:120'],
            'bookingSubtitle' => ['nullable', 'string', 'max:180'],
            'bookingTemplate' => ['required', Rule::in(['clean', 'medical', 'wellness'])],
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
            'bookingShowPrices' => ['boolean'],
            'bookingIsActive' => ['boolean'],
            'bookingBackgroundImage' => ['nullable', 'image', 'max:4096'],
        ]);

        $page = $company->bookingPage ?: new BookingPage(['company_id' => $company->id]);
        $page->fill([
            'slug' => $validated['bookingSlug'],
            'title' => trim((string) $validated['bookingTitle']) ?: null,
            'subtitle' => trim((string) $validated['bookingSubtitle']) ?: null,
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
            'show_prices' => (bool) $validated['bookingShowPrices'],
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
        $company = $this->company();
        $plans = CompanyPlan::query()->where('is_active', true)->orderBy('sort_order')->get();

        return view('livewire.settings.company-system-manager', [
            'company' => $company->load([
                'plan',
                'branches' => fn ($query) => $query->where('status', 'active')->orderBy('name'),
                'billingPayments' => fn ($query) => $query->latest('paid_at')->limit(8),
            ]),
            'plans' => $plans->map(fn (CompanyPlan $plan) => $this->planCard($plan)),
            'usage' => CompanyPlanLimits::usage($company),
            'limits' => [
                'branches' => $this->limitText($company, 'branches'),
                'users' => $this->limitText($company, 'users'),
                'clients' => $this->limitText($company, 'clients'),
                'products' => $this->limitText($company, 'products'),
                'appointments_per_month' => $this->limitText($company, 'appointments_per_month'),
            ],
            'accessLabel' => $this->accessLabel($company),
            'requestedPlan' => $this->requestedPlanSlug
                ? $plans->firstWhere('slug', $this->requestedPlanSlug)
                : null,
            'bookingPage' => $company->bookingPage,
            'bookingTemplates' => $this->bookingTemplates(),
            'bookingGeneralUrl' => $this->bookingSlug ? route('booking.public', $this->bookingSlug) : null,
            'bookingBranchLinks' => $this->bookingBranchLinks($company),
        ]);
    }

    private function company(): Company
    {
        return Auth::user()->companies()->with('plan')->firstOrFail();
    }

    private function loadBrandForm(): void
    {
        $company = $this->company();

        $this->companyName = $company->name;
        $this->companyLegalName = $company->legal_name ?? '';
        $this->currentCompanyLogoPath = $company->logo_path ?? '';
    }

    private function loadBookingForm(): void
    {
        $company = $this->company();
        $page = $company->bookingPage;

        $this->bookingSlug = $page?->slug ?? Str::slug($company->name);
        $this->bookingTitle = $page?->title ?? 'Agenda tu cita en '.$company->name;
        $this->bookingSubtitle = $page?->subtitle ?? 'Elige tu sucursal, tratamiento y horario disponible.';
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
        $this->bookingShowPrices = (bool) ($page?->show_prices ?? true);
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
            'clean' => ['name' => 'Simple', 'description' => 'Clara, directa y rapida para cualquier rubro.'],
            'medical' => ['name' => 'Clinica', 'description' => 'Mas seria para centros medicos, odontologos y consultorios.'],
            'wellness' => ['name' => 'Bienestar', 'description' => 'Suave para spas, estetica, belleza y atencion personal.'],
        ];
    }

    private function bookingBranchLinks(Company $company): array
    {
        if ($this->bookingSlug === '') {
            return [];
        }

        return $company->branches()
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(fn ($branch) => [
                'id' => $branch->id,
                'name' => $branch->name,
                'url' => route('booking.public', ['slug' => $this->bookingSlug, 'sucursal' => $branch->id]),
            ])
            ->all();
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

    private function planCard(CompanyPlan $plan): array
    {
        $features = $plan->features ?? [];
        $limits = $features['limits'] ?? [];
        $modules = $features['modules'] ?? [];

        return [
            'plan' => $plan,
            'is_current' => $this->company()->company_plan_id === $plan->id,
            'can_request' => $this->canRequestPlan($plan),
            'modules' => in_array('*', $modules, true) ? ['Todos los modulos'] : $this->moduleLabels($modules),
            'limits' => [
                'Sucursales' => $limits['branches'] ?? 'Sin limite',
                'Usuarios' => $limits['users'] ?? 'Sin limite',
                'Clientes' => $limits['clients'] ?? 'Sin limite',
                'Productos' => $limits['products'] ?? 'Sin limite',
                'Citas/mes' => $limits['appointments_per_month'] ?? 'Sin limite',
            ],
            'notes' => $features['notes'] ?? [],
        ];
    }

    private function moduleLabels(array $modules): array
    {
        $labels = [
            'inicio' => 'Inicio',
            'agenda' => 'Agenda',
            'clientes' => 'Clientes',
            'historia_clinica' => 'Historia clinica',
            'servicios' => 'Servicios',
            'caja' => 'Caja',
            'ventas_productos' => 'Ventas directas',
            'facturacion' => 'Facturacion',
            'deudas' => 'Deudas',
            'reportes' => 'Reportes',
            'comisiones' => 'Comisiones',
            'sucursales' => 'Sucursales',
            'usuarios' => 'Usuarios',
            'roles' => 'Roles',
            'inventario' => 'Inventario',
            'inventario_operaciones' => 'Operaciones de inventario',
            'gastos' => 'Gastos',
            'estadisticas' => 'Estadisticas',
        ];

        return collect($modules)->map(fn (string $module) => $labels[$module] ?? Str::headline($module))->values()->all();
    }

    private function limitText(Company $company, string $key): string
    {
        $limit = CompanyPlanLimits::limit($company, $key);

        return $limit === null ? 'Sin limite' : (string) $limit;
    }

    private function canRequestPlan(CompanyPlan $plan): bool
    {
        $company = $this->company();
        $currentPlan = $company->plan;

        if (! $currentPlan) {
            return true;
        }

        if ($company->company_plan_id === $plan->id) {
            return false;
        }

        return (int) $plan->sort_order > (int) $currentPlan->sort_order;
    }

    private function accessLabel(Company $company): string
    {
        if (CompanyPlanLimits::isExpired($company)) {
            return 'Vencido o bloqueado';
        }

        $days = CompanyPlanLimits::daysLeft($company);

        return $days === null ? 'Sin vencimiento' : "{$days} dias restantes";
    }
}
