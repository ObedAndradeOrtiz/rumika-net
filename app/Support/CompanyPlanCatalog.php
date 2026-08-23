<?php

namespace App\Support;

class CompanyPlanCatalog
{
    public static function plans(): array
    {
        return [
            [
                'name' => 'Free',
                'slug' => 'free',
                'description' => 'Demo completo durante 3 dias. Luego requiere activacion desde Rumika SaaS.',
                'monthly_price' => 0,
                'currency' => 'USD',
                'sort_order' => 1,
                'features' => [
                    'trial_days' => 3,
                    'blocked_after_trial' => true,
                    'modules' => ['*'],
                    'limits' => [
                        'branches' => 1,
                        'users' => 2,
                        'clients' => 50,
                        'products' => 50,
                        'appointments_per_month' => 100,
                    ],
                    'notes' => ['Demo completo por 3 dias', 'Despues del demo queda bloqueado hasta activacion'],
                ],
            ],
            [
                'name' => 'Basico',
                'slug' => 'basico',
                'description' => 'Para negocios pequenos que necesitan agenda, clientes, servicios y caja simple.',
                'monthly_price' => 30,
                'currency' => 'USD',
                'sort_order' => 2,
                'features' => [
                    'modules' => ['inicio', 'agenda', 'clientes', 'historia_clinica', 'servicios', 'caja', 'facturacion', 'deudas', 'reportes', 'sucursales'],
                    'limits' => [
                        'branches' => 1,
                        'users' => 3,
                        'clients' => 1000,
                        'products' => 0,
                        'appointments_per_month' => 600,
                    ],
                    'notes' => ['Sin inventario avanzado', 'Sin resumen financiero global', 'Sin registros administrativos avanzados'],
                ],
            ],
            [
                'name' => 'Plus',
                'slug' => 'plus',
                'description' => 'Para equipos con varias sucursales, inventario, caja, gastos y estadisticas.',
                'monthly_price' => 60,
                'currency' => 'USD',
                'sort_order' => 3,
                'features' => [
                    'modules' => ['inicio', 'agenda', 'clientes', 'historia_clinica', 'servicios', 'caja', 'facturacion', 'deudas', 'reportes', 'comisiones', 'sucursales', 'usuarios', 'roles', 'inventario', 'inventario_operaciones', 'gastos', 'estadisticas', 'crm'],
                    'limits' => [
                        'branches' => 3,
                        'users' => 10,
                        'clients' => 5000,
                        'products' => 1000,
                        'appointments_per_month' => 3000,
                    ],
                    'notes' => ['Inventario y operaciones incluidos', 'Gastos y estadisticas incluidos', 'Sin registros ni resumen financiero multiempresa avanzado'],
                ],
            ],
            [
                'name' => 'Empresa',
                'slug' => 'empresa',
                'description' => 'Plan completo sin limites para clinicas, centros y grupos con operacion avanzada.',
                'monthly_price' => 90,
                'currency' => 'USD',
                'sort_order' => 4,
                'features' => [
                    'modules' => ['*'],
                    'limits' => [
                        'branches' => null,
                        'users' => null,
                        'clients' => null,
                        'products' => null,
                        'appointments_per_month' => null,
                    ],
                    'notes' => ['Sin limites', 'Todos los modulos', 'Soporte para control completo por sucursales'],
                ],
            ],
        ];
    }

    public static function forSlug(?string $slug): array
    {
        return collect(self::plans())->firstWhere('slug', $slug) ?? self::plans()[0];
    }
}
