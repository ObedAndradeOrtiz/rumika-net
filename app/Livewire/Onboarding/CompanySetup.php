<?php

namespace App\Livewire\Onboarding;

use App\Models\Branch;
use App\Models\BusinessType;
use App\Support\ActiveBranch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

class CompanySetup extends Component
{
    public string $companyName = '';

    public string $branchName = '';

    public string $businessTypeId = '';

    public string $phone = '';

    public string $address = '';

    public function mount(): void
    {
        $company = Auth::user()->companies()->firstOrFail();

        if ($company->onboarding_completed_at) {
            $this->redirect(route('dashboard', absolute: false), navigate: true);

            return;
        }

        $this->companyName = str_starts_with($company->name, 'Empresa de ') ? '' : $company->name;
        $this->branchName = '';
    }

    public function save(): void
    {
        $company = Auth::user()->companies()->firstOrFail();

        $validated = $this->validate([
            'companyName' => ['required', 'string', 'max:120'],
            'branchName' => ['required', 'string', 'max:120'],
            'businessTypeId' => ['required', 'integer', Rule::exists('business_types', 'id')->where('is_active', true)],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:180'],
        ]);

        $company->update([
            'name' => $validated['companyName'],
            'slug' => $company->slug ?: $this->uniqueCompanySlug($validated['companyName']),
            'onboarding_completed_at' => now(),
        ]);

        $branch = Branch::query()->create([
            'company_id' => $company->id,
            'business_type_id' => $validated['businessTypeId'],
            'name' => $validated['branchName'],
            'slug' => $this->uniqueBranchSlug($company->id, $validated['branchName']),
            'phone' => $validated['phone'] ?: null,
            'address' => $validated['address'] ?: null,
            'status' => 'active',
        ]);

        $branch->users()->syncWithoutDetaching([
            Auth::id() => [
                'assigned_at' => now(),
            ],
        ]);

        ActiveBranch::remember(Auth::user(), $branch->id);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }

    #[Layout('layouts.guest')]
    public function render()
    {
        return view('livewire.onboarding.company-setup', [
            'businessTypes' => BusinessType::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'description']),
        ]);
    }

    private function uniqueCompanySlug(string $name): string
    {
        $base = Str::slug($name) ?: 'empresa';
        $slug = $base;
        $counter = 2;

        while (\App\Models\Company::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function uniqueBranchSlug(int $companyId, string $name): string
    {
        $base = Str::slug($name) ?: 'sucursal';
        $slug = $base;
        $counter = 2;

        while (Branch::query()->where('company_id', $companyId)->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
