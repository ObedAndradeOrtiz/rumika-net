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
        <button class="rm-button rm-button-primary" type="button" wire:click="createClient" data-rumi-action="new-client">Nuevo cliente</button>
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

        <div class="rm-client-list-shell">
            <section class="rm-panel rm-nested-panel">
                <div class="rm-commerce-list">
                    @forelse ($clients as $client)
                        <article class="rm-commerce-row rm-client-list-row {{ $selectedClient?->id === $client->id ? 'is-selected' : '' }}">
                            <div class="rm-row-main">
                                <strong>{{ $client->full_name }}</strong>
                                <span>{{ $client->displayContact() ?? 'Sin telefono ni CI' }}</span>
                                <div class="rm-commerce-meta">
                                    <span>{{ $client->status === 'active' ? 'Activo' : 'Inactivo' }}</span>
                                    @foreach ($client->phones->take(3) as $phone)
                                        <span>{{ $phone->label ? $phone->label.': ' : '' }}{{ $phone->phone }}</span>
                                    @endforeach
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

                <label class="rm-field">
                    <span>CI / documento</span>
                    <input wire:model="identityNumber" type="text" placeholder="Documento">
                    @error('identityNumber') <small>{{ $message }}</small> @enderror
                </label>

                <div class="rm-field rm-phone-editor">
                    <div class="rm-phone-header">
                        <div>
                            <span>Teléfonos</span>
                            <strong>{{ collect($phones)->firstWhere('is_primary', true)['phone'] ?? 'Sin número principal' }}</strong>
                            <small>Marca cuál será el número principal del cliente.</small>
                        </div>
                        <label class="rm-field rm-field-compact">
                            <span>País</span>
                            <select wire:model="phoneCountry">
                                @foreach ($phoneCountries as $countryCode => $countryRule)
                                    <option value="{{ $countryCode }}">{{ $countryRule['name'] }} (+{{ $countryRule['code'] }})</option>
                                @endforeach
                            </select>
                            @error('phoneCountry') <small>{{ $message }}</small> @enderror
                        </label>
                    </div>
                    <div class="rm-phone-list">
                        @foreach ($phones as $index => $phoneRow)
                            <div class="rm-phone-row {{ ! empty($phoneRow['is_primary']) ? 'is-primary' : '' }}" wire:key="client-phone-{{ $index }}">
                                <button class="rm-phone-primary" type="button" wire:click="setPrimaryPhone({{ $index }})" aria-label="Marcar teléfono principal">
                                    @if (! empty($phoneRow['is_primary']))
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6">
                                            <path d="m5 12 4 4L19 6" />
                                        </svg>
                                    @else
                                        <span></span>
                                    @endif
                                </button>
                                <label>
                                    <span>Número</span>
                                    <input wire:model="phones.{{ $index }}.phone" type="text" placeholder="70000000">
                                </label>
                                <label>
                                    <span>Etiqueta</span>
                                    <input wire:model="phones.{{ $index }}.label" type="text" placeholder="{{ ! empty($phoneRow['is_primary']) ? 'Principal' : 'Casa, trabajo, familiar' }}">
                                </label>
                                <button class="rm-phone-remove" type="button" wire:click="removePhone({{ $index }})">Quitar</button>
                            </div>
                        @endforeach
                    </div>
                    <button class="rm-button rm-button-outline" type="button" wire:click="addPhone">Agregar teléfono</button>
                    @error('phones.*.phone') <small>{{ $message }}</small> @enderror
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

    @if ($showHistoryModal && $selectedClient)
        <div class="rm-modal-backdrop" wire:click="closeHistoryModal"></div>
        <section class="rm-modal-panel rm-modal-panel-xl" role="dialog" aria-modal="true">
            <div class="rm-modal-title">
                <div>
                    <span>Historial clinico</span>
                    <h2>{{ $selectedClient->full_name }}</h2>
                </div>
                <button type="button" wire:click="closeHistoryModal">x</button>
            </div>

            <div class="rm-form-stack">
                <div class="rm-commerce-meta">
                    <span>CI {{ $selectedClient->identity_number ?? 'N/A' }}</span>
                    @forelse ($selectedClient->phones as $phone)
                        <span>{{ $phone->label ? $phone->label.': ' : '' }}{{ $phone->phone }}</span>
                    @empty
                        <span>{{ $selectedClient->displayContact() ?? 'Sin telefono ni CI' }}</span>
                    @endforelse
                    <span>{{ $selectedClient->email ?? 'Sin email' }}</span>
                    <span>{{ $selectedClient->status === 'active' ? 'Activo' : 'Inactivo' }}</span>
                </div>

                <div class="rm-tab-switcher rm-tab-switcher-four rm-history-tabs">
                    <button class="{{ $historyTab === 'appointments' ? 'is-active' : '' }}" type="button" wire:click="setHistoryTab('appointments')">Citas <span>{{ $selectedClient->appointments->count() }}</span></button>
                    <button class="{{ $historyTab === 'products' ? 'is-active' : '' }}" type="button" wire:click="setHistoryTab('products')">Productos <span>{{ $historyProductItems->count() }}</span></button>
                    <button class="{{ $historyTab === 'service_debts' ? 'is-active' : '' }}" type="button" wire:click="setHistoryTab('service_debts')">Tratamientos <span>{{ $historyPendingServiceCharges->count() }}</span></button>
                    <button class="{{ $historyTab === 'product_debts' ? 'is-active' : '' }}" type="button" wire:click="setHistoryTab('product_debts')">A cuenta <span>{{ $historyPendingProductCharges->count() }}</span></button>
                </div>

                @if ($historyTab === 'appointments')
                    <div class="rm-history-section">
                        <div class="rm-commerce-list">
                            @forelse ($selectedClient->appointments->sortByDesc('scheduled_at') as $appointment)
                                <article class="rm-commerce-row">
                                    <div class="rm-commerce-icon">{{ $appointment->scheduled_at->format('d/m') }}</div>
                                    <div class="rm-row-main">
                                        <strong>{{ $appointment->services->pluck('name')->join(' + ') ?: 'Atencion programada' }}</strong>
                                <span>{{ $appointment->scheduled_at->format('d/m/Y H:i') }} - {{ match($appointment->status) { 'scheduled' => 'Programada', 'rescheduled' => 'Reagendada', 'completed' => 'Finalizada', 'no_show' => 'No asistió', default => 'Pendiente' } }}</span>
                                        <div class="rm-service-scroll">
                                            @foreach ($appointment->services as $service)
                                                <span class="rm-service-pill {{ $service->status === 'completed' ? 'is-completed' : '' }}">
                                                    {{ $service->name }}
                                                    <small>{{ $service->status === 'completed' ? 'Finalizado' : 'Pendiente' }}</small>
                                                </span>
                                            @endforeach
                                        </div>
                                        <div class="rm-commerce-meta">
                                <span>{{ $appointment->attended ? 'Asistió' : 'Sin asistencia' }}</span>
                                            @if ($appointment->attendedBy)
                                                <span>Doctor {{ $appointment->attendedBy->name }}</span>
                                            @endif
                                            <span>Pagos {{ \App\Support\Money::symbol() }} {{ number_format((float) $appointment->payments->sum('amount'), 2) }}</span>
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
                    </div>
                @endif

                @if ($historyTab === 'products')
                    <div class="rm-history-section">
                        <div class="rm-commerce-list">
                            @forelse ($historyProductItems as $item)
                                <article class="rm-commerce-row">
                                    <div class="rm-commerce-icon">{{ $item->payment?->paid_at?->format('d/m') ?? $item->created_at->format('d/m') }}</div>
                                    <div class="rm-row-main">
                                        <strong>{{ $item->name }}</strong>
                                        <span>{{ $item->quantity }} x {{ \App\Support\Money::symbol() }} {{ number_format((float) $item->unit_price, 2) }} - Pagado {{ \App\Support\Money::symbol() }} {{ number_format((float) $item->total, 2) }}</span>
                                        <div class="rm-commerce-meta">
                                            <span>Total {{ \App\Support\Money::symbol() }} {{ number_format((float) ($item->charged_total ?: $item->total), 2) }}</span>
                                            @if ($item->batch)<span>Lote {{ $item->batch->lot_code }}</span>@endif
                                            @if ($item->soldBy)<span>Vendido por {{ $item->soldBy->name }}</span>@endif
                                        </div>
                                    </div>
                                </article>
                            @empty
                                <div class="rm-empty-state"><strong>Sin productos</strong><span>Las compras de productos apareceran aqui.</span></div>
                            @endforelse
                        </div>
                    </div>
                @endif

                @if ($historyTab === 'service_debts' || $historyTab === 'product_debts')
                    @php
                        $debtRows = $historyTab === 'service_debts' ? $historyPendingServiceCharges : $historyPendingProductCharges;
                    @endphp
                    <div class="rm-history-section">
                        <div class="rm-history-debt-card">
                            @forelse ($debtRows as $charge)
                                <div class="rm-pending-charge-row">
                                    <div>
                                        <strong>{{ $charge->name }}</strong>
                                        <span>Total {{ \App\Support\Money::symbol() }} {{ number_format((float) $charge->total_amount, 2) }} - Pagado {{ \App\Support\Money::symbol() }} {{ number_format((float) $charge->paid_amount, 2) }}</span>
                                        @if ($charge->type === 'product' && $charge->soldBy)
                                            <span>Vendido por {{ $charge->soldBy->name }}</span>
                                        @endif
                                    </div>
                                    <span class="rm-debt-balance">Saldo {{ \App\Support\Money::symbol() }} {{ number_format((float) $charge->balance_amount, 2) }}</span>
                                </div>
                            @empty
                                <div class="rm-empty-state"><strong>Sin pendientes</strong><span>No hay saldos pendientes en esta seccion.</span></div>
                            @endforelse
                        </div>
                    </div>
                @endif
            </div>
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
