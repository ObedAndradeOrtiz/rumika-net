<?php

namespace App\Livewire\Saas;

use App\Models\Company;
use App\Models\CompanyPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class AdminDashboard extends Component
{
    public string $search = '';

    public string $status = 'all';

    public string $plan = 'all';

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

        $this->editCompany($company->id);
    }

    public function render()
    {
        $companiesQuery = Company::query()
            ->withCount(['branches', 'users', 'clients', 'appointments'])
            ->with(['plan', 'users' => fn ($query) => $query->latest('users.created_at')->limit(3)])
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
}
