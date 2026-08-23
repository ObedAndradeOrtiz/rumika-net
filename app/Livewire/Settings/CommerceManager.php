<?php

namespace App\Livewire\Settings;

use App\Models\Branch;
use App\Models\BusinessType;
use App\Models\Company;
use App\Models\Role;
use App\Support\ActiveBranch;
use App\Support\PhoneNumber;
use App\Support\RumikaPermissions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class CommerceManager extends Component
{
    use WithFileUploads;

    public ?int $editingId = null;

    public string $name = '';

    public ?int $businessTypeId = null;

    public string $countryCode = 'BO';

    public string $phone = '';

    public string $address = '';

    public string $status = 'active';

    public bool $usesTicketPrinter = false;

    public string $printerName = '';

    public string $printerBridgeUrl = '';

    public string $productCommissionPercent = '0';

    public string $productCommissionMinSale = '0';

    public string $serviceCommissionPercent = '0';

    public string $serviceCommissionMinSale = '0';

    public ?string $currentLogoPath = null;

    public $logo = null;

    public ?int $activeBranchId = null;

    public bool $showCommerceModal = false;

    public ?int $confirmingDeleteId = null;

    public function mount(): void
    {
        $company = $this->company();

        $this->ensureSystemRoles($company);
        $company->branches->each(fn (Branch $branch) => $this->grantCurrentUserBranchAccess($branch));

        $this->activeBranchId = session('active_branch_id')
            ?? $company->branches()->oldest()->value('id');
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showCommerceModal = true;
    }

    public function save(): void
    {
        $company = $this->company();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'businessTypeId' => ['required', 'exists:business_types,id'],
            'countryCode' => ['required', 'in:'.implode(',', array_keys(PhoneNumber::countries()))],
            'phone' => ['nullable', 'string', 'max:60'],
            'address' => ['nullable', 'string', 'max:180'],
            'status' => ['required', 'in:active,inactive'],
            'usesTicketPrinter' => ['boolean'],
            'printerName' => ['nullable', 'string', 'max:120'],
            'productCommissionPercent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'productCommissionMinSale' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'serviceCommissionPercent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'serviceCommissionMinSale' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'logo' => ['nullable', 'image', 'max:4096'],
        ]);

        $phone = null;

        if (trim($validated['phone']) !== '') {
            $phone = PhoneNumber::normalize($validated['phone'], $validated['countryCode']);

            if (! $phone) {
                $this->addError('phone', PhoneNumber::hint($validated['countryCode']));

                return;
            }
        }

        $currency = PhoneNumber::currencyFor($validated['countryCode']);

        $branch = $this->editingId
            ? $company->branches()->whereKey($this->editingId)->firstOrFail()
            : new Branch(['company_id' => $company->id]);

        $branch->fill([
            'business_type_id' => $validated['businessTypeId'],
            'name' => $validated['name'],
            'slug' => $this->uniqueSlug($company, $validated['name'], $branch->id),
            'country_code' => $validated['countryCode'],
            'currency_code' => $currency['currency_code'],
            'currency_symbol' => $currency['currency_symbol'],
            'phone' => $phone,
            'address' => $validated['address'] ?: null,
            'status' => $validated['status'],
            'uses_ticket_printer' => $validated['usesTicketPrinter'],
            'printer_name' => $validated['usesTicketPrinter'] ? ($validated['printerName'] ?: null) : null,
            'printer_bridge_url' => null,
            'product_commission_percent' => (float) ($validated['productCommissionPercent'] ?: 0),
            'product_commission_min_sale' => (float) ($validated['productCommissionMinSale'] ?: 0),
            'service_commission_percent' => (float) ($validated['serviceCommissionPercent'] ?: 0),
            'service_commission_min_sale' => (float) ($validated['serviceCommissionMinSale'] ?: 0),
        ]);

        if ($this->logo instanceof TemporaryUploadedFile) {
            if ($branch->logo_path) {
                Storage::disk('public')->delete($branch->logo_path);
            }

            $branch->logo_path = $this->logo->store('branch-logos', 'public');
        }

        $branch->save();
        $this->grantCurrentUserBranchAccess($branch);

        if (! $this->activeBranchId) {
            $this->selectBranch($branch->id);
        }

        $this->resetForm();
        $this->showCommerceModal = false;
        $this->dispatch('commerce-saved');
    }

    public function edit(int $branchId): void
    {
        $branch = $this->company()
            ->branches()
            ->whereKey($branchId)
            ->firstOrFail();

        $this->editingId = $branch->id;
        $this->name = $branch->name;
        $this->businessTypeId = $branch->business_type_id;
        $this->countryCode = $branch->country_code ?? 'BO';
        $this->phone = $branch->phone ?? '';
        $this->address = $branch->address ?? '';
        $this->status = $branch->status;
        $this->usesTicketPrinter = (bool) $branch->uses_ticket_printer;
        $this->printerName = $branch->printer_name ?? '';
        $this->printerBridgeUrl = $branch->printer_bridge_url ?? '';
        $this->productCommissionPercent = (string) ($branch->product_commission_percent ?? 0);
        $this->productCommissionMinSale = (string) ($branch->product_commission_min_sale ?? 0);
        $this->serviceCommissionPercent = (string) ($branch->service_commission_percent ?? 0);
        $this->serviceCommissionMinSale = (string) ($branch->service_commission_min_sale ?? 0);
        $this->currentLogoPath = $branch->logo_path;
        $this->logo = null;
        $this->showCommerceModal = true;
    }

    public function confirmDelete(int $branchId): void
    {
        $this->resetErrorBag();
        $this->confirmingDeleteId = $branchId;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
    }

    public function delete(int $branchId): void
    {
        $company = $this->company();

        if ($company->branches()->count() <= 1) {
            $this->addError('delete', 'Debe existir al menos una sucursal o comercio.');

            return;
        }

        $branch = $company->branches()->whereKey($branchId)->firstOrFail();
        $branch->delete();

        if ($this->activeBranchId === $branchId) {
            $this->selectBranch($company->branches()->oldest()->value('id'));
        }

        if ($this->editingId === $branchId) {
            $this->resetForm();
        }

        $this->confirmingDeleteId = null;
    }

    public function selectBranch(?int $branchId): void
    {
        if (! $branchId) {
            return;
        }

        $branch = $this->company()->branches()->whereKey($branchId)->firstOrFail();

        $this->activeBranchId = $branch->id;
        ActiveBranch::remember(Auth::user(), $branch->id);
        $this->dispatch('branch-switched');
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'businessTypeId', 'countryCode', 'phone', 'address', 'usesTicketPrinter', 'printerName', 'printerBridgeUrl', 'productCommissionPercent', 'productCommissionMinSale', 'serviceCommissionPercent', 'serviceCommissionMinSale', 'currentLogoPath', 'logo']);
        $this->countryCode = 'BO';
        $this->status = 'active';
        $this->productCommissionPercent = '0';
        $this->productCommissionMinSale = '0';
        $this->serviceCommissionPercent = '0';
        $this->serviceCommissionMinSale = '0';
        $this->resetErrorBag();
    }

    public function closeCommerceModal(): void
    {
        $this->showCommerceModal = false;
        $this->resetForm();
    }

    public function render()
    {
        $company = $this->company();

        return view('livewire.settings.commerce-manager', [
            'company' => $company,
            'branches' => $company->branches()
                ->with('businessType')
                ->latest()
                ->get(),
            'businessTypes' => BusinessType::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'roles' => $company->roles()->orderBy('id')->get(),
            'phoneCountries' => PhoneNumber::countries(),
        ]);
    }

    private function company(): Company
    {
        return Auth::user()
            ->companies()
            ->with('branches')
            ->firstOrFail();
    }

    private function uniqueSlug(Company $company, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'sucursal';
        $slug = $base;
        $counter = 2;

        while ($company->branches()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function ensureSystemRoles(Company $company): void
    {
        collect(RumikaPermissions::defaults())->each(fn (array $role) => Role::query()->firstOrCreate(
            ['company_id' => $company->id, 'slug' => $role['slug']],
            [
                ...$role,
                'scope' => 'company',
                'permissions' => [],
                'is_system' => true,
            ],
        ));
    }

    private function grantCurrentUserBranchAccess(Branch $branch): void
    {
        $roleId = $branch->company
            ->roles()
            ->where('slug', 'administrador')
            ->value('id');

        $branch->users()->syncWithoutDetaching([
            Auth::id() => [
                'role_id' => $roleId,
                'assigned_at' => now(),
            ],
        ]);
    }
}
