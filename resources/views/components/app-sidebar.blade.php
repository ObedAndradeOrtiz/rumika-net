@props(['active' => 'home'])


@php
    $sidebarNavUser = Auth::user();
    $sidebarNavCompany = $sidebarNavUser?->companies()->first();
    $canAccess = fn (string $module, string $action = 'view') => $sidebarNavUser
        ? \App\Support\RumikaAccess::can($sidebarNavUser, $module, $action, company: $sidebarNavCompany)
        : false;
    $sidebarCanSeeHome = $canAccess('inicio');
    $sidebarCanSeeAgenda = $canAccess('agenda');
    $sidebarCanSeeClients = $canAccess('clientes');
    $sidebarCanSeeClinicalHistory = $canAccess('historia_clinica');
    $sidebarCanSeeInventory = $canAccess('inventario');
    $sidebarCanSeeInventoryOperations = $canAccess('inventario_operaciones');
    $sidebarCanSeeCashbox = $canAccess('caja');
    $sidebarCanSeeProductSales = $canAccess('ventas_productos');
    $sidebarCanSeeInvoicing = $canAccess('facturacion');
    $sidebarCanSeeExpenses = $canAccess('gastos');
    $sidebarCanSeeFinancialSummary = $canAccess('resumen_financiero');
    $sidebarCanSeeStatistics = $canAccess('estadisticas');
    $sidebarCanSeeCommerce = $canAccess('sucursales');
    $sidebarCanSeeServices = $canAccess('servicios');
    $sidebarCanSeeRecords = $canAccess('registros');
    $sidebarCanSeeAudit = $canAccess('bitacora');
    $sidebarCanSeeUsers = $canAccess('usuarios');
    $sidebarCanSeeRoles = $canAccess('roles');
@endphp

