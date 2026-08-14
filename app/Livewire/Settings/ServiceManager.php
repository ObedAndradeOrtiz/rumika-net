<?php

namespace App\Livewire\Settings;

use App\Models\Company;
use App\Models\Service;
use App\Models\ServicePackage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class ServiceManager extends Component
{
    use WithPagination;

    public bool $showServiceModal = false;

    public bool $showPackageModal = false;

    public string $activeTab = 'services';

    public string $serviceSearch = '';

    public string $packageSearch = '';

    public ?int $editingServiceId = null;

    public ?int $editingPackageId = null;

    public ?int $confirmingServiceDeleteId = null;

    public ?int $confirmingPackageDeleteId = null;

    public string $serviceName = '';

    public string $serviceDescription = '';

    public string $servicePrice = '';

    public string $serviceDuration = '';

    public ?int $serviceBranchId = null;

    public string $serviceStatus = 'available';

    public string $packageName = '';

    public string $packageDescription = '';

    public string $packagePrice = '';

    public ?int $packageBranchId = null;

    public string $packageStatus = 'available';

    public string $startsAt = '';

    public string $expiresAt = '';

    public array $packageServiceIds = [];

    public function setActiveTab(string $tab): void
    {
        if (! in_array($tab, ['services', 'packages'], true)) {
            return;
        }

        $this->activeTab = $tab;
    }

    public function updatedServiceSearch(): void
    {
        $this->resetPage('servicesPage');
    }

    public function updatedPackageSearch(): void
    {
        $this->resetPage('packagesPage');
    }

    public function createService(): void
    {
        $this->resetServiceForm();
        $this->activeTab = 'services';
        $this->showServiceModal = true;
    }

    public function saveService(): void
    {
        $company = $this->company();
        $branchIds = $this->availableBranchIds();

        $validated = $this->validate([
            'serviceName' => ['required', 'string', 'max:120'],
            'serviceDescription' => ['nullable', 'string', 'max:400'],
            'servicePrice' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'serviceDuration' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'serviceBranchId' => ['nullable', Rule::in($branchIds)],
            'serviceStatus' => ['required', 'in:available,unavailable'],
        ]);

        $service = $this->editingServiceId
            ? $company->services()->whereKey($this->editingServiceId)->firstOrFail()
            : new Service(['company_id' => $company->id]);

        $service->fill([
            'branch_id' => $validated['serviceBranchId'],
            'name' => $validated['serviceName'],
            'description' => $validated['serviceDescription'] ?: null,
            'price' => $validated['servicePrice'],
            'duration_minutes' => $validated['serviceDuration'] ?: null,
            'status' => $validated['serviceStatus'],
        ]);
        $service->save();

        $this->resetServiceForm();
        $this->showServiceModal = false;
    }

    public function editService(int $serviceId): void
    {
        $service = $this->company()->services()->whereKey($serviceId)->firstOrFail();

        $this->editingServiceId = $service->id;
        $this->serviceName = $service->name;
        $this->serviceDescription = $service->description ?? '';
        $this->servicePrice = (string) $service->price;
        $this->serviceDuration = (string) ($service->duration_minutes ?? '');
        $this->serviceBranchId = $service->branch_id;
        $this->serviceStatus = $service->status;
        $this->showServiceModal = true;
    }

    public function confirmDeleteService(int $serviceId): void
    {
        $this->confirmingServiceDeleteId = $serviceId;
    }

    public function deleteService(int $serviceId): void
    {
        $service = $this->company()->services()->whereKey($serviceId)->firstOrFail();
        $service->delete();
        $this->confirmingServiceDeleteId = null;
    }

    public function createPackage(): void
    {
        $this->resetPackageForm();
        $this->activeTab = 'packages';
        $this->showPackageModal = true;
    }

    public function savePackage(): void
    {
        $company = $this->company();
        $branchIds = $this->availableBranchIds();
        $serviceIds = $company->services()->pluck('id')->all();

        $validated = $this->validate([
            'packageName' => ['required', 'string', 'max:120'],
            'packageDescription' => ['nullable', 'string', 'max:400'],
            'packagePrice' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'packageBranchId' => ['nullable', Rule::in($branchIds)],
            'packageStatus' => ['required', 'in:available,unavailable'],
            'startsAt' => ['nullable', 'date'],
            'expiresAt' => ['nullable', 'date', 'after_or_equal:startsAt'],
            'packageServiceIds' => ['array'],
            'packageServiceIds.*' => [Rule::in($serviceIds)],
        ]);

        $package = $this->editingPackageId
            ? $company->servicePackages()->whereKey($this->editingPackageId)->firstOrFail()
            : new ServicePackage(['company_id' => $company->id]);

        $package->fill([
            'branch_id' => $validated['packageBranchId'],
            'name' => $validated['packageName'],
            'description' => $validated['packageDescription'] ?: null,
            'price' => $validated['packagePrice'],
            'starts_at' => $validated['startsAt'] ?: null,
            'expires_at' => $validated['expiresAt'] ?: null,
            'status' => $validated['packageStatus'],
        ]);
        $package->save();

        $package->services()->sync(
            collect($validated['packageServiceIds'] ?? [])
                ->mapWithKeys(fn ($serviceId) => [(int) $serviceId => ['quantity' => 1]])
                ->all()
        );

        $this->resetPackageForm();
        $this->showPackageModal = false;
    }

    public function editPackage(int $packageId): void
    {
        $package = $this->company()
            ->servicePackages()
            ->with('services')
            ->whereKey($packageId)
            ->firstOrFail();

        $this->editingPackageId = $package->id;
        $this->packageName = $package->name;
        $this->packageDescription = $package->description ?? '';
        $this->packagePrice = (string) $package->price;
        $this->packageBranchId = $package->branch_id;
        $this->packageStatus = $package->status;
        $this->startsAt = $package->starts_at?->format('Y-m-d') ?? '';
        $this->expiresAt = $package->expires_at?->format('Y-m-d') ?? '';
        $this->packageServiceIds = $package->services->pluck('id')->map(fn ($id) => (string) $id)->all();
        $this->showPackageModal = true;
    }

    public function confirmDeletePackage(int $packageId): void
    {
        $this->confirmingPackageDeleteId = $packageId;
    }

    public function deletePackage(int $packageId): void
    {
        $package = $this->company()->servicePackages()->whereKey($packageId)->firstOrFail();
        $package->delete();
        $this->confirmingPackageDeleteId = null;
    }

    public function closeServiceModal(): void
    {
        $this->showServiceModal = false;
        $this->resetServiceForm();
    }

    public function closePackageModal(): void
    {
        $this->showPackageModal = false;
        $this->resetPackageForm();
    }

    public function render()
    {
        $company = $this->company();
        $serviceSearch = trim($this->serviceSearch);
        $packageSearch = trim($this->packageSearch);

        return view('livewire.settings.service-manager', [
            'services' => $company->services()
                ->with('branch')
                ->when($serviceSearch !== '', function ($query) use ($serviceSearch) {
                    $query->where(function ($nested) use ($serviceSearch) {
                        $nested
                            ->where('name', 'like', "%{$serviceSearch}%")
                            ->orWhere('description', 'like', "%{$serviceSearch}%");
                    });
                })
                ->latest()
                ->paginate(15, ['*'], 'servicesPage'),
            'packages' => $company->servicePackages()
                ->with(['branch', 'services'])
                ->when($packageSearch !== '', function ($query) use ($packageSearch) {
                    $query->where(function ($nested) use ($packageSearch) {
                        $nested
                            ->where('name', 'like', "%{$packageSearch}%")
                            ->orWhere('description', 'like', "%{$packageSearch}%");
                    });
                })
                ->latest()
                ->paginate(15, ['*'], 'packagesPage'),
            'servicesTotal' => $company->services()->count(),
            'packagesTotal' => $company->servicePackages()->count(),
            'packageServiceOptions' => $company->services()->orderBy('name')->get(),
            'branches' => $this->availableBranches(),
        ]);
    }

    private function company(): Company
    {
        return Auth::user()->companies()->firstOrFail();
    }

    private function availableBranchIds(): array
    {
        return $this->availableBranches()->pluck('id')->all();
    }

    private function availableBranches()
    {
        $company = $this->company();
        $branches = Auth::user()
            ->branches()
            ->where('company_id', $company->id)
            ->orderBy('name')
            ->get();

        return $branches->isNotEmpty()
            ? $branches
            : $company->branches()->orderBy('name')->get();
    }

    private function resetServiceForm(): void
    {
        $this->reset(['editingServiceId', 'serviceName', 'serviceDescription', 'servicePrice', 'serviceDuration', 'serviceBranchId']);
        $this->serviceStatus = 'available';
        $this->resetErrorBag();
    }

    private function resetPackageForm(): void
    {
        $this->reset(['editingPackageId', 'packageName', 'packageDescription', 'packagePrice', 'packageBranchId', 'startsAt', 'expiresAt', 'packageServiceIds']);
        $this->packageStatus = 'available';
        $this->resetErrorBag();
    }
}
