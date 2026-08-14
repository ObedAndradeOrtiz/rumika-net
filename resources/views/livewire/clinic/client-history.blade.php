<div class="rm-content rm-settings-page">
    <div class="rm-settings-hero">
        <div>
            <span>Clientes</span>
            <h1>Clientes e historial clinico</h1>
            <p>Consulta ficha, tratamientos, sesiones, pagos y asistencia de cada cliente.</p>
        </div>
        <div class="rm-settings-summary">
            <strong>{{ $clientCount }}</strong>
            <span>Clientes activos en esta sucursal</span>
        </div>
    </div>

    <section class="rm-panel rm-catalog-panel">
        <div class="rm-panel-title">
            <div>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9.5" cy="7" r="4"/><path d="M20 8v6M17 11h6"/></svg>
                <h2>Lista de clientes</h2>
            </div>
            <button class="rm-button rm-button-primary" type="button" wire:click="createClient">Nuevo cliente</button>
        </div>

        <div class="rm-filter-row">
            <label class="rm-search-field">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input wire:model.live.debounce.300ms="search" type="search" placeholder="Buscar por nombre, CI, telefono o email">
            </label>

            <label class="rm-field rm-field-compact">
                <span>Estado</span>
                <select wire:model.live="statusFilter">
                    <option value="active">Activos</option>
                    <option value="inactive">Inactivos</option>
                    <option value="all">Todos</option>
                </select>
            </label>
        </div>

        <div class="rm-settings-grid">
            <section class="rm-panel rm-nested-panel">
                <div class="rm-commerce-list">
                    @forelse ($clients as $client)
                        <article class="rm-commerce-row rm-client-list-row {{ $selectedClient?->id === $client->id ? 'is-selected' : '' }}">
                            <div class="rm-user-avatar">{{ strtoupper(substr($client->full_name, 0, 1)) }}</div>
                            <div class="rm-row-main">
                                <strong>{{ $client->full_name }}</strong>
                                <span>CI {{ $client->identity_number ?? 'N/A' }} - {{ $client->phone ?? 'Sin telefono' }}</span>
                                <div class="rm-commerce-meta">
                                    <span>{{ $client->status === 'active' ? 'Activo' : 'Inactivo' }}</span>
                                    @if ($client->email)
                                        <span>{{ $client->email }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="rm-commerce-actions">
                                <button type="button" wire:click="selectClient({{ $client->id }})">Historial</button>
                                <button type="button" wire:click="editClient({{ $client->id }})">Editar</button>
                                @if ($client->status === 'active')
                                    <button type="button" wire:click="confirmInactivateClient({{ $client->id }})">Inactivar</button>
                                @endif
                            </div>
                        </article>
                    @empty
                        <div class="rm-empty-state"><strong>Sin clientes</strong><span>Crea clientes desde agenda o desde esta pantalla.</span></div>
                    @endforelse
                </div>
                @if ($clients->hasPages())
                    <div class="rm-pagination-wrap">
                        <nav class="rm-pagination-compact" aria-label="Paginacion de clientes">
                            <button
                                type="button"
                                wire:click="previousPage"
                                wire:loading.attr="disabled"
                                @disabled($clients->onFirstPage())
                            >
                                Anterior
                            </button>
                            <span>
                                Pagina {{ $clients->currentPage() }} de {{ $clients->lastPage() }}
                                <small>{{ $clients->firstItem() }}-{{ $clients->lastItem() }} de {{ $clients->total() }}</small>
                            </span>
                            <button
                                type="button"
                                wire:click="nextPage"
                                wire:loading.attr="disabled"
                                @disabled(! $clients->hasMorePages())
                            >
                                Siguiente
                            </button>
                        </nav>
                    </div>
                @endif
            </section>

            <section class="rm-panel rm-nested-panel">
                @if ($selectedClient)
                    <div class="rm-panel-title"><div><h2>{{ $selectedClient->full_name }}</h2></div></div>
                    <div class="rm-commerce-meta">
                        <span>CI {{ $selectedClient->identity_number ?? 'N/A' }}</span>
                        <span>{{ $selectedClient->phone ?? 'Sin telefono' }}</span>
                        <span>{{ $selectedClient->email ?? 'Sin email' }}</span>
                        <span>{{ $selectedClient->status === 'active' ? 'Activo' : 'Inactivo' }}</span>
                    </div>
                    <div class="rm-commerce-list">
                        @forelse ($selectedClient->appointments->sortByDesc('scheduled_at') as $appointment)
                            <article class="rm-commerce-row">
                                <div class="rm-commerce-icon">{{ $appointment->scheduled_at->format('d/m') }}</div>
                                <div class="rm-row-main">
                                    <strong>{{ $appointment->services->pluck('name')->join(' + ') }}</strong>
                                    <span>{{ $appointment->scheduled_at->format('d/m/Y H:i') }} - {{ ucfirst(str_replace('_', ' ', $appointment->status)) }}</span>
                                    <div class="rm-commerce-meta">
                                        <span>{{ $appointment->attended ? 'Asistio' : 'Sin asistencia' }}</span>
                                        <span>Pagos Bs {{ number_format((float) $appointment->payments->sum('amount'), 2) }}</span>
                                        @if ($appointment->reschedule_reason)
                                            <span>Motivo: {{ $appointment->reschedule_reason }}</span>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="rm-empty-state"><strong>Sin historial</strong><span>Este cliente aun no tiene citas registradas.</span></div>
                        @endforelse
                    </div>
                @else
                    <div class="rm-empty-state"><strong>Selecciona un cliente</strong><span>Aqui veras tratamientos, citas y pagos.</span></div>
                @endif
            </section>
        </div>
    </section>

    @if ($showClientModal)
        <div class="rm-modal-backdrop" wire:click="closeClientModal"></div>
        <section class="rm-modal-panel rm-modal-panel-wide" role="dialog" aria-modal="true" aria-labelledby="client-modal-title">
            <div class="rm-modal-title">
                <div>
                    <span>{{ $editingClientId ? 'Editar cliente' : 'Nuevo cliente' }}</span>
                    <h2 id="client-modal-title">{{ $editingClientId ? 'Actualizar ficha de cliente' : 'Crear ficha de cliente' }}</h2>
                </div>
                <button type="button" wire:click="closeClientModal" aria-label="Cerrar modal">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <form wire:submit="saveClient" class="rm-form-stack">
                <label class="rm-field">
                    <span>Nombre completo</span>
                    <input wire:model="fullName" type="text" placeholder="Nombre y apellido">
                    @error('fullName') <small>{{ $message }}</small> @enderror
                </label>

                <div class="rm-form-row">
                    <label class="rm-field">
                        <span>CI / documento</span>
                        <input wire:model="identityNumber" type="text" placeholder="Documento">
                        @error('identityNumber') <small>{{ $message }}</small> @enderror
                    </label>

                    <label class="rm-field">
                        <span>Telefono</span>
                        <input wire:model="phone" type="text" placeholder="70000000">
                        @error('phone') <small>{{ $message }}</small> @enderror
                    </label>
                </div>

                <div class="rm-form-row">
                    <label class="rm-field">
                        <span>Email</span>
                        <input wire:model="email" type="email" placeholder="cliente@correo.com">
                        @error('email') <small>{{ $message }}</small> @enderror
                    </label>

                    <label class="rm-field">
                        <span>Fecha de nacimiento</span>
                        <input wire:model="birthDate" type="date">
                        @error('birthDate') <small>{{ $message }}</small> @enderror
                    </label>
                </div>

                <label class="rm-field">
                    <span>Estado</span>
                    <select wire:model="status">
                        <option value="active">Activo</option>
                        <option value="inactive">Inactivo</option>
                    </select>
                    @error('status') <small>{{ $message }}</small> @enderror
                </label>

                <label class="rm-field">
                    <span>Notas clinicas</span>
                    <textarea wire:model="clinicalNotes" rows="4" placeholder="Antecedentes, alergias, observaciones o preferencias importantes"></textarea>
                    @error('clinicalNotes') <small>{{ $message }}</small> @enderror
                </label>

                <div class="rm-form-actions">
                    <button class="rm-button rm-button-primary" type="submit">{{ $editingClientId ? 'Guardar cambios' : 'Crear cliente' }}</button>
                    <button class="rm-button rm-button-outline" type="button" wire:click="closeClientModal">Cancelar</button>
                </div>
            </form>
        </section>
    @endif

    @if ($confirmingInactiveClientId)
        <div class="rm-modal-backdrop" wire:click="cancelInactivateClient"></div>
        <section class="rm-modal-panel rm-modal-panel-small" role="dialog" aria-modal="true">
            <div class="rm-confirm-icon">!</div>
            <h2>Inactivar cliente</h2>
            <p>El historial, citas y pagos se conservaran. El cliente dejara de aparecer en la lista de activos.</p>
            <div class="rm-form-actions">
                <button class="rm-button rm-button-danger" type="button" wire:click="inactivateClient({{ $confirmingInactiveClientId }})">Inactivar</button>
                <button class="rm-button rm-button-outline" type="button" wire:click="cancelInactivateClient">Cancelar</button>
            </div>
        </section>
    @endif
</div>
