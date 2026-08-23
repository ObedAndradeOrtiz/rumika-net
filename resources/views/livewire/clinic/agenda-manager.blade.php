<div class="rm-content rm-settings-page">
    <section class="rm-panel rm-catalog-panel">
        <div class="rm-panel-title rm-agenda-toolbar">
            <div class="rm-agenda-heading">
                <div class="rm-agenda-branch-chip">
                    <span>Sucursal</span>
                    <strong>{{ $branch->name }}</strong>
                </div>
                <label class="rm-search-field rm-agenda-search" aria-label="Buscar cita">
                    <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.3">
                        <circle cx="11" cy="11" r="7" />
                        <path d="m20 20-3.5-3.5" />
                    </svg>
                    <input type="search" wire:model.live.debounce.300ms="appointmentSearch"
                        placeholder="Buscar cliente, CI, telefono o tratamiento">
                </label>
            </div>
            <div class="rm-action-row rm-agenda-actions">
                <button class="rm-button rm-button-outline rm-icon-button" type="button" wire:click="previousDay"
                    aria-label="Dia anterior" title="Dia anterior">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.4">
                        <path d="m15 18-6-6 6-6" />
                    </svg>
                </button>
                <input class="rm-date-inline" wire:model.live="selectedDate" type="date">
                <button class="rm-button rm-button-outline rm-icon-button" type="button" wire:click="nextDay"
                    aria-label="Dia siguiente" title="Dia siguiente">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.4">
                        <path d="m9 18 6-6-6-6" />
                    </svg>
                </button>
                <button class="rm-button rm-button-primary rm-icon-button" type="button" wire:click="createAppointment"
                    aria-label="Nueva cita" title="Nueva cita">
                    <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.4">
                        <path d="M8 2v4M16 2v4M3 10h18" />
                        <rect x="3" y="4" width="18" height="18" rx="3" />
                        <path d="M12 14v5M9.5 16.5h5" />
                    </svg>
                </button>
            </div>
        </div>

        <div class="rm-commerce-list">
            @forelse ($appointments as $appointment)
                <article class="rm-commerce-row rm-appointment-row">
                    <div class="rm-row-main">
                        <div class="rm-appointment-headline">
                            @if ($editingTimeAppointmentId === $appointment->id)
                                <div class="rm-appointment-time-editor">
                                    <input
                                        type="time"
                                        wire:model="editingAppointmentTime"
                                        wire:keydown.enter="saveAppointmentTime"
                                        wire:keydown.escape="cancelAppointmentTimeEdit"
                                        aria-label="Cambiar hora de cita"
                                        autofocus
                                    >
                                    <button type="button" wire:click="saveAppointmentTime" aria-label="Guardar hora">✓</button>
                                    <button type="button" wire:click="cancelAppointmentTimeEdit" aria-label="Cancelar">x</button>
                                </div>
                            @else
                                <button
                                    class="rm-commerce-icon rm-appointment-time"
                                    type="button"
                                    wire:click="editAppointmentTime({{ $appointment->id }})"
                                    title="Cambiar hora"
                                    aria-label="Cambiar hora de {{ $appointment->client->full_name }}"
                                >{{ $appointment->scheduled_at->format('H:i') }}</button>
                            @endif
                            <strong>{{ $appointment->client->full_name }}</strong>
                            @if ($appointment->client->displayPhone())
                                <span class="rm-appointment-phone">{{ $appointment->client->displayPhone() }}</span>
                            @endif
                        </div>
                        @error('editingAppointmentTime')
                            @if ($editingTimeAppointmentId === $appointment->id)
                                <small class="rm-inline-error">{{ $message }}</small>
                            @endif
                        @enderror
                        <div class="rm-service-scroll">
                            @forelse ($appointment->services as $service)
                                <span
                                    class="rm-service-pill {{ $service->status === 'completed' ? 'is-completed' : '' }}">
                                    {{ $service->name }}
                                    @if ($service->status === 'completed')
                                        <small>Finalizado</small>
                                    @else
                                        <button type="button"
                                            wire:click="completeAppointmentService({{ $service->id }})">Finalizar</button>
                                    @endif
                                </span>
                            @empty
                                <span>Sin servicios</span>
                            @endforelse
                        </div>
                        <div class="rm-commerce-meta">
                            <span>{{ $this->appointmentStatusLabel($appointment->status) }}</span>
                            @if ($appointment->attended)
                                <span class="rm-attendance-chip is-attended">
                                    ✓ Asistió

                                    @if ($appointment->attendedBy)
                                        · {{ $appointment->attendedBy->name }}
                                    @endif
                                </span>
                            @elseif ($appointment->status === 'no_show')
                                <span class="rm-attendance-chip is-no-show">
                                    × No asistió
                                </span>

                                @if ($appointment->rescheduledAppointments?->isNotEmpty())
                                    <span class="rm-attendance-chip is-rescheduled">
                                        ↻ Reagendado
                                    </span>
                                @endif

                                @if ($appointment->reschedule_reason)
                                    <span>
                                        Motivo: {{ $appointment->reschedule_reason }}
                                    </span>
                                @endif
                            @else
                                <span class="rm-attendance-chip">
                                    Pendiente
                                </span>
                                @if ($appointment->attendedBy)
                                    <span class="rm-attendance-chip">
                                        Doctor: {{ $appointment->attendedBy->name }}
                                    </span>
                                @endif
                            @endif
                            <span>Pagado {{ \App\Support\Money::symbol() }}
                                {{ number_format((float) $appointment->payments->sum('amount'), 2) }}</span>
                            @if ($appointment->reschedule_reason)
                                <span>Motivo: {{ $appointment->reschedule_reason }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="rm-commerce-actions">
                        <div class="rm-attendance-actions">
                            @if ($appointment->attended && $appointment->locked_by_payment)
                                <button class="rm-locked-payment-button" type="button" disabled>
                                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="2.3">
                                        <rect x="3" y="11" width="18" height="10" rx="2" />
                                        <path d="M7 11V8a5 5 0 0 1 10 0v3" />
                                    </svg>
                                    Bloqueado por pago
                                </button>
                            @else
                                <button type="button"
                                    wire:click="markAttended({{ $appointment->id }})">Asistio</button>
                                <button type="button" wire:click="markNoShow({{ $appointment->id }})">No
                                    asistio</button>
                            @endif
                        </div>
                        <div class="rm-icon-actions">
                            <button class="rm-icon-button is-payment" type="button"
                                wire:click="openPayment({{ $appointment->id }})" aria-label="Registrar pago"
                                title="Registrar pago">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2.3">
                                    <rect x="3" y="5" width="18" height="14" rx="3" />
                                    <path d="M3 10h18M8 15h2" />
                                </svg>
                            </button>
                            @foreach ($appointment->payments as $payment)
                                <button class="rm-icon-button is-edit-payment" type="button"
                                    wire:click="editPayment({{ $payment->id }})" aria-label="Editar pago"
                                    title="Editar pago">
                                    @if ($loop->first)
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2.3">
                                            <path d="M6 2h12v20l-3-2-3 2-3-2-3 2Z" />
                                            <path d="M9 8h6M9 12h6M9 16h3" />
                                        </svg>
                                    @else
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2.3">
                                            <path
                                                d="M4 7h16a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2Z" />
                                            <path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2M12 11v4M10 13h4" />
                                        </svg>
                                    @endif
                                </button>
                            @endforeach
                            <button class="rm-icon-button is-reschedule" type="button"
                                wire:click="openReschedule({{ $appointment->id }})" aria-label="Reagendar"
                                title="Reagendar">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2.3">
                                    <path d="M8 2v4M16 2v4M3 10h18" />
                                    <rect x="3" y="4" width="18" height="18" rx="3" />
                                    <path d="m15 14 2 2-2 2M9 18l-2-2 2-2M7 16h10" />
                                </svg>
                            </button>
                            <button class="rm-icon-button is-add-service" type="button"
                                wire:click="openAddServices({{ $appointment->id }})" aria-label="Agregar servicios"
                                title="Agregar servicios">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2.3">
                                    <path d="M12 5v14M5 12h14" />
                                    <path d="M4 4h16v16H4z" />
                                </svg>
                            </button>
                        </div>
                        <div class="rm-secondary-actions">
                            <button class="rm-icon-button is-history" type="button"
                                wire:click="openHistory({{ $appointment->client_id }})" aria-label="Ver historial"
                                title="Ver historial">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2.3">
                                    <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z" />
                                    <path d="M14 2v6h6M8 13h8M8 17h5" />
                                </svg>
                                <span>Ver historial</span>
                            </button>
                            @if ($canDeleteAppointments)
                                <button class="rm-icon-button is-delete" type="button"
                                    wire:click="confirmDeleteAppointment({{ $appointment->id }})"
                                    aria-label="Eliminar cita" title="Eliminar cita">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="2.3">
                                        <path d="M3 6h18" />
                                        <path d="M8 6V4h8v2" />
                                        <path d="M19 6l-1 15H6L5 6" />
                                        <path d="M10 11v6M14 11v6" />
                                    </svg>
                                    <span>Eliminar</span>
                                </button>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="rm-empty-state">
                    <strong>{{ trim($appointmentSearch) !== '' ? 'Sin resultados' : 'Sin citas' }}</strong>
                    <span>{{ trim($appointmentSearch) !== '' ? 'No encontramos citas con ese cliente, CI, telefono o tratamiento en esta fecha.' : 'Crea una cita con cliente, servicios, sesiones y pago inicial si corresponde.' }}</span>
                </div>
            @endforelse
        </div>
    </section>

    @if ($confirmingAppointmentDeleteId)
        <div class="rm-modal-backdrop" wire:click="cancelDeleteAppointment"></div>
        <section class="rm-modal-panel rm-modal-panel-sm" role="dialog" aria-modal="true">
            <div class="rm-modal-title">
                <div>
                    <span>Eliminar cita</span>
                    <h2>Confirmar eliminacion</h2>
                </div>
                <button type="button" wire:click="cancelDeleteAppointment">x</button>
            </div>
            <div class="rm-form-stack">
                <p class="rm-modal-subtitle">
                    Esto elimina la cita y revierte sus cobros, productos vendidos, movimientos de inventario y saldos
                    creados por error.
                </p>
                @error('appointmentDelete')
                    <small class="rm-inline-error">{{ $message }}</small>
                @enderror
                <div class="rm-form-actions">
                    <button class="rm-button rm-button-outline" type="button"
                        wire:click="cancelDeleteAppointment">Cancelar</button>
                    <button class="rm-button rm-button-danger" type="button"
                        wire:click="deleteAppointment({{ $confirmingAppointmentDeleteId }})">Eliminar cita</button>
                </div>
            </div>
        </section>
    @endif

    @include('livewire.clinic.partials.agenda-modals')
</div>
