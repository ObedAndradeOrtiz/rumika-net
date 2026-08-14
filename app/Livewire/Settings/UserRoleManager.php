<?php

namespace App\Livewire\Settings;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Support\RumikaPermissions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class UserRoleManager extends Component
{
    use WithFileUploads;

    public string $userSearch = '';

    public string $userBranchFilter = '';

    public string $userRoleFilter = '';

    public string $userStatusFilter = '';

    public ?int $editingUserId = null;

    public string $userName = '';

    public string $userEmail = '';

    public string $userPassword = '';

    public string $userStatus = 'active';

    public ?string $currentUserPhotoPath = null;

    public ?TemporaryUploadedFile $userPhoto = null;

    public ?int $userRoleId = null;

    public array $userBranchIds = [];

    public ?int $editingRoleId = null;

    public string $roleName = '';

    public string $roleDescription = '';

    public array $rolePermissions = [];

    public bool $editingSystemRole = false;

    public bool $showUserModal = false;

    public bool $showRoleModal = false;

    public ?int $confirmingUserDeleteId = null;

    public ?int $confirmingRoleDeleteId = null;

    public function mount(): void
    {
        $company = $this->company();

        $this->ensureSystemRoles($company);
        $this->userRoleId = $company->roles()->where('slug', 'recepcion')->value('id')
            ?? $company->roles()->oldest()->value('id');
        $this->userBranchIds = $company->branches()->pluck('id')->map(fn ($id) => (string) $id)->all();
        $this->rolePermissions = RumikaPermissions::onlyView();
    }

    public function createUser(): void
    {
        $this->resetUserForm();
        $this->showUserModal = true;
    }

    public function saveUser(): void
    {
        $company = $this->company();

        $validated = $this->validate([
            'userName' => ['required', 'string', 'max:120'],
            'userEmail' => [
                'required',
                'email',
                'max:180',
                Rule::unique('users', 'email')->ignore($this->editingUserId),
            ],
            'userPassword' => [$this->editingUserId ? 'nullable' : 'required', 'string', 'min:8'],
            'userPhoto' => ['nullable', 'image', 'max:2048'],
            'userStatus' => ['required', 'in:active,inactive'],
            'userRoleId' => ['required', Rule::exists('roles', 'id')->where('company_id', $company->id)],
            'userBranchIds' => ['required', 'array', 'min:1'],
            'userBranchIds.*' => [Rule::exists('branches', 'id')->where('company_id', $company->id)],
        ]);

        $user = $this->editingUserId
            ? $company->users()->whereKey($this->editingUserId)->firstOrFail()
            : new User();

        $user->fill([
            'name' => $validated['userName'],
            'email' => $validated['userEmail'],
            'status' => $validated['userStatus'],
        ]);

        if ($validated['userPassword']) {
            $user->password = $validated['userPassword'];
        }

        if ($this->userPhoto) {
            $user->profile_photo_path = $this->userPhoto->store('user-photos', 'public');
        }

        $user->save();

        $company->users()->syncWithoutDetaching([
            $user->id => [
                'role' => 'member',
                'joined_at' => now(),
            ],
        ]);

        $companyBranchIds = $company->branches()->pluck('id');

        DB::table('branch_user')
            ->where('user_id', $user->id)
            ->whereIn('branch_id', $companyBranchIds)
            ->delete();

        $user->branches()->syncWithoutDetaching(
            collect($validated['userBranchIds'])
                ->mapWithKeys(fn (string|int $branchId) => [
                    (int) $branchId => [
                        'role_id' => $validated['userRoleId'],
                        'assigned_at' => now(),
                    ],
                ])
                ->all()
        );

        $this->resetUserForm($company);
        $this->showUserModal = false;
        $this->dispatch('user-saved');
    }

    public function editUser(int $userId): void
    {
        $company = $this->company();
        $user = $company->users()
            ->whereKey($userId)
            ->with('branches')
            ->firstOrFail();

        $this->editingUserId = $user->id;
        $this->userName = $user->name;
        $this->userEmail = $user->email;
        $this->userPassword = '';
        $this->userStatus = $user->status ?? 'active';
        $this->currentUserPhotoPath = $user->profile_photo_path;
        $this->userBranchIds = $user->branches->pluck('id')->map(fn ($id) => (string) $id)->all();
        $this->userRoleId = $user->branches->first()?->pivot?->role_id
            ?? $company->roles()->oldest()->value('id');
        $this->showUserModal = true;
    }

    public function confirmDeleteUser(int $userId): void
    {
        $this->resetErrorBag();
        $this->confirmingUserDeleteId = $userId;
    }

    public function cancelDeleteUser(): void
    {
        $this->confirmingUserDeleteId = null;
    }

    public function deleteUser(int $userId): void
    {
        if ($userId === Auth::id()) {
            $this->addError('userDelete', 'No puedes eliminar tu propio acceso.');

            return;
        }

        $company = $this->company();
        $user = $company->users()->whereKey($userId)->firstOrFail();
        $user->update(['status' => 'inactive']);

        if ($this->editingUserId === $userId) {
            $this->resetUserForm($company);
        }

        $this->confirmingUserDeleteId = null;
    }

    public function createRole(): void
    {
        $this->resetRoleForm();
        $this->showRoleModal = true;
    }