<aside class="rm-side">
    <div class="rm-side-brand">
        <span class="rm-brand-mark">
            <x-application-logo class="h-7 w-7 text-white" />
        </span>
        <strong data-sidebar-label>Rumika</strong>
        <button class="rm-side-toggle" type="button" data-sidebar-toggle aria-label="Contraer menu" aria-expanded="true">
            <svg class="rm-side-toggle-icon" width="18" height="18" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2.4">
                <path d="m15 18-6-6 6-6" />
            </svg>
        </button>
    </div>

    <nav class="rm-side-nav" aria-label="Modulos">
        @if ($sidebarCanSeeHome)
        <a class="rm-side-link {{ $active === 'home' ? 'is-active' : '' }}" href="{{ route('dashboard') }}">
            <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2.2">
                <path d="M3 10.5 12 3l9 7.5" />
                <path d="M5 9.5V21h14V9.5" />
                <path d="M9 21v-6h6v6" />
            </svg>
            <span data-sidebar-label>Inicio</span>
        </a>
        @endif

        @if ($sidebarCanSeeAgenda || $sidebarCanSeeClients || $sidebarCanSeeClinicalHistory || $sidebarCanSeeProductSales)
        <details class="rm-menu-group" {{ in_array($active, ['agenda', 'clients', 'clinical-history', 'product-sales'], true) ? 'open' : '' }}>
            <summary>
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2.2">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2" />
                    <circle cx="9.5" cy="7" r="4" />
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                </svg>
                <span data-sidebar-label>Gestion de clientes</span>
                <svg class="rm-menu-chevron" data-sidebar-label width="16" height="16" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2.4">
                    <path d="m6 9 6 6 6-6" />
                </svg>
            </summary>
            <div class="rm-menu-list">
                @if ($sidebarCanSeeAgenda)
                <a class="rm-side-link rm-side-sub-link {{ $active === 'agenda' ? 'is-active' : '' }}"
                    href="{{ route('clinic.agenda') }}">
                    <i aria-hidden="true"></i>
                    <span data-sidebar-label>Agenda</span>
                </a>
                @endif
                @if ($sidebarCanSeeClients)
                <a class="rm-side-link rm-side-sub-link {{ $active === 'clients' ? 'is-active' : '' }}"
                    href="{{ route('clinic.clients') }}">
                    <i aria-hidden="true"></i>
                    <span data-sidebar-label>Clientes</span>
                </a>
                @endif
                @if ($sidebarCanSeeProductSales)
                <a class="rm-side-link rm-side-sub-link {{ $active === 'product-sales' ? 'is-active' : '' }}"
                    href="{{ route('sales.products') }}">
                    <i aria-hidden="true"></i>
                    <span data-sidebar-label>Ventas</span>
                </a>
                @endif
                @if ($sidebarCanSeeClinicalHistory)
                <a class="rm-side-link rm-side-sub-link {{ $active === 'clinical-history' ? 'is-active' : '' }}"
                    href="{{ route('clinic.clinical-history') }}">
                    <i aria-hidden="true"></i>
                    <span data-sidebar-label>Historia clinica</span>
                </a>
                @endif
            </div>
        </details>
        @endif

        @if ($sidebarCanSeeInventory || $sidebarCanSeeInventoryOperations)
        <details class="rm-menu-group"
            {{ in_array($active, ['inventory', 'inventory-operations'], true) ? 'open' : '' }}>
            <summary>
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2.2">
                    <path
                        d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z" />
                    <path d="m3.3 7 8.7 5 8.7-5" />
                    <path d="M12 22V12" />
                </svg>
                <span data-sidebar-label>Gestion de inventario</span>
                <svg class="rm-menu-chevron" data-sidebar-label width="16" height="16" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2.4">
                    <path d="m6 9 6 6 6-6" />
                </svg>
            </summary>
            <div class="rm-menu-list">
                @if ($sidebarCanSeeInventory)
                <a class="rm-side-link rm-side-sub-link {{ $active === 'inventory' ? 'is-active' : '' }}"
                    href="{{ route('inventory.index') }}">
                    <i aria-hidden="true"></i>
                    <span data-sidebar-label>Productos y activos</span>
                </a>
                @endif
                @if ($sidebarCanSeeInventoryOperations)
                <a class="rm-side-link rm-side-sub-link {{ $active === 'inventory-operations' ? 'is-active' : '' }}"
                    href="{{ route('inventory.operations') }}">
                    <i aria-hidden="true"></i>
                    <span data-sidebar-label>Operaciones</span>
                </a>
                @endif
            </div>
        </details>
        @endif

        @if ($sidebarCanSeeCashbox || $sidebarCanSeeInvoicing || $sidebarCanSeeExpenses || $sidebarCanSeeFinancialSummary || $sidebarCanSeeStatistics)
        <details class="rm-menu-group"
            {{ in_array($active, ['expenses', 'cashbox', 'invoicing', 'finance-summary', 'statistics'], true) ? 'open' : '' }}>
            <summary>
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2.2">
                    <path d="M4 4h16v16H4z" />
                    <path d="M8 9h8M8 13h5M8 17h8" />
                    <path d="M16 4v16" />
                </svg>
                <span data-sidebar-label>Gestion financiera</span>
                <svg class="rm-menu-chevron" data-sidebar-label width="16" height="16" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2.4">
                    <path d="m6 9 6 6 6-6" />
                </svg>
            </summary>
            <div class="rm-menu-list">
                @if ($sidebarCanSeeCashbox)
                <a class="rm-side-link rm-side-sub-link {{ $active === 'cashbox' ? 'is-active' : '' }}"
                    href="{{ route('clinic.cashbox') }}">
                    <i aria-hidden="true"></i>
                    <span data-sidebar-label>Caja</span>
                </a>
                @endif
                @if ($sidebarCanSeeExpenses)
                <a class="rm-side-link rm-side-sub-link {{ $active === 'expenses' ? 'is-active' : '' }}"
                    href="{{ route('finance.expenses') }}">
                    <i aria-hidden="true"></i>
                    <span data-sidebar-label>Gastos</span>
                </a>
                @endif
                @if ($sidebarCanSeeInvoicing)
                <a class="rm-side-link rm-side-sub-link {{ $active === 'invoicing' ? 'is-active' : '' }}"
                    href="{{ route('finance.invoicing') }}">
                    <i aria-hidden="true"></i>
                    <span data-sidebar-label>Facturacion</span>
                </a>
                @endif
                @if ($sidebarCanSeeFinancialSummary)
                    <a class="rm-side-link rm-side-sub-link {{ $active === 'finance-summary' ? 'is-active' : '' }}"
                        href="{{ route('finance.summary') }}">
                        <i aria-hidden="true"></i>
                        <span data-sidebar-label>Resumen</span>
                    </a>
                @endif
                @if ($sidebarCanSeeStatistics)
                    <a class="rm-side-link rm-side-sub-link {{ $active === 'statistics' ? 'is-active' : '' }}"
                        href="{{ route('statistics.index') }}">
                        <i aria-hidden="true"></i>
                        <span data-sidebar-label>Estadisticas</span>
                    </a>
                @endif
            </div>
        </details>
        @endif

        @if ($sidebarCanSeeCommerce || $sidebarCanSeeServices || $sidebarCanSeeRecords || $sidebarCanSeeAudit || $sidebarCanSeeUsers || $sidebarCanSeeRoles)
        <details class="rm-menu-group"
            {{ in_array($active, ['settings', 'services', 'users', 'roles', 'records', 'audit'], true) ? 'open' : '' }}>
            <summary>
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2.2">
                    <path d="M3 21h18M5 21V8l7-5 7 5v13M9 21v-6h6v6" />
                </svg>
                <span data-sidebar-label>Gestion administrativa</span>
                <svg class="rm-menu-chevron" data-sidebar-label width="16" height="16" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2.4">
                    <path d="m6 9 6 6 6-6" />
                </svg>
            </summary>
            <div class="rm-menu-list">
                @if ($sidebarCanSeeCommerce)
                <a class="rm-side-link rm-side-sub-link {{ $active === 'settings' ? 'is-active' : '' }}"
                    href="{{ route('settings.commerce') }}">
                    <i aria-hidden="true"></i>
                    <span data-sidebar-label>Sucursales</span>
                </a>
                @endif
                @if ($sidebarCanSeeServices)
                <a class="rm-side-link rm-side-sub-link {{ $active === 'services' ? 'is-active' : '' }}"
                    href="{{ route('settings.services') }}">
                    <i aria-hidden="true"></i>
                    <span data-sidebar-label>Servicios</span>
                </a>
                @endif
                @if ($sidebarCanSeeRecords)
                <a class="rm-side-link rm-side-sub-link {{ $active === 'records' ? 'is-active' : '' }}"
                    href="{{ route('settings.records') }}">
                    <i aria-hidden="true"></i>
                    <span data-sidebar-label>Registros</span>
                </a>
                @endif
                @if ($sidebarCanSeeAudit)
                <a class="rm-side-link rm-side-sub-link {{ $active === 'audit' ? 'is-active' : '' }}"
                    href="{{ route('settings.audit') }}">
                    <i aria-hidden="true"></i>
                    <span data-sidebar-label>Bitacora</span>
                </a>
                @endif
                @if ($sidebarCanSeeUsers || $sidebarCanSeeRoles)
                <a class="rm-side-link rm-side-sub-link {{ $active === 'users' ? 'is-active' : '' }}"
                    href="{{ route('settings.users') }}">
                    <i aria-hidden="true"></i>
                    <span data-sidebar-label>Usuarios y roles</span>
                </a>
                @endif
            </div>
        </details>
        @endif
    </nav>

    @php
        $sidebarUser = Auth::user();
        $sidebarCompany = $sidebarUser?->companies()->first();
        $sidebarBranches = $sidebarCompany
            ? $sidebarUser?->branches()->where('company_id', $sidebarCompany->id)->orderBy('name')->get()
            : collect();
        $sidebarActiveBranch =
            $sidebarBranches->firstWhere('id', session('active_branch_id')) ?? $sidebarBranches->first();
        $sidebarBranchRole = $sidebarActiveBranch
            ? $sidebarUser
                ?->branches()
                ->where('branches.id', $sidebarActiveBranch->id)
                ->leftJoin('roles', 'roles.id', '=', 'branch_user.role_id')
                ->select('roles.name', 'roles.slug')
                ->first()
            : null;
        $sidebarCompanyRole = $sidebarCompany
            ? $sidebarUser->companies()->where('companies.id', $sidebarCompany->id)->value('company_user.role')
            : null;
        $sidebarRole = $sidebarBranchRole?->slug ?: $sidebarCompanyRole;
        $sidebarRoleLabel = match ($sidebarRole) {
            'owner' => 'Administrador principal',
            'super_admin', 'super-administrador' => 'Super administrador',
            'admin', 'administrador' => 'Administrador',
            'member' => 'Usuario',
            'staff' => 'Personal',
            default => $sidebarBranchRole?->name ?:
            ($sidebarRole
                ? \Illuminate\Support\Str::headline($sidebarRole)
                : 'Sin rol'),
        };
        $sidebarInitials = collect(explode(' ', trim($sidebarUser?->name ?? 'Usuario')))
            ->filter()
            ->take(2)
            ->map(fn($part) => strtoupper(substr($part, 0, 1)))
            ->implode('');
    @endphp

    <a class="rm-side-profile" href="{{ route('profile') }}">
        <span class="rm-side-profile-photo">
            @if ($sidebarUser?->profile_photo_path)
                <img src="{{ asset('storage/' . $sidebarUser->profile_photo_path) }}" alt="{{ $sidebarUser->name }}">
            @else
                {{ $sidebarInitials ?: 'U' }}
            @endif
        </span>
        <span class="rm-side-profile-text" data-sidebar-label>
            <strong>{{ $sidebarUser?->name ?? 'Usuario' }}</strong>
            <small>{{ $sidebarRoleLabel }}</small>
        </span>
    </a>

    <form class="rm-side-logout" method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="rm-side-link" type="submit">
            <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2.2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                <path d="m16 17 5-5-5-5" />
                <path d="M21 12H9" />
            </svg>
            <span data-sidebar-label>Cerrar sesion</span>
        </button>
    </form>
