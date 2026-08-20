<?php

namespace App\Livewire\App;

use App\Models\Branch;
use App\Models\Company;
use App\Support\ActiveBranch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;

class BranchSwitcher extends Component
{
    public bool $showBranchModal = false;
    public bool $showCompanyModal = false;
    public string $companyName = '';

    public function open(): void
    {
        $this->showBranchModal = true;
    }

    public function close(): void
    {
        $this->showBranchModal = false;
    }

    public function editCompanyName(): void
    {
        $company = $this->companyOrFail();

        $this->companyName = $company->name;
        $this->showCompanyModal = true;
    }

    public function closeCompanyModal(): void
    {
        $this->showCompanyModal = false;
        $this->companyName = '';
        $this->resetErrorBag();
    }

    public function saveCompanyName(): void
    {
        $company = $this->companyOrFail();

        $validated = $this->validate([
            'companyName' => ['required', 'string', 'max:120'],
        ]);

        $company->update([
            'name' => $validated['companyName'],
            'slug' => $company->slug ?: Str::slug($validated['companyName']),
        ]);

        $this->showCompanyModal = false;
        $this->dispatch('company-name-updated');
    }

    public function select(int $branchId): void
    {
        $branch = $this->availableBranches()->firstWhere('id', $branchId);

        if (! $branch) {
            return;
        }

        ActiveBranch::remember(Auth::user(), $branch->id);
        $this->showBranchModal = false;
        $this->dispatch('branch-switched');
    }

    public function render()
    {
        $branches = $this->availableBranches();
        $activeBranch = ActiveBranch::resolve(Auth::user(), $branches);

        return view('livewire.app.branch-switcher', [
            'branches' => $branches,
            'activeBranch' => $activeBranch,
            'company' => $this->company(),
        ]);
    }

    private function company(): ?Company
    {
        return Auth::user()->companies()->first();
    }

    private function companyOrFail(): Company
    {
        return Auth::user()->companies()->firstOrFail();
    }

    private function availableBranches()
    {
        $user = Auth::user();
        $branches = $user->branches()
            ->with(['businessType', 'company'])
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        if ($branches->isNotEmpty()) {
            return $branches;
        }

        $company = $user->companies()->first();

        return $company
            ? Branch::query()
                ->where('company_id', $company->id)
                ->with(['businessType', 'company'])
                ->where('status', 'active')
                ->orderBy('name')
                ->get()
            : collect();
    }
}
