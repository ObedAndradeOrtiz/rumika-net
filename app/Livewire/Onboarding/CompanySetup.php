<?php

namespace App\Livewire\Onboarding;

use App\Models\Branch;
use App\Models\BusinessType;
use App\Models\Company;
use App\Support\ActiveBranch;
use App\Support\RumikaAccess;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        if ($company->onboarding_completed_at && $company->branches()->whereNotNull('business_type_id')->where('name', '!=', '')->exists()) {
            $this->redirect(route('dashboard', absolute: false), navigate: true);

            return;
        }

        $this->companyName = str_starts_with($company->name, 'Empresa de ') ? '' : $company->name;
        $branch = $company->branches()->first();
        $this->branchName = $branch?->name ?? '';
        $this->businessTypeId = (string) ($branch?->business_type_id ?? '');
        $this->phone = $branch?->phone ?? '';
        $this->address = $branch?->address ?? '';
    }

    public function save(): void
    {
        $company = Auth::user()->companies()->firstOrFail();
        abort_unless(in_array($company->pivot->role, RumikaAccess::ADMIN_ROLES, true), 403);

        foreach (['companyName', 'branchName', 'businessTypeId', 'phone', 'address'] as $field) {
            $this->{$field} = trim($this->{$field});
        }

        $validated = $this->validate([
            'companyName' => ['required', 'string', 'max:120'],
            'branchName' => ['required', 'string', 'max:120'],
            'businessTypeId' => ['required', 'integer', Rule::exists('business_types', 'id')->where('is_active', true)],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:180'],
        ], [], [
            'companyName' => 'nombre de empresa',
            'branchName' => 'primera sucursal',
            'businessTypeId' => 'tipo de negocio',
            'phone' => 'telefono',
            'address' => 'direccion',
        ]);

        try {
            $branch = DB::transaction(function () use ($company, $validated) {
                $company = $company->newQuery()->lockForUpdate()->findOrFail($company->id);

                // A retried request must reuse the branch created by the first request.
                if ($company->onboarding_completed_at && $company->branches()->whereNotNull('business_type_id')->where('name', '!=', '')->exists()) {
                    return $company->branches()->whereNotNull('business_type_id')->where('name', '!=', '')->firstOrFail();
                }

                $branch = $company->branches()->first() ?? new Branch([
                    'company_id' => $company->id,
                    'slug' => $this->uniqueBranchSlug($company->id, $validated['branchName']),
                ]);
                $branch->fill([
                    'business_type_id' => $validated['businessTypeId'],
                    'name' => $validated['branchName'],
                    'phone' => $validated['phone'] ?: null,
                    'address' => $validated['address'] ?: null,
                    'status' => 'active',
                ])->save();

                $branch->users()->syncWithoutDetaching([
                    Auth::id() => ['assigned_at' => now()],
                ]);

                $company->update([
                    'name' => $validated['companyName'],
                    'slug' => $company->slug ?: $this->uniqueCompanySlug($validated['companyName']),
                    'onboarding_completed_at' => now(),
                ]);

                return $branch;
            });
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('registration', 'No pudimos guardar tu empresa. Tus datos siguen en el formulario. Intenta nuevamente.');

            return;
        }

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

        while (Company::query()->where('slug', $slug)->exists()) {
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
