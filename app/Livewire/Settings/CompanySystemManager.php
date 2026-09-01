<?php

namespace App\Livewire\Settings;

use App\Models\Company;
use App\Models\CompanyPlan;
use App\Support\CompanyPlanLimits;
use App\Support\RumikaAccess;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

    public function mount(): void
    {
        abort_unless($this->isCompanyAdmin(), 403);

        $this->loadBrandForm();
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