</aside>

<nav class="rm-mobile-tabs" aria-label="Navegacion movil">
    @if ($sidebarCanSeeHome)
    <a class="rm-mobile-tab {{ $active === 'home' ? 'is-active' : '' }}" href="{{ route('dashboard') }}"><svg
            width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="2.2">
            <path d="M3 10.5 12 3l9 7.5" />
            <path d="M5 9.5V21h14V9.5" />
        </svg>Inicio</a>
    @endif
    @if ($sidebarCanSeeAgenda)
    <a class="rm-mobile-tab {{ $active === 'agenda' ? 'is-active' : '' }}" href="{{ route('clinic.agenda') }}"><svg
            width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="2.2">
            <rect x="3" y="5" width="18" height="16" rx="3" />
            <path d="M8 3v4M16 3v4M3 10h18" />
        </svg>Agenda</a>
    @endif
    @if ($sidebarCanSeeClients)
    <a class="rm-mobile-tab {{ $active === 'clients' ? 'is-active' : '' }}"
        href="{{ route('clinic.clients') }}"><svg width="22" height="22" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2.2">
            <path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2" />
            <circle cx="9.5" cy="7" r="4" />
        </svg>Clientes</a>
    @endif
    <button class="rm-mobile-tab" type="button" data-mobile-more-toggle aria-expanded="false"><svg width="22"
            height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
            <circle cx="5" cy="12" r="1.5" />
            <circle cx="12" cy="12" r="1.5" />
            <circle cx="19" cy="12" r="1.5" />
        </svg>Mas</button>
