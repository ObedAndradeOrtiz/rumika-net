<?php

namespace App\Livewire\Settings;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Support\CompanyPlanLimits;
use App\Support\RumikaAccess;
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

    public string $accessTab = 'users';

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

    public array $rolePermissionChecks = [];

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
        $this->rolePermissionChecks = $this->permissionChecksFromPermissions($this->rolePermissions);
    }

    public function setAccessTab(string $tab): void
    {
        if (! in_array($tab, ['users', 'roles'], true)) {
            return;
        }

        if ($tab === 'users') {
            $this->authorizePermission('usuarios');
        }

        if ($tab === 'roles') {
            $this->authorizePermission('roles');
        }

        $this->accessTab = $tab;
    }

    public function createUser(): void
    {
        $this->authorizePermission('usuarios', 'create');
        $this->resetUserForm();
        $this->showUserModal = true;
    }

    public function saveUser(): void
    {
        $this->authorizePermission('usuarios', $this->editingUserId ? 'edit' : 'create');
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

        if (! $this->editingUserId) {
            CompanyPlanLimits::assertCanCreate($company, 'users', 'usuarios');
        }

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
        $this->authorizePermission('usuarios', 'edit');
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
        $this->authorizePermission('usuarios', 'delete');
        $this->resetErrorBag();
        $this->confirmingUserDeleteId = $userId;
    }

    public function cancelDeleteUser(): void
    {
        $this->confirmingUserDeleteId = null;
    }

    public function deleteUser(int $userId): void
    {
        $this->authorizePermission('usuarios', 'delete');
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
        $this->authorizePermission('roles', 'create');
        $this->resetRoleForm();
        $this->showRoleModal = true;
    }

    public function saveRole(): void
    {
        $this->authorizePermission('roles', $this->editingRoleId ? 'edit' : 'create');
        $company = $this->company();

        $validated = $this->validate([
            'roleName' => ['required', 'string', 'max:90'],
            'roleDescription' => ['nullable', 'string', 'max:180'],
            'rolePermissionChecks' => ['array'],
        ]);

        $this->rolePermissions = $this->permissionsFromChecks($validated['rolePermissionChecks'] ?? []);

        $role = $this->editingRoleId
            ? $company->roles()->whereKey($this->editingRoleId)->firstOrFail()
            : new Role(['company_id' => $company->id]);

        $role->fill([
            'name' => $validated['roleName'],
            'slug' => $role->exists ? $role->slug : $this->uniqueRoleSlug($company, $validated['roleName']),
            'scope' => 'company',
            'description' => $validated['roleDescription'] ?: null,
            'permissions' => $this->rolePermissions,
            'is_system' => $role->exists ? $role->is_system : false,
        ]);

        $role->save();

        $this->resetRoleForm();
        $this->showRoleModal = false;
        $this->dispatch('role-saved');
    }

    public function editRole(int $roleId): void
    {
        $this->authorizePermission('roles', 'edit');
        $role = $this->company()->roles()->whereKey($roleId)->firstOrFail();

        $this->editingRoleId = $role->id;
        $this->roleName = $role->name;
        $this->roleDescription = $role->description ?? '';
        $this->rolePermissions = $role->permissions ?? [];
        $this->rolePermissionChecks = $this->permissionChecksFromPermissions($this->rolePermissions);
        $this->editingSystemRole = $role->is_system;
        $this->showRoleModal = true;
    }

    public function confirmDeleteRole(int $roleId): void
    {
        $this->authorizePermission('roles', 'delete');
        $this->resetErrorBag();
        $this->confirmingRoleDeleteId = $roleId;
    }

    public function cancelDeleteRole(): void
    {
        $this->confirmingRoleDeleteId = null;
    }

    public function deleteRole(int $roleId): void
    {
        $this->authorizePermission('roles', 'delete');
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
        $this->rolePermissionChecks = $this->permissionChecksFromPermissions($this->rolePermissions);
        $this->resetErrorBag();
    }

    public function render()
    {
        $company = $this->company();
        $canViewUsers = $this->can('usuarios');
        $canViewRoles = $this->can('roles');

        if ($this->accessTab === 'users' && ! $canViewUsers && $canViewRoles) {
            $this->accessTab = 'roles';
        }

        if ($this->accessTab === 'roles' && ! $canViewRoles && $canViewUsers) {
            $this->accessTab = 'users';
        }

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
            'canViewUsers' => $canViewUsers,
            'canCreateUsers' => $this->can('usuarios', 'create'),
            'canEditUsers' => $this->can('usuarios', 'edit'),
            'canDeleteUsers' => $this->can('usuarios', 'delete'),
            'canViewRoles' => $canViewRoles,
            'canCreateRoles' => $this->can('roles', 'create'),
            'canEditRoles' => $this->can('roles', 'edit'),
            'canDeleteRoles' => $this->can('roles', 'delete'),
        ]);
    }

    public function can(string $module, string $action = 'view'): bool
    {
        return RumikaAccess::can(Auth::user(), $module, $action, company: $this->company());
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
        collect(RumikaPermissions::defaults())->each(function (array $role) use ($company) {
            $existingRole = Role::query()
                ->where('company_id', $company->id)
                ->where('slug', $role['slug'])
                ->first();

            if (! $existingRole) {
                Role::query()->create([
                    ...$role,
                    'company_id' => $company->id,
                    'scope' => 'company',
                    'is_system' => true,
                ]);

                return;
            }

            $existingRole->update([
                'name' => $existingRole->name ?: $role['name'],
                'scope' => 'company',
                'is_system' => true,
                'permissions' => array_replace($role['permissions'], $existingRole->permissions ?? []),
            ]);
        });
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
            ->mapWithKeys(function (mixed $actions, string $moduleKey) use ($modules) {
                if (! array_key_exists($moduleKey, $modules)) {
                    return [];
                }

                $actions = match (true) {
                    is_array($actions) => $actions,
                    is_string($actions) => [$actions],
                    default => [],
                };

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

    private function permissionChecksFromPermissions(array $permissions): array
    {
        $cleanPermissions = $this->cleanPermissions($permissions);

        return collect(RumikaPermissions::modules())
            ->mapWithKeys(fn (array $module, string $moduleKey) => [
                $moduleKey => collect($module['actions'])
                    ->mapWithKeys(fn (string $action) => [
                        $action => in_array($action, $cleanPermissions[$moduleKey] ?? [], true),
                    ])
                    ->all(),
            ])
            ->all();
    }

    private function permissionsFromChecks(array $checks): array
    {
        $modules = RumikaPermissions::modules();

        return collect($modules)
            ->mapWithKeys(function (array $module, string $moduleKey) use ($checks) {
                $moduleChecks = $checks[$moduleKey] ?? [];

                if (! is_array($moduleChecks)) {
                    return [];
                }

                $actions = collect($module['actions'])
                    ->filter(fn (string $action) => filter_var($moduleChecks[$action] ?? false, FILTER_VALIDATE_BOOLEAN))
                    ->values()
                    ->all();

                return $actions ? [$moduleKey => $actions] : [];
            })
            ->all();
    }

    private function authorizePermission(string $module, string $action = 'view'): void
    {
        abort_unless($this->can($module, $action), 403);
    }
}
