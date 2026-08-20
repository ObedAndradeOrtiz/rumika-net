<?php

namespace App\Support;

use App\Models\Company;
use App\Models\User;

class RumikaAccess
{
    public const ADMIN_ROLES = [
        'owner',
        'super_admin',
        'super-administrador',
        'admin',
        'administrator',
        'administrador',
    ];

    public static function can(User $user, string $module, string $action = 'view', ?int $branchId = null, ?Company $company = null): bool
    {
        $company ??= $user->companies()->first();

        if (! $company) {
            return false;
        }

        $companyRole = $user->companies()
            ->where('companies.id', $company->id)
            ->value('company_user.role');

        if (in_array($companyRole, self::ADMIN_ROLES, true)) {
            return true;
        }

        $branchId ??= session('active_branch_id');
        $roleQuery = $user->branches()
            ->where('branches.company_id', $company->id)
            ->leftJoin('roles', 'roles.id', '=', 'branch_user.role_id')
            ->select(['roles.slug', 'roles.permissions']);

        if ($branchId) {
            $roleQuery->where('branches.id', $branchId);
        }

        return $roleQuery
            ->get()
            ->contains(fn ($role) => self::roleCan($role->slug, $role->permissions, $module, $action));
    }

    public static function roleCan(?string $slug, mixed $permissions, string $module, string $action = 'view'): bool
    {
        if (in_array($slug, self::ADMIN_ROLES, true)) {
            return true;
        }

        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true);
        }

        if (! is_array($permissions)) {
            return false;
        }

        $modulePermissions = $permissions[$module] ?? [];

        if (is_string($modulePermissions)) {
            $modulePermissions = [$modulePermissions];
        }

        return is_array($modulePermissions)
            && (in_array($action, $modulePermissions, true) || in_array('*', $modulePermissions, true));
    }

    public static function routes(): array
    {
        return [
            'dashboard' => 'inicio',
            'clinic.agenda' => 'agenda',
            'clinic.clients' => 'clientes',
            'clinic.cashbox' => 'caja',
            'inventory.index' => 'inventario',
            'inventory.operations' => 'inventario_operaciones',
            'finance.expenses' => 'gastos',
            'finance.summary' => 'resumen_financiero',
            'statistics.index' => 'estadisticas',
            'settings.commerce' => 'sucursales',
            'settings.services' => 'servicios',
            'settings.records' => 'registros',
            'settings.users' => 'usuarios',
        ];
    }

    public static function moduleForRoute(string $routeName): ?string
    {
        return self::routes()[$routeName] ?? null;
    }
}
