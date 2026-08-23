<div class="rm-saas-shell">
    <aside class="rm-saas-sidebar">
        <a class="rm-saas-brand" href="{{ route('saas.dashboard') }}" wire:navigate>
            <span class="rm-brand-mark">
                <x-application-logo class="h-7 w-7 text-white" />
            </span>
            <span>
                <strong>Rumika SaaS</strong>
                <small>Administracion global</small>
            </span>
        </a>

        <nav class="rm-saas-nav">
            <a class="is-active" href="{{ route('saas.dashboard') }}" wire:navigate>
                <span>Inicio</span>
            </a>
            <span>Clientes SaaS</span>
            <span>Planes</span>
            <span>Usuarios</span>
        </nav>

        <form method="POST" action="{{ route('logout') }}" class="rm-saas-logout">
            @csrf
            <button type="submit">Cerrar sesion</button>
        </form>
    </aside>

    <main class="rm-saas-main">
        <header class="rm-saas-topbar">
            <div>
                <span>Panel propietario</span>
                <h1>Clientes de Rumika SaaS</h1>
                <p>Administra empresas, planes, usuarios y estado general de cada cliente.</p>
            </div>
            <div class="rm-saas-admin-chip">
                <strong>{{ auth()->user()->name }}</strong>
                <span>Super administrador SaaS</span>
            </div>
        </header>

        <section class="rm-saas-kpis">
            <article>
                <span>Empresas</span>
                <strong>{{ $totalCompanies }}</strong>
            </article>
            <article>
                <span>Activas</span>
                <strong>{{ $activeCompanies }}</strong>
            </article>
            <article>
                <span>En prueba</span>
                <strong>{{ $trialCompanies }}</strong>
            </article>
            <article>
                <span>Usuarios cliente</span>
                <strong>{{ $totalUsers }}</strong>
            </article>
            <article>
                <span>Potencial mensual</span>
                <strong>$ {{ number_format($monthlyPotential, 2) }}</strong>
            </article>
        </section>

        <section class="rm-saas-panel">
            <div class="rm-saas-panel-title">
                <div>
                    <span>Directorio</span>
                    <h2>Empresas registradas</h2>
                </div>
            </div>

            <div class="rm-saas-filters">
                <label>
                    <span>Buscar</span>
                    <input wire:model.live.debounce.350ms="search" type="search" placeholder="Empresa, usuario, correo o slug">
                </label>
                <label>
                    <span>Plan</span>
                    <select wire:model.live="plan">
                        <option value="all">Todos</option>
                        @foreach ($plans as $companyPlan)
                            <option value="{{ $companyPlan->slug }}">{{ $companyPlan->name }} - ${{ number_format((float) $companyPlan->monthly_price, 0) }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>Estado</span>
                    <select wire:model.live="status">
                        <option value="all">Todos</option>
                        <option value="trial">Prueba</option>
                        <option value="active">Activo</option>
                        <option value="suspended">Suspendido</option>
                    </select>
                </label>
            </div>

            <div class="rm-saas-company-list">
                @forelse ($companies as $company)
                    <article class="rm-saas-company-card">
                        <div class="rm-saas-company-head">
                            <span class="rm-saas-company-logo">
                                @if ($company->logo_path)
                                    <img src="{{ asset('storage/'.$company->logo_path) }}" alt="{{ $company->name }}">
                                @else
                                    {{ strtoupper(substr($company->name, 0, 1)) }}
                                @endif
                            </span>
                            <div>
                                <strong>{{ $company->name }}</strong>
                                <small>{{ $company->legal_name ?: $company->slug }}</small>
                            </div>
                        </div>

                        <div class="rm-saas-company-meta">
                            <span>{{ $company->plan?->name ?? 'Sin plan' }} · ${{ number_format((float) ($company->plan?->monthly_price ?? 0), 0) }}</span>
                            <span class="is-{{ $company->status }}">{{ ucfirst($company->status) }}</span>
                            <span>{{ $company->billing_status ?: 'Sin pago' }}</span>
                            <span>Acceso hasta {{ $company->access_expires_at?->format('d/m/Y') ?? ($company->trial_ends_at?->format('d/m/Y') ?? 'Sin fecha') }}</span>
                            <span>{{ $company->branches_count }} sucursales</span>
                            <span>{{ $company->users_count }} usuarios</span>
                            <span>{{ $company->clients_count }} clientes</span>
                            <span>{{ $company->appointments_count }} citas</span>
                        </div>

                        <div class="rm-saas-company-users">
                            @forelse ($company->users as $user)
                                <span>{{ $user->name }} <small>{{ $user->email }}</small></span>
                            @empty
                                <span>Sin usuarios asociados</span>
                            @endforelse
                        </div>

                        <div class="rm-saas-card-actions">
                            <button class="rm-button rm-button-outline" type="button" wire:click="editCompany({{ $company->id }})">
                                Editar acceso y plan
                            </button>
                        </div>
                    </article>
                @empty
                    <div class="rm-saas-empty">
                        <strong>Sin empresas</strong>
                        <span>No hay clientes SaaS con esos filtros.</span>
                    </div>
                @endforelse
            </div>
        </section>

        <section class="rm-saas-split">
            <div class="rm-saas-panel">
                <div class="rm-saas-panel-title">
                    <div>
                        <span>Comercial</span>
                        <h2>Planes</h2>
                    </div>
                </div>

                <div class="rm-saas-plan-list">
                    @foreach ($plans as $companyPlan)
                        <article>
                            <div>
                                <strong>{{ $companyPlan->name }}</strong>
                                <span>{{ $companyPlan->description }}</span>
                                @php
                                    $features = $companyPlan->features ?? [];
                                    $limits = $features['limits'] ?? [];
                                    $modules = $features['modules'] ?? [];
                                @endphp
                                <small>
                                    {{ in_array('*', $modules, true) ? 'Todos los modulos' : count($modules).' modulos' }}
                                    · Sucursales {{ $limits['branches'] ?? 'sin limite' }}
                                    · Usuarios {{ $limits['users'] ?? 'sin limite' }}
                                </small>
                            </div>
                            <b>$ {{ number_format((float) $companyPlan->monthly_price, 0) }}</b>
                        </article>
                    @endforeach
                </div>
            </div>

            <div class="rm-saas-panel">
                <div class="rm-saas-panel-title">
                    <div>
                        <span>Accesos</span>
                        <h2>Usuarios recientes</h2>
                    </div>
                </div>

                <div class="rm-saas-user-list">
                    @forelse ($latestUsers as $user)
                        <article>
                            <span>{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                            <div>
                                <strong>{{ $user->name }}</strong>
                                <small>{{ $user->email }} · {{ $user->companies->pluck('name')->join(', ') ?: 'Sin empresa' }}</small>
                            </div>
                        </article>
                    @empty
                        <div class="rm-saas-empty">
                            <strong>Sin usuarios</strong>
                            <span>Aun no hay usuarios cliente registrados.</span>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>
    </main>

    @if ($editingCompanyId)
        <div class="rm-modal-backdrop" wire:click="closeCompanyEditor"></div>
        <section class="rm-modal-panel rm-saas-editor-modal" role="dialog" aria-modal="true">
            <div class="rm-modal-title">
                <div>
                    <span>Cliente SaaS</span>
                    <h2>Editar acceso y plan</h2>
                </div>
                <button type="button" wire:click="closeCompanyEditor">x</button>
            </div>

            <form class="rm-form-stack" wire:submit="saveCompanyBilling">
                <label class="rm-field">
                    <span>Nombre comercial</span>
                    <input class="rm-input" wire:model="editCompanyName" type="text">
                    @error('editCompanyName') <small>{{ $message }}</small> @enderror
                </label>

                <div class="rm-form-row">
                    <label class="rm-field">
                        <span>Plan</span>
                        <select class="rm-input" wire:model="editPlanId">
                            @foreach ($plans as $companyPlan)
                                <option value="{{ $companyPlan->id }}">{{ $companyPlan->name }} - ${{ number_format((float) $companyPlan->monthly_price, 0) }}</option>
                            @endforeach
                        </select>
                        @error('editPlanId') <small>{{ $message }}</small> @enderror
                    </label>

                    <label class="rm-field">
                        <span>Estado del cliente</span>
                        <select class="rm-input" wire:model="editStatus">
                            <option value="trial">Prueba</option>
                            <option value="active">Activo</option>
                            <option value="past_due">Vencido</option>
                            <option value="blocked">Bloqueado</option>
                            <option value="suspended">Suspendido</option>
                        </select>
                        @error('editStatus') <small>{{ $message }}</small> @enderror
                    </label>
                </div>

                <div class="rm-form-row">
                    <label class="rm-field">
                        <span>Estado de pago</span>
                        <select class="rm-input" wire:model="editBillingStatus">
                            <option value="trial">Demo</option>
                            <option value="paid">Pagado</option>
                            <option value="pending">Pendiente</option>
                            <option value="past_due">Vencido</option>
                            <option value="blocked">Bloqueado</option>
                        </select>
                        @error('editBillingStatus') <small>{{ $message }}</small> @enderror
                    </label>

                    <label class="rm-field">
                        <span>Fecha de pago</span>
                        <input class="rm-input" wire:model="editLastPaidAt" type="date">
                        @error('editLastPaidAt') <small>{{ $message }}</small> @enderror
                    </label>
                </div>

                <div class="rm-form-row">
                    <label class="rm-field">
                        <span>Acceso hasta</span>
                        <input class="rm-input" wire:model="editAccessExpiresAt" type="date">
                        @error('editAccessExpiresAt') <small>{{ $message }}</small> @enderror
                    </label>

                    <label class="rm-field">
                        <span>Proximo pago</span>
                        <input class="rm-input" wire:model="editNextPaymentDueAt" type="date">
                        @error('editNextPaymentDueAt') <small>{{ $message }}</small> @enderror
                    </label>
                </div>

                <label class="rm-field">
                    <span>Notas de facturacion</span>
                    <textarea class="rm-input" wire:model="editBillingNotes" rows="4" placeholder="Comprobante, monto, banco, observacion interna"></textarea>
                    @error('editBillingNotes') <small>{{ $message }}</small> @enderror
                </label>

                <div class="rm-saas-editor-actions">
                    <button class="rm-button rm-button-outline" type="button" wire:click="grantMonthlyAccess">
                        Dar acceso 1 mes pagado
                    </button>
                    <button class="rm-button rm-button-muted" type="button" wire:click="closeCompanyEditor">
                        Cancelar
                    </button>
                    <button class="rm-button rm-button-primary" type="submit">
                        Guardar cambios
                    </button>
                </div>
            </form>
        </section>
    @endif
</div>
