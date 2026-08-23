<?php

use App\Models\CompanyPlan;
use App\Models\Role;
use App\Support\CompanyPlanCatalog;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        foreach (CompanyPlanCatalog::plans() as $plan) {
            CompanyPlan::query()->updateOrCreate(
                ['slug' => $plan['slug']],
                [
                    'name' => $plan['name'],
                    'description' => $plan['description'],
                    'monthly_price' => $plan['monthly_price'],
                    'currency' => $plan['currency'],
                    'features' => $plan['features'],
                    'sort_order' => $plan['sort_order'],
                    'is_active' => true,
                ],
            );
        }

        Role::query()->chunkById(100, function ($roles) {
            foreach ($roles as $role) {
                $permissions = $role->permissions ?: [];

                if (in_array($role->slug, ['administrador', 'admin', 'owner', 'super_admin', 'super-administrador'], true)) {
                    $permissions['crm'] = ['view', 'create', 'edit', 'delete'];
                }

                if ($role->slug === 'gerente') {
                    $permissions['crm'] = ['view', 'create', 'edit'];
                }

                if ($role->slug === 'recepcion') {
                    $permissions['crm'] = ['view', 'create', 'edit'];
                }

                $role->update(['permissions' => $permissions]);
            }
        });
    }

    public function down(): void
    {
        Role::query()->chunkById(100, function ($roles) {
            foreach ($roles as $role) {
                $permissions = $role->permissions ?: [];
                unset($permissions['crm']);
                $role->update(['permissions' => $permissions]);
            }
        });
    }
};
