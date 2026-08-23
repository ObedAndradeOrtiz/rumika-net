<?php

use App\Support\CompanyPlanCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('roles')
            ->whereIn('slug', ['administrador', 'gerente'])
            ->orderBy('id')
            ->each(function (object $role) {
                $permissions = json_decode((string) $role->permissions, true);

                if (! is_array($permissions)) {
                    $permissions = [];
                }

                $permissions['historia_clinica'] = $role->slug === 'administrador'
                    ? ['view', 'create', 'edit', 'delete', 'view_full', 'manage_access']
                    : ['view', 'create', 'edit', 'view_full'];

                DB::table('roles')
                    ->where('id', $role->id)
                    ->update(['permissions' => json_encode($permissions)]);
            });

        DB::table('roles')
            ->whereIn('slug', ['recepcion', 'profesional'])
            ->orderBy('id')
            ->each(function (object $role) {
                $permissions = json_decode((string) $role->permissions, true);

                if (! is_array($permissions)) {
                    $permissions = [];
                }

                $permissions['historia_clinica'] = ['view', 'create'];

                DB::table('roles')
                    ->where('id', $role->id)
                    ->update(['permissions' => json_encode($permissions)]);
            });

        foreach (CompanyPlanCatalog::plans() as $plan) {
            DB::table('company_plans')
                ->where('slug', $plan['slug'])
                ->update(['features' => json_encode($plan['features'])]);
        }
    }

    public function down(): void
    {
        DB::table('roles')->orderBy('id')->each(function (object $role) {
            $permissions = json_decode((string) $role->permissions, true);

            if (! is_array($permissions)) {
                return;
            }

            unset($permissions['historia_clinica']);

            DB::table('roles')
                ->where('id', $role->id)
                ->update(['permissions' => json_encode($permissions)]);
        });
    }
};
