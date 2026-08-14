<?php

namespace App\Support;

class RumikaPermissions
{
    public static function modules(): array
    {
        return [
            'inicio' => [
                'label' => 'Inicio',
                'group' => 'General',
                'actions' => ['view'],
            ],
            'agenda' => [
                'label' => 'Agenda',
                'group' => 'Gestion de clientes',
                'actions' => ['view', 'create', 'edit', 'delete'],
            ],
            'clientes' => [
                'label' => 'Clientes',
                'group' => 'Gestion de clientes',
                'actions' => ['view', 'create', 'edit', 'delete'],
            ],
            'inventario' => [
                'label' => 'Inventario',
                'group' => 'Gestion de inventario',
                'actions' => ['view', 'create', 'edit', 'delete'],
            ],
            'gastos' => [
                'label' => 'Gastos',
                'group' => 'Gestion financiera',
                'actions' => ['view', 'create', 'edit', 'delete'],
            ],
            'resumen_financiero' => [
                'label' => 'Resumen financiero',
                'group' => 'Gestion financiera',
                'actions' => ['view'],
            ],
            'sucursales' => [
                'label' => 'Sucursales',
                'group' => 'Gestion administrativa',
                'actions' => ['view', 'create', 'edit', 'delete'],
            ],
            'servicios' => [
                'label' => 'Servicios',
                'group' => 'Gestion administrativa',
                'actions' => ['view', 'create', 'edit', 'delete'],
            ],
            'usuarios' => [
                'label' => 'Usuarios',
                'group' => 'Gestion administrativa',
                'actions' => ['view', 'create', 'edit', 'delete'],
            ],
            'roles' => [
                'label' => 'Roles',
                'group' => 'Gestion administrativa',
                'actions' => ['view', 'create', 'edit', 'delete'],
            ],
        ];
    }

    public static function actionLabels(): array
    {
        return [
            'view' => 'Ver',
            'create' => 'Crear',
            'edit' => 'Editar',
            'delete' => 'Eliminar',
        ];
    }

    public static function all(): array
    {
        return collect(self::modules())
            ->mapWithKeys(fn (array $module, string $key) => [
                $key => $module['actions'],
            ])
            ->all();
    }

    public static function onlyView(array $extraModules = []): array
    {
        return collect(self::modules())
            ->mapWithKeys(fn (array $module, string $key) => [
                $key => in_array($key, $extraModules, true)
                    ? $module['actions']
                    : ($key === 'resumen_financiero' ? [] : ['view']),
            ])
            ->all();
    }

    public static function defaults(): array
    {
        return [
            [
                'name' => 'Administrador',
                'slug' => 'administrador',
                'description' => 'Control total del comercio, usuarios, roles y configuracion.',
                'permissions' => self::all(),
            ],
            [
                'name' => 'Gerente',
                'slug' => 'gerente',
                'description' => 'Gestion operativa con acceso amplio, sin eliminar configuraciones criticas.',
                'permissions' => self::withoutDelete(['sucursales', 'usuarios', 'roles']),
            ],
            [
                'name' => 'Recepcion',
                'slug' => 'recepcion',
                'description' => 'Agenda, clientes y atencion diaria.',
                'permissions' => self::onlyView(['agenda', 'clientes']),
            ],
            [
                'name' => 'Profesional',
                'slug' => 'profesional',
                'description' => 'Agenda propia y lectura de clientes permitida.',
                'permissions' => [
                    'inicio' => ['view'],
                    'agenda' => ['view', 'edit'],
                    'clientes' => ['view'],
                ],
            ],
            [
                'name' => 'Caja',
                'slug' => 'caja',
                'description' => 'Rol base para cobros y lectura de clientes. Pagos se activara luego.',
                'permissions' => [
                    'inicio' => ['view'],
                    'clientes' => ['view'],
                ],
            ],
        ];
    }

    private static function withoutDelete(array $modules): array
    {
        $permissions = self::all();

        foreach ($modules as $module) {
            $permissions[$module] = array_values(array_filter(
                $permissions[$module] ?? [],
                fn (string $action) => $action !== 'delete',
            ));
        }

        return $permissions;
    }
}
