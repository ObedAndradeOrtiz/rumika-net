<?php

namespace Database\Seeders;

use App\Models\BusinessType;
use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanyPlan;
use App\Models\User;
use App\Support\CompanyPlanCatalog;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        foreach (CompanyPlanCatalog::plans() as $plan) {
            CompanyPlan::query()->updateOrCreate(
                ['slug' => $plan['slug']],
                [...$plan, 'is_active' => true],
            );
        }

        User::query()->updateOrCreate(
            ['email' => 'saas@rumika.app'],
            [
                'name' => 'Administrador Rumika SaaS',
                'password' => 'Rumika2026!',
                'email_verified_at' => now(),
                'status' => 'active',
                'is_saas_admin' => true,
            ],
        );

        $businessTypes = [
            [
                'name' => 'Clinica',
                'slug' => 'clinica',
                'description' => 'Consultas, tratamientos e historial clinico.',
                'enabled_modules' => ['agenda', 'clientes', 'historial', 'inventario'],
            ],
            [
                'name' => 'Spa',
                'slug' => 'spa',
                'description' => 'Reservas, paquetes, sesiones y tratamientos.',
                'enabled_modules' => ['agenda', 'clientes', 'historial', 'inventario'],
            ],
            [
                'name' => 'Centro de belleza',
                'slug' => 'centro-belleza',
                'description' => 'Servicios, estilistas, agenda e historial de atencion.',
                'enabled_modules' => ['agenda', 'clientes', 'historial', 'inventario'],
            ],
            [
                'name' => 'Barberia',
                'slug' => 'barberia',
                'description' => 'Cortes, barberos, clientes y citas rapidas.',
                'enabled_modules' => ['agenda', 'clientes', 'historial', 'inventario'],
            ],
            [
                'name' => 'Dentista',
                'slug' => 'dentista',
                'description' => 'Pacientes, citas e historial odontologico.',
                'enabled_modules' => ['agenda', 'clientes', 'historial', 'inventario'],
            ],
            [
                'name' => 'Farmacia',
                'slug' => 'farmacia',
                'description' => 'Venta directa, compradores por NIT, inventario por lotes y vencimientos.',
                'enabled_modules' => ['inicio', 'clientes', 'ventas_productos', 'inventario', 'inventario_operaciones', 'caja', 'crm', 'facturacion', 'deudas', 'reportes', 'gastos', 'resumen_financiero', 'estadisticas', 'sucursales', 'usuarios', 'roles', 'registros', 'bitacora'],
            ],
            [
                'name' => 'Tienda',
                'slug' => 'tienda',
                'description' => 'Venta comercial, compradores, inventario, caja y facturacion.',
                'enabled_modules' => ['inicio', 'clientes', 'ventas_productos', 'inventario', 'inventario_operaciones', 'caja', 'crm', 'facturacion', 'deudas', 'reportes', 'gastos', 'resumen_financiero', 'estadisticas', 'sucursales', 'usuarios', 'roles', 'registros', 'bitacora'],
            ],
            [
                'name' => 'Perfumeria',
                'slug' => 'perfumeria',
                'description' => 'Venta directa de perfumes por frasco o por ml, compradores por NIT e inventario por sucursal.',
                'enabled_modules' => ['inicio', 'clientes', 'ventas_productos', 'inventario', 'inventario_operaciones', 'caja', 'crm', 'facturacion', 'deudas', 'reportes', 'gastos', 'resumen_financiero', 'estadisticas', 'sucursales', 'usuarios', 'roles', 'registros', 'bitacora'],
            ],
        ];

        foreach ($businessTypes as $businessType) {
            BusinessType::query()->updateOrCreate(
                ['slug' => $businessType['slug']],
                $businessType,
            );
        }

        $externalUser = User::query()->updateOrCreate(
            ['email' => 'externo@rumika.test'],
            [
                'name' => 'Usuario Externo',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        $externalCompany = Company::query()->updateOrCreate(
            ['slug' => 'negocio-externo'],
            [
                'name' => 'Negocio Externo',
                'status' => 'trial',
                'company_plan_id' => CompanyPlan::query()->where('slug', 'free')->value('id'),
            ],
        );

        $externalBranch = Branch::query()->updateOrCreate(
            ['company_id' => $externalCompany->id, 'slug' => 'sucursal-externa'],
            [
                'business_type_id' => BusinessType::query()->where('slug', 'barberia')->value('id'),
                'name' => 'Sucursal Externa',
                'status' => 'active',
            ],
        );

        $externalCompany->users()->syncWithoutDetaching([
            $externalUser->id => [
                'role' => 'owner',
                'joined_at' => now(),
            ],
        ]);

        $externalBranch->users()->syncWithoutDetaching([
            $externalUser->id => [
                'assigned_at' => now(),
            ],
        ]);

        $this->call(RumikaDemoCatalogSeeder::class);
    }
}