</nav>

<div class="rm-mobile-more" data-mobile-more-panel aria-hidden="true">
    <div class="rm-mobile-more-backdrop" data-mobile-more-close></div>
    <div class="rm-mobile-more-sheet">
        <div class="rm-mobile-more-title">
            <strong>Mas opciones</strong>
            <button type="button" data-mobile-more-close aria-label="Cerrar menu">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2.4">
                    <path d="M18 6 6 18M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="rm-mobile-more-scroll">
            @if ($sidebarCanSeeClinicalHistory || $sidebarCanSeeProductSales)
            <details class="rm-mobile-menu-group" {{ in_array($active, ['clinical-history', 'product-sales'], true) ? 'open' : '' }}>
                <summary>
                    <span>Gestion de clientes</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.4">
                        <path d="m6 9 6 6 6-6" />
                    </svg>
                </summary>
                <div>
                    @if ($sidebarCanSeeProductSales)
                    <a href="{{ route('sales.products') }}">Ventas</a>
                    @endif
                    @if ($sidebarCanSeeClinicalHistory)
                    <a href="{{ route('clinic.clinical-history') }}">Historia clinica</a>
                    @endif
                </div>
            </details>
            @endif

            @if ($sidebarCanSeeInventory || $sidebarCanSeeInventoryOperations)
            <details class="rm-mobile-menu-group"
                {{ in_array($active, ['inventory', 'inventory-operations'], true) ? 'open' : '' }}>
                <summary>
                    <span>Gestion de inventario</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.4">
                        <path d="m6 9 6 6 6-6" />
                    </svg>
                </summary>
                <div>
                    @if ($sidebarCanSeeInventory)
                    <a href="{{ route('inventory.index') }}">Productos y activos</a>
                    @endif
                    @if ($sidebarCanSeeInventoryOperations)
                    <a href="{{ route('inventory.operations') }}">Operaciones</a>
                    @endif
                </div>
            </details>
            @endif

            @if ($sidebarCanSeeCashbox || $sidebarCanSeeInvoicing || $sidebarCanSeeExpenses || $sidebarCanSeeFinancialSummary || $sidebarCanSeeStatistics)
            <details class="rm-mobile-menu-group"
                {{ in_array($active, ['expenses', 'cashbox', 'invoicing', 'finance-summary', 'statistics'], true) ? 'open' : '' }}>
                <summary>
                    <span>Gestion financiera</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.4">
                        <path d="m6 9 6 6 6-6" />
                    </svg>
                </summary>
                <div>
                    @if ($sidebarCanSeeCashbox)
                    <a href="{{ route('clinic.cashbox') }}">Caja</a>
                    @endif
                    @if ($sidebarCanSeeExpenses)
                    <a href="{{ route('finance.expenses') }}">Gastos</a>
                    @endif
                    @if ($sidebarCanSeeInvoicing)
                    <a href="{{ route('finance.invoicing') }}">Facturacion</a>
                    @endif
                    @if ($sidebarCanSeeFinancialSummary)
                        <a class="rm-side-link rm-side-sub-link {{ $active === 'finance-summary' ? 'is-active' : '' }}"
                            href="{{ route('finance.summary') }}">
                            <i aria-hidden="true"></i>
                            <span data-sidebar-label>Resumen</span>
                        </a>
                    @endif
                    @if ($sidebarCanSeeStatistics)
                        <a class="rm-side-link rm-side-sub-link {{ $active === 'statistics' ? 'is-active' : '' }}"
                            href="{{ route('statistics.index') }}">
                            <i aria-hidden="true"></i>
                            <span data-sidebar-label>Estadisticas</span>
                        </a>
                    @endif
                </div>
            </details>
            @endif

            @if ($sidebarCanSeeCommerce || $sidebarCanSeeServices || $sidebarCanSeeRecords || $sidebarCanSeeUsers || $sidebarCanSeeRoles)
            <details class="rm-mobile-menu-group"
                {{ in_array($active, ['settings', 'services', 'users', 'roles', 'records'], true) ? 'open' : '' }}>
                <summary>
                    <span>Gestion administrativa</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.4">
                        <path d="m6 9 6 6 6-6" />
                    </svg>
                </summary>
                <div>
                    @if ($sidebarCanSeeCommerce)
                    <a href="{{ route('settings.commerce') }}">Sucursales</a>
                    @endif
                    @if ($sidebarCanSeeServices)
                    <a href="{{ route('settings.services') }}">Servicios</a>
                    @endif
                    @if ($sidebarCanSeeRecords)
                    <a href="{{ route('settings.records') }}">Registros</a>
                    @endif
                    @if ($sidebarCanSeeUsers || $sidebarCanSeeRoles)
                    <a href="{{ route('settings.users') }}">Usuarios y roles</a>
                    @endif
                </div>
            </details>
            @endif
        </div>
        <div class="rm-mobile-profile">
            <span class="rm-side-profile-photo">
                @if ($sidebarUser?->profile_photo_path)
                    <img src="{{ asset('storage/' . $sidebarUser->profile_photo_path) }}"
                        alt="{{ $sidebarUser->name }}">
                @else
                    {{ $sidebarInitials ?: 'U' }}
                @endif
            </span>
            <span>
                <strong>{{ $sidebarUser?->name ?? 'Usuario' }}</strong>
                <small>{{ $sidebarRoleLabel }}</small>
            </span>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="rm-mobile-more-logout" type="submit"><span>Cuenta</span>Cerrar sesion</button>
        </form>
    </div>
</div>
