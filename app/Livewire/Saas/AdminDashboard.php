<?php

namespace App\Livewire\Saas;

use App\Models\Company;
use App\Models\CompanyPlan;
use App\Models\User;
use App\Support\CompanyPlanLimits;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class AdminDashboard extends Component
{
    public string $search = '';

    public string $status = 'all';

    public string $plan = 'all';

    public ?int $expandedCompanyId = null;

    public ?int $editingCompanyId = null;

    public string $editCompanyName = '';

    public string $editPlanId = '';

    public string $editStatus = 'trial';

    public string $editBillingStatus = 'trial';

    public string $editLastPaidAt = '';

    public string $editAccessExpiresAt = '';

    public string $editNextPaymentDueAt = '';

    public string $editBillingNotes = '';

    public function editCompany(int $companyId): void
    {
        $company = Company::query()->with('plan')->findOrFail($companyId);

        $this->editingCompanyId = $company->id;
        $this->editCompanyName = $company->name;
        $this->editPlanId = (string) $company->company_plan_id;
        $this->editStatus = $company->status;
        $this->editBillingStatus = $company->billing_status ?: $company->status;
        $this->editLastPaidAt = $company->last_paid_at?->format('Y-m-d') ?? '';
        $this->editAccessExpiresAt = $company->access_expires_at?->format('Y-m-d') ?? '';
        $this->editNextPaymentDueAt = $company->next_payment_due_at?->format('Y-m-d') ?? '';
        $this->editBillingNotes = $company->billing_notes ?? '';
    }

    public function closeCompanyEditor(): void
    {
        $this->reset([
            'editingCompanyId',
            'editCompanyName',
            'editPlanId',
            'editStatus',
            'editBillingStatus',
            'editLastPaidAt',
            'editAccessExpiresAt',
            'editNextPaymentDueAt',
            'editBillingNotes',
        ]);
    }

    public function toggleCompanySystem(int $companyId): void
    {
        $this->expandedCompanyId = $this->expandedCompanyId === $companyId ? null : $companyId;
    }

    public function saveCompanyBilling(): void
    {
        $validated = $this->validate([
            'editCompanyName' => ['required', 'string', 'max:255'],
            'editPlanId' => ['required', 'exists:company_plans,id'],
            'editStatus' => ['required', 'in:trial,active,past_due,blocked,suspended'],
            'editBillingStatus' => ['required', 'in:trial,paid,pending,past_due,blocked'],
            'editLastPaidAt' => ['nullable', 'date'],
            'editAccessExpiresAt' => ['nullable', 'date'],
            'editNextPaymentDueAt' => ['nullable', 'date'],
            'editBillingNotes' => ['nullable', 'string', 'max:1500'],
        ]);

        $company = Company::query()->findOrFail($this->editingCompanyId);

        $company->update([
            'name' => $validated['editCompanyName'],
            'company_plan_id' => (int) $validated['editPlanId'],
            'status' => $validated['editStatus'],
            'billing_status' => $validated['editBillingStatus'],
            'last_paid_at' => $validated['editLastPaidAt'] ?: null,
            'access_expires_at' => $validated['editAccessExpiresAt'] ?: null,
            'next_payment_due_at' => $validated['editNextPaymentDueAt'] ?: null,
            'billing_notes' => $validated['editBillingNotes'] ?: null,
        ]);

        if ($validated['editBillingStatus'] === 'paid' && $validated['editLastPaidAt'] !== '') {
            $this->recordBillingPayment(
                $company->refresh(),
                $validated['editLastPaidAt'],
                $validated['editAccessExpiresAt'] ?: null,
                $validated['editBillingNotes'] ?: null
            );
        }

        $this->closeCompanyEditor();
    }

    public function grantMonthlyAccess(): void
    {
        $company = Company::query()->findOrFail($this->editingCompanyId);
        $paidAt = now();
        $expiresAt = $paidAt->copy()->addMonth();

        $company->update([
            'status' => 'active',
            'billing_status' => 'paid',
            'last_paid_at' => $paidAt,
            'access_expires_at' => $expiresAt,
            'next_payment_due_at' => $expiresAt->toDateString(),
        ]);

        $this->recordBillingPayment($company->refresh(), $paidAt->toDateString(), $expiresAt->toDateString(), 'Acceso mensual habilitado desde panel SaaS.');

        $this->editCompany($company->id);
    }

    public function render()
    {
        $companiesQuery = Company::query()
            ->withCount(['branches', 'users', 'clients', 'appointments', 'billingPayments'])
            ->with([
                'plan',
                'billingPayments' => fn ($query) => $query->latest('paid_at')->limit(6),
                'users' => fn ($query) => $query->latest('users.created_at')->limit(3),
            ])
            ->when($this->search !== '', function (Builder $query) {
                $search = '%'.trim($this->search).'%';

                $query->where(function (Builder $query) use ($search) {
                    $query->where('name', 'like', $search)
                        ->orWhere('legal_name', 'like', $search)
                        ->orWhere('slug', 'like', $search)
                        ->orWhereHas('users', fn (Builder $userQuery) => $userQuery
                            ->where('name', 'like', $search)
                            ->orWhere('email', 'like', $search));
                });
            })
            ->when($this->status !== 'all', fn (Builder $query) => $query->where('status', $this->status))
            ->when($this->plan !== 'all', fn (Builder $query) => $query->whereHas('plan', fn (Builder $query) => $query->where('slug', $this->plan)))
            ->latest();

        $companies = $companiesQuery->limit(30)->get();
        $allCompanies = Company::query()->with('plan')->get();

        return view('livewire.saas.admin-dashboard', [
            'companies' => $companies,
            'plans' => CompanyPlan::query()->orderBy('sort_order')->get(),
            'planCards' => CompanyPlan::query()->orderBy('sort_order')->get()->map(fn (CompanyPlan $plan) => $this->planCard($plan)),
            'latestUsers' => User::query()
                ->where('is_saas_admin', false)
                ->with('companies')
                ->latest()
                ->limit(8)
                ->get(),
            'totalCompanies' => $allCompanies->count(),
            'activeCompanies' => $allCompanies->where('status', 'active')->count(),
            'trialCompanies' => $allCompanies->where('status', 'trial')->count(),
            'monthlyPotential' => (float) $allCompanies->sum(fn (Company $company) => (float) ($company->plan?->monthly_price ?? 0)),
            'totalUsers' => User::query()->where('is_saas_admin', false)->count(),
        ]);
    }

    private function recordBillingPayment(Company $company, string $paidAt, ?string $periodEndsAt, ?string $notes): void
    {
        $periodStart = \Illuminate\Support\Carbon::parse($paidAt)->toDateString();
        $periodEnd = $periodEndsAt ?: \Illuminate\Support\Carbon::parse($paidAt)->addMonth()->toDateString();

        $exists = $company->billingPayments()
            ->whereDate('paid_at', $periodStart)
            ->whereDate('period_ends_at', $periodEnd)
            ->exists();

        if ($exists) {
            return;
        }

        $company->billingPayments()->create([
            'company_plan_id' => $company->company_plan_id,
            'paid_at' => $paidAt,
            'period_starts_at' => $periodStart,
            'period_ends_at' => $periodEnd,
            'amount' => $company->plan?->monthly_price ?? 0,
            'currency' => $company->plan?->currency ?? 'USD',
            'notes' => $notes,
            'recorded_by_user_id' => auth()->id(),
        ]);
    }

    private function planCard(CompanyPlan $plan): array
    {
        $features = $plan->features ?? [];
        $limits = $features['limits'] ?? [];
        $modules = $features['modules'] ?? [];

        return [
            'plan' => $plan,
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
            'registros' => 'Registros',
            'bitacora' => 'Bitacora',
            'resumen_financiero' => 'Resumen financiero',
        ];

        return collect($modules)->map(fn (string $module) => $labels[$module] ?? $module)->values()->all();
    }

    public function companyUsage(Company $company): array
    {
        return CompanyPlanLimits::usage($company);
    }

    public function companyLimit(Company $company, string $key): string
    {
        $limit = CompanyPlanLimits::limit($company, $key);

        return $limit === null ? 'Sin limite' : (string) $limit;
    }

    public function accessLabel(Company $company): string
    {
        if (CompanyPlanLimits::isExpired($company)) {
            return 'Vencido o bloqueado';
        }

        $days = CompanyPlanLimits::daysLeft($company);

        return $days === null ? 'Sin vencimiento' : "{$days} dias restantes";
    }
}
