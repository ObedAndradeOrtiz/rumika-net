<?php

namespace Database\Seeders;

use App\Models\BusinessType;
use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanyPlan;
use App\Models\User;
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
        $plans = [
            ['name' => 'Free', 'slug' => 'free', 'description' => 'Plan inicial para probar Rumika.', 'monthly_price' => 0, 'currency' => 'USD', 'sort_order' => 1],
            ['name' => 'Basico', 'slug' => 'basico', 'description' => 'Operaciones esenciales para negocios pequenos.', 'monthly_price' => 30, 'currency' => 'USD', 'sort_order' => 2],
            ['name' => 'Plus', 'slug' => 'plus', 'description' => 'Gestion completa para equipos en crecimiento.', 'monthly_price' => 60, 'currency' => 'USD', 'sort_order' => 3],
            ['name' => 'Empresa', 'slug' => 'empresa', 'description' => 'Control avanzado para varias sucursales.', 'monthly_price' => 90, 'currency' => 'USD', 'sort_order' => 4],
        ];

        foreach ($plans as $plan) {
            CompanyPlan::query()->updateOrCreate(
                ['slug' => $plan['slug']],
                [...$plan, 'is_active' => true],
            );
        }

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
