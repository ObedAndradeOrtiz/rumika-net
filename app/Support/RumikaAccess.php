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

        if (! self::planAllows($company, $module)) {
            return false;
        }

        if (! self::businessTypeAllows($user, $company, $module, $branchId)) {
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

    public static function planAllows(Company $company, string $module): bool
    {
        $features = $company->plan?->features ?? CompanyPlanCatalog::forSlug('free')['features'];

        if (is_string($features)) {
            $features = json_decode($features, true);
        }

        $modules = is_array($features) ? ($features['modules'] ?? []) : [];

        return in_array('*', $modules, true) || in_array($module, $modules, true);
    }

    public static function businessTypeAllows(User $user, Company $company, string $module, ?int $branchId = null): bool
    {
        $alwaysAllowed = ['inicio', 'sucursales', 'usuarios', 'roles', 'registros', 'bitacora', 'recursos_humanos'];

        if (in_array($module, $alwaysAllowed, true)) {
            return true;
        }

        $branchId ??= session('active_branch_id');
        $branchQuery = $user->branches()
            ->with('businessType')
            ->where('branches.company_id', $company->id);

        if ($branchId) {
            $branchQuery->where('branches.id', $branchId);
        }

        $branch = $branchQuery->first()
            ?: $user->branches()->with('businessType')->where('branches.company_id', $company->id)->first();

        $businessType = $branch?->businessType;

        if (! in_array($businessType?->slug, ['farmacia', 'tienda', 'perfumeria'], true)) {
            return true;
        }

        $modules = $businessType?->enabled_modules;

        if (is_string($modules)) {
            $modules = json_decode($modules, true);
        }

        if (! is_array($modules) || $modules === []) {
            return true;
        }

        return in_array('*', $modules, true) || in_array($module, $modules, true);
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
            'clinic.clinical-history' => 'historia_clinica',
            'clinic.cashbox' => 'caja',
            'sales.products' => 'ventas_productos',
            'crm.index' => 'crm',
            'finance.invoicing' => 'facturacion',
            'finance.debts' => 'deudas',
            'finance.reports' => 'reportes',
            'finance.reports.pdf' => 'reportes',
            'finance.commissions' => 'comisiones',
            'inventory.index' => 'inventario',
            'inventory.operations' => 'inventario_operaciones',
            'finance.expenses' => 'gastos',
            'finance.summary' => 'resumen_financiero',
            'statistics.index' => 'estadisticas',
            'settings.booking' => 'agenda',
            'settings.commerce' => 'sucursales',
            'settings.services' => 'servicios',
            'settings.records' => 'registros',
            'settings.audit' => 'bitacora',
            'settings.users' => 'usuarios',
            'hr.attendance' => 'recursos_humanos',
        ];
    }

    public static function moduleForRoute(string $routeName): ?string
    {
        return self::routes()[$routeName] ?? null;
    }
}