    public function saveRole(): void
    {
        $company = $this->company();

        $validated = $this->validate([
            'roleName' => ['required', 'string', 'max:90'],
            'roleDescription' => ['nullable', 'string', 'max:180'],
            'rolePermissions' => ['array'],
        ]);

        $role = $this->editingRoleId
            ? $company->roles()->whereKey($this->editingRoleId)->firstOrFail()
            : new Role(['company_id' => $company->id]);

        $role->fill([
            'name' => $validated['roleName'],
            'slug' => $role->exists ? $role->slug : $this->uniqueRoleSlug($company, $validated['roleName']),
            'scope' => 'company',
            'description' => $validated['roleDescription'] ?: null,
            'permissions' => $this->cleanPermissions($validated['rolePermissions'] ?? []),
            'is_system' => $role->exists ? $role->is_system : false,
        ]);

        $role->save();

        $this->resetRoleForm();
        $this->showRoleModal = false;
        $this->dispatch('role-saved');
    }

    public function editRole(int $roleId): void
    {
        $role = $this->company()->roles()->whereKey($roleId)->firstOrFail();

        $this->editingRoleId = $role->id;
        $this->roleName = $role->name;
        $this->roleDescription = $role->description ?? '';
        $this->rolePermissions = $role->permissions ?? [];
        $this->editingSystemRole = $role->is_system;
        $this->showRoleModal = true;
    }

    public function confirmDeleteRole(int $roleId): void
    {
        $this->resetErrorBag();
        $this->confirmingRoleDeleteId = $roleId;
    }

    public function cancelDeleteRole(): void
    {
        $this->confirmingRoleDeleteId = null;
    }

    public function deleteRole(int $roleId): void
    {
        $company = $this->company();
        $role = $company->roles()->whereKey($roleId)->firstOrFail();

        if ($role->is_system) {
            $this->addError('roleDelete', 'Los roles base se pueden editar, pero no eliminar.');

            return;
        }

        if (DB::table('branch_user')->where('role_id', $role->id)->exists()) {
            $this->addError('roleDelete', 'Este rol esta asignado a usuarios.');

            return;
        }

        $role->delete();

        if ($this->editingRoleId === $roleId) {
            $this->resetRoleForm();
        }

        $this->confirmingRoleDeleteId = null;
    }

    public function closeUserModal(): void
    {
        $this->showUserModal = false;
        $this->resetUserForm();
    }

    public function closeRoleModal(): void
    {
        $this->showRoleModal = false;
        $this->resetRoleForm();
    }

    public function resetUserForm(?Company $company = null): void
    {
        $company ??= $this->company();

        $this->reset(['editingUserId', 'userName', 'userEmail', 'userPassword', 'currentUserPhotoPath', 'userPhoto']);
        $this->userStatus = 'active';
        $this->userRoleId = $company->roles()->where('slug', 'recepcion')->value('id')
            ?? $company->roles()->oldest()->value('id');
        $this->userBranchIds = $company->branches()->pluck('id')->map(fn ($id) => (string) $id)->all();
        $this->resetErrorBag();
    }

    public function resetRoleForm(): void
    {
        $this->reset(['editingRoleId', 'roleName', 'roleDescription', 'editingSystemRole']);
        $this->rolePermissions = RumikaPermissions::onlyView();
        $this->resetErrorBag();
    }

    public function render()
    {
        $company = $this->company();
        $search = trim($this->userSearch);
        $usersQuery = $company->users()
            ->with('branches.businessType')
            ->when($search !== '', fn ($query) => $query->where(fn ($nested) => $nested
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->when($this->userStatusFilter !== '', fn ($query) => $query->where('status', $this->userStatusFilter))
            ->when($this->userBranchFilter !== '', fn ($query) => $query->whereHas('branches', fn ($branchQuery) => $branchQuery
                ->where('branches.id', $this->userBranchFilter)))
            ->when($this->userRoleFilter !== '', fn ($query) => $query->whereHas('branches', fn ($branchQuery) => $branchQuery
                ->where('branch_user.role_id', $this->userRoleFilter)));

        return view('livewire.settings.user-role-manager', [
            'company' => $company,
            'users' => $usersQuery
                ->orderBy('name')
                ->get(),
            'branches' => $company->branches()
                ->with('businessType')
                ->orderBy('name')
                ->get(),
            'roles' => $company->roles()
                ->orderByDesc('is_system')
                ->orderBy('name')
                ->get(),
            'modules' => RumikaPermissions::modules(),
            'actionLabels' => RumikaPermissions::actionLabels(),
        ]);
    }

    private function company(): Company
    {
        return Auth::user()
            ->companies()
            ->with('branches')
            ->firstOrFail();
    }

    private function ensureSystemRoles(Company $company): void
    {
        collect(RumikaPermissions::defaults())->each(fn (array $role) => Role::query()->updateOrCreate(
            ['company_id' => $company->id, 'slug' => $role['slug']],
            [
                ...$role,
                'scope' => 'company',
                'is_system' => true,
            ],
        ));
    }

    private function uniqueRoleSlug(Company $company, string $name): string
    {
        $base = Str::slug($name) ?: 'rol';
        $slug = $base;
        $counter = 2;

        while ($company->roles()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function cleanPermissions(array $permissions): array
    {
        $modules = RumikaPermissions::modules();

        return collect($permissions)
            ->mapWithKeys(function (array $actions, string $moduleKey) use ($modules) {
                if (! array_key_exists($moduleKey, $modules)) {
                    return [];
                }

                return [
                    $moduleKey => array_values(array_intersect(
                        $actions,
                        $modules[$moduleKey]['actions'],
                    )),
                ];
            })
            ->filter()
            ->all();
    }
}
