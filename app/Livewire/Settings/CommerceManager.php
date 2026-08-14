<?php

namespace App\Livewire\Settings;

use App\Models\Branch;
use App\Models\BusinessType;
use App\Models\Company;
use App\Models\Role;
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

    public string $phone = '';

    public string $address = '';

    public string $status = 'active';

    public bool $usesTicketPrinter = false;

    public string $printerName = '';

    public string $printerBridgeUrl = '';

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
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:180'],
            'status' => ['required', 'in:active,inactive'],
            'usesTicketPrinter' => ['boolean'],
            'printerName' => ['nullable', 'string', 'max:120'],
            'logo' => ['nullable', 'image', 'max:4096'],
        ]);

        $branch = $this->editingId
            ? $company->branches()->whereKey($this->editingId)->firstOrFail()
            : new Branch(['company_id' => $company->id]);

        $branch->fill([
            'business_type_id' => $validated['businessTypeId'],
            'name' => $validated['name'],
            'slug' => $this->uniqueSlug($company, $validated['name'], $branch->id),
            'phone' => $validated['phone'] ?: null,
            'address' => $validated['address'] ?: null,
            'status' => $validated['status'],
            'uses_ticket_printer' => $validated['usesTicketPrinter'],
            'printer_name' => $validated['usesTicketPrinter'] ? ($validated['printerName'] ?: null) : null,
            'printer_bridge_url' => null,
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
        $this->phone = $branch->phone ?? '';
        $this->address = $branch->address ?? '';
        $this->status = $branch->status;
        $this->usesTicketPrinter = (bool) $branch->uses_ticket_printer;
        $this->printerName = $branch->printer_name ?? '';
        $this->printerBridgeUrl = $branch->printer_bridge_url ?? '';
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
        session(['active_branch_id' => $branch->id]);
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'businessTypeId', 'phone', 'address', 'usesTicketPrinter', 'printerName', 'printerBridgeUrl', 'currentLogoPath', 'logo']);
        $this->status = 'active';
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
