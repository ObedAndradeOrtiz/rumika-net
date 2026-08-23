<?php

namespace App\Livewire\Saas;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class AdminDashboard extends Component
{
    public string $search = '';

    public string $status = 'all';

    public string $plan = 'all';

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
            'plans' => \App\Models\CompanyPlan::query()->orderBy('sort_order')->get(),
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
