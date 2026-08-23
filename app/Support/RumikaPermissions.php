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
            'historia_clinica' => [
                'label' => 'Historia clinica',
                'group' => 'Gestion de clientes',
                'actions' => ['view', 'create', 'edit', 'delete', 'view_full', 'manage_access'],
            ],
            'inventario' => [
                'label' => 'Productos y activos',
                'group' => 'Gestion de inventario',
                'actions' => ['view', 'create', 'edit', 'delete'],
            ],
            'inventario_operaciones' => [
                'label' => 'Operaciones',
                'group' => 'Gestion de inventario',
                'actions' => ['view', 'create', 'edit', 'delete'],
            ],
            'caja' => [
                'label' => 'Caja',
                'group' => 'Gestion financiera',
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
            'estadisticas' => [
                'label' => 'Estadisticas',
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
            'registros' => [
                'label' => 'Registros',
                'group' => 'Gestion administrativa',
                'actions' => ['view', 'edit', 'delete'],
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
            'view_full' => 'Ver historial completo',
            'manage_access' => 'Autorizar doctores',
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

    public static function onlyView(array $modules = []): array
    {
        $modules = array_values(array_unique(['inicio', ...$modules]));

        return collect($modules)
            ->filter(fn (string $key) => array_key_exists($key, self::modules()))
            ->mapWithKeys(fn (string $key) => [$key => ['view']])
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
                'permissions' => [
                    'inicio' => ['view'],
                    'agenda' => ['view', 'create', 'edit'],
                    'clientes' => ['view', 'create', 'edit'],
                    'historia_clinica' => ['view', 'create'],
                ],
            ],
            [
                'name' => 'Profesional',
                'slug' => 'profesional',
                'description' => 'Agenda propia y lectura de clientes permitida.',
                'permissions' => [
                    'inicio' => ['view'],
                    'agenda' => ['view', 'edit'],
                    'clientes' => ['view'],
                    'historia_clinica' => ['view', 'create'],
                ],
            ],
            [
                'name' => 'Doctor',
                'slug' => 'doctor',
                'description' => 'Atiende citas asignadas y registra historia clinica solo de sus pacientes.',
                'permissions' => [
                    'inicio' => ['view'],
                    'agenda' => ['view', 'edit'],
                    'historia_clinica' => ['view', 'create'],
                ],
            ],
            [
                'name' => 'Caja',
                'slug' => 'caja',
                'description' => 'Rol base para cobros y lectura de clientes. Pagos se activara luego.',
                'permissions' => [
                    'inicio' => ['view'],
                    'clientes' => ['view'],
                    'caja' => ['view', 'create', 'edit'],
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
