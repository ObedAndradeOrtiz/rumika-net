<?php

use App\Support\CompanyPlanCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach ([
            [
                'name' => 'Farmacia',
                'slug' => 'farmacia',
                'description' => 'Venta directa, compradores por NIT, inventario por lotes y vencimientos.',
                'enabled_modules' => json_encode(['clientes', 'ventas_productos', 'inventario', 'caja', 'facturacion']),
            ],
            [
                'name' => 'Tienda',
                'slug' => 'tienda',
                'description' => 'Venta comercial, compradores, inventario, caja y facturacion.',
                'enabled_modules' => json_encode(['clientes', 'ventas_productos', 'inventario', 'caja', 'facturacion']),
            ],
        ] as $type) {
            DB::table('business_types')->updateOrInsert(
                ['slug' => $type['slug']],
                [
                    ...$type,
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }

        foreach (CompanyPlanCatalog::plans() as $plan) {
            DB::table('company_plans')
                ->where('slug', $plan['slug'])
                ->update(['features' => json_encode($plan['features'])]);
        }

        DB::table('roles')
            ->whereIn('slug', ['administrador', 'gerente'])
            ->orderBy('id')
            ->each(function (object $role) {
                $permissions = json_decode((string) $role->permissions, true);

                if (! is_array($permissions)) {
                    $permissions = [];
                }

                $permissions['facturacion'] = $role->slug === 'administrador'
                    ? ['view', 'edit']
                    : ['view'];

                DB::table('roles')
                    ->where('id', $role->id)
                    ->update(['permissions' => json_encode($permissions)]);
            });
    }

    public function down(): void
    {
        DB::table('business_types')->whereIn('slug', ['farmacia', 'tienda'])->delete();
    }
};
