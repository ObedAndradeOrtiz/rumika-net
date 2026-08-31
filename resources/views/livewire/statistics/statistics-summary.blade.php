<div class="rm-content rm-statistics-page">
    <section class="rm-panel rm-statistics-hero">
        <div>
            <span>Estadisticas</span>
            <h1>Indicadores del negocio</h1>
            <p>Asistencia, ingresos, egresos y rendimiento por sucursal.</p>
        </div>
        <div class="rm-stat-filters">
            <label class="rm-field">
                <span>Desde</span>
                <input type="date" wire:model.live="dateFrom">
            </label>
            <label class="rm-field">
                <span>Hasta</span>
                <input type="date" wire:model.live="dateTo">
            </label>
            <label class="rm-field">
                <span>Sucursal</span>
                <select wire:model.live="branchFilter">
                    <option value="">Todas</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="rm-field">
                <span>Año</span>
                <select wire:model.live="year">
                    @foreach ($yearOptions as $yearOption)
                        <option value="{{ $yearOption }}">{{ $yearOption }}</option>
                    @endforeach
                </select>
            </label>
        </div>
    </section>

    <section class="rm-stat-grid">
        <article class="rm-stat-circle-card">
            <div class="rm-stat-circle-main">
                <div class="rm-stat-ring" style="--value: {{ $attendance['rate'] }};">
                    <span>{{ $attendance['rate'] }}%</span>
                </div>
                <div class="rm-stat-circle-copy">
                    <strong>Asistencia</strong>
                    <span>{{ $dateLabel }}</span>
                </div>
            </div>
            <div class="rm-stat-mini-grid">
                <span><strong>{{ $attendance['scheduled'] }}</strong>Agendados</span>
                <span><strong>{{ $attendance['attended'] }}</strong>Asistieron</span>
                <span><strong>{{ $attendance['no_show'] }}</strong>No asistieron</span>
                <span><strong>{{ $attendance['pending'] }}</strong>Pendientes</span>
            </div>
        </article>

        <article class="rm-stat-money-card">
            <span>Ingresos</span>
            <strong>{{ \App\Support\Money::symbol() }} {{ number_format((float) $finance['income'], 2) }}</strong>
            <div>
                <small>Servicios {{ \App\Support\Money::symbol() }} {{ number_format((float) $finance['services'], 2) }}</small>
                <small>Productos {{ \App\Support\Money::symbol() }} {{ number_format((float) $finance['products'], 2) }}</small>
            </div>
        </article>
        <article class="rm-stat-money-card rm-stat-click-card" role="button" tabindex="0" wire:click="openNewPatientsModal">
            <span>Pacientes nuevos</span>
            <strong>{{ number_format((int) $patients['new']) }}</strong>
            <div>
                <small>Registrados en el rango</small>
                <small>Click para ver detalle</small>
            </div>
        </article>
        <article class="rm-stat-money-card">
            <span>Egresos</span>
            <strong>{{ \App\Support\Money::symbol() }} {{ number_format((float) $finance['expenses'], 2) }}</strong>
            <div>
                <small>Gastos registrados</small>
                <small>Neto {{ \App\Support\Money::symbol() }} {{ number_format((float) $finance['net'], 2) }}</small>
            </div>
        </article>
        <article class="rm-stat-money-card">
            <span>Metodos de pago</span>
            <strong>{{ \App\Support\Money::symbol() }} {{ number_format((float) $finance['cash'] + (float) $finance['qr'], 2) }}</strong>
            <div>
                <small>Efectivo {{ \App\Support\Money::symbol() }} {{ number_format((float) $finance['cash'], 2) }}</small>
                <small>QR {{ \App\Support\Money::symbol() }} {{ number_format((float) $finance['qr'], 2) }}</small>
                <small>Ticket prom. {{ \App\Support\Money::symbol() }} {{ number_format((float) $finance['average_ticket'], 2) }}</small>
            </div>
        </article>
    </section>

    <section class="rm-panel">
        <div class="rm-panel-title">
            <div>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 3v18h18"/><path d="m7 14 3-3 3 2 5-6"/></svg>
                <h2>Estadisticas por sucursal</h2>
            </div>
            <span>{{ $branchRows->count() }}</span>
        </div>
        <div class="rm-stat-table">
            <div class="rm-stat-table-head">
                <span>Sucursal</span>
                <span>Agendados</span>
                <span>Asistidos</span>
                <span>Ingresos</span>
                <span>Egresos</span>
                <span>Neto</span>
            </div>
            @forelse ($branchRows as $row)
                <article class="rm-stat-table-row">
                    <span><strong>{{ $row['name'] }}</strong><small>{{ $row['type'] }}</small></span>
                    <span>{{ $row['scheduled'] }}</span>
                    <span>{{ $row['attended'] }} <small>{{ $row['rate'] }}%</small></span>
                    <span>{{ \App\Support\Money::symbol() }} {{ number_format((float) $row['income'], 2) }}</span>
                    <span>{{ \App\Support\Money::symbol() }} {{ number_format((float) $row['expenses'], 2) }}</span>
                    <span>{{ \App\Support\Money::symbol() }} {{ number_format((float) $row['net'], 2) }}</span>
                </article>
            @empty
                <div class="rm-empty-state"><strong>Sin datos</strong><span>No hay movimientos en el rango seleccionado.</span></div>
            @endforelse
        </div>
    </section>

    <section class="rm-panel">
        <div class="rm-panel-title">
            <div>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9.5" cy="7" r="4"/><path d="M20 8v8M16 12h8"/></svg>
                <h2>Profesionales con mas consultas</h2>
            </div>
            <span>{{ $topProfessionals->count() }}</span>
        </div>
        <div class="rm-professional-rank-list">
            @forelse ($topProfessionals as $professional)
                <article class="rm-professional-click-row" role="button" tabindex="0" wire:click="openProfessionalModal('{{ $professional['key'] }}')">
                    <div class="rm-professional-rank-info">
                        <strong>{{ $professional['name'] }}</strong>
                        <small>{{ $professional['count'] }} consulta(s) atendida(s) - click para ver detalle</small>
                    </div>
                    <div class="rm-professional-rank-meter">
                        <span>{{ $professional['percentage'] }}%</span>
                        <div><i style="width: {{ $professional['percentage'] }}%"></i></div>
                    </div>
                </article>
            @empty
                <div class="rm-dashboard-empty">Sin consultas atendidas en este rango.</div>
            @endforelse
        </div>
    </section>

    <div class="rm-stat-sales-grid">
        <section class="rm-panel">
            <div class="rm-panel-title">
                <div>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 11h-6M19 8v6"/></svg>
                    <h2>Vendedores de productos</h2>
                </div>
                <span>{{ $topSellers->count() }}</span>
            </div>
            <div class="rm-stat-rank-list">
                @forelse ($topSellers as $seller)
                    <article>
                        <div>
                            <strong>{{ $seller['name'] }}</strong>
                            <small>{{ number_format((float) $seller['quantity'], 2) }} producto(s) en {{ $seller['count'] }} venta(s)</small>
                        </div>
                        <span>{{ \App\Support\Money::symbol() }} {{ number_format((float) $seller['total'], 2) }}</span>
                    </article>
                @empty
                    <div class="rm-dashboard-empty">Sin ventas de productos en este rango.</div>
                @endforelse
            </div>
        </section>

        <section class="rm-panel">
            <div class="rm-panel-title">
                <div>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/></svg>
                    <h2>Productos mas vendidos</h2>
                </div>
                <span>{{ $topProducts->count() }}</span>
            </div>
            <div class="rm-stat-rank-list">
                @forelse ($topProducts as $product)
                    <article>
                        <div>
                            <strong>{{ $product['name'] }}</strong>
                            <small>{{ number_format((float) $product['quantity'], 2) }} unidad(es)</small>
                        </div>
                        <span>{{ \App\Support\Money::symbol() }} {{ number_format((float) $product['total'], 2) }}</span>
                    </article>
                @empty
                    <div class="rm-dashboard-empty">Sin productos vendidos en este rango.</div>
                @endforelse
            </div>
        </section>
    </div>

    <section class="rm-panel">
        <div class="rm-panel-title">
            <div>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 3v18h18"/><path d="M7 14l3-3 3 2 5-6"/><path d="M18 7h3v3"/></svg>
                <h2>Panel anual {{ $year }}</h2>
            </div>
            <span>12 meses</span>
        </div>
        <div class="rm-stat-table rm-stat-annual-table">
            <div class="rm-stat-table-head">
                <span>Mes</span>
                <span>Servicios</span>
                <span>Productos</span>
                <span>Ingresos</span>
                <span>Gastos</span>
                <span>Neto</span>
                <span>Asistencia</span>
            </div>
            @foreach ($annualRows as $row)
                <article class="rm-stat-table-row">
                    <span><strong>{{ $row['month'] }}</strong></span>
                    <span>{{ \App\Support\Money::symbol() }} {{ number_format((float) $row['services'], 2) }}</span>
                    <span>{{ \App\Support\Money::symbol() }} {{ number_format((float) $row['products'], 2) }}</span>
                    <span>{{ \App\Support\Money::symbol() }} {{ number_format((float) $row['income'], 2) }}</span>
                    <span>{{ \App\Support\Money::symbol() }} {{ number_format((float) $row['expenses'], 2) }}</span>
                    <span>{{ \App\Support\Money::symbol() }} {{ number_format((float) $row['net'], 2) }}</span>
                    <span>{{ $row['attended'] }}/{{ $row['scheduled'] }}</span>
                </article>
            @endforeach
        </div>
    </section>

    <div class="rm-stat-bottom-grid">
        <section class="rm-panel">
            <div class="rm-panel-title">
                <div>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M8 2v4M16 2v4M3 10h18"/><rect x="3" y="4" width="18" height="18" rx="3"/></svg>
                    <h2>Asistencia diaria</h2>
                </div>
            </div>
            <div class="rm-stat-bars">
                @foreach ($dailyRows as $row)
                    @php $maxValue = max(1, $dailyRows->max('scheduled')); @endphp
                    <article>
                        <span>{{ $row['date'] }}</span>
                        <div><i style="width: {{ min(100, ($row['scheduled'] / $maxValue) * 100) }}%"></i></div>
                        <strong>{{ $row['attended'] }}/{{ $row['scheduled'] }}</strong>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="rm-panel">
            <div class="rm-panel-title">
                <div>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 20V10"/><path d="M18 20V4"/><path d="M6 20v-6"/></svg>
                    <h2>Tratamientos mas agendados</h2>
                </div>
            </div>
            <div class="rm-alert-list">
                @forelse ($topServices as $service)
                    <article>
                        <span class="rm-alert-dot is-stock"></span>
                        <div><strong>{{ $service['name'] }}</strong><small>{{ $service['count'] }} cita(s)</small></div>
                    </article>
                @empty
                    <div class="rm-dashboard-empty">Sin tratamientos en este rango.</div>
                @endforelse
            </div>
        </section>
    </div>

    @if ($showProfessionalModal)
        <div class="rm-modal-backdrop" wire:click="closeProfessionalModal"></div>
        <section class="rm-modal-panel rm-stat-patient-modal" role="dialog" aria-modal="true">
            <div class="rm-modal-title">
                <div>
                    <span>Consultas atendidas</span>
                    <h2>{{ $selectedProfessionalName }}</h2>
                </div>
                <button type="button" wire:click="closeProfessionalModal">x</button>
            </div>

            <div class="rm-stat-modal-filter-row">
                <label class="rm-field">
                    <span>Filtrar por tratamiento</span>
                    <select wire:model.live="professionalServiceFilter">
                        <option value="">Todos los tratamientos</option>
                        @foreach ($professionalTreatmentOptions as $treatment)
                            <option value="{{ $treatment }}">{{ $treatment }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="rm-stat-modal-count">
                    <span>Resultados</span>
                    <strong>{{ $professionalRows->count() }}</strong>
                </div>
            </div>

            <div class="rm-stat-patient-list">
                @forelse ($professionalRows as $row)
                    <article class="rm-stat-patient-card rm-stat-professional-card">
                        <header>
                            <div>
                                <strong>{{ $row['patient'] }}</strong>
                                <span>{{ $row['phone'] ?: 'Sin telefono' }}</span>
                            </div>
                            <small>{{ $row['date'] }}</small>
                        </header>

                        <div class="rm-stat-patient-meta">
                            <span>{{ $row['branch'] }}</span>
                            <span>{{ $row['status'] }}</span>
                        </div>

                        <div class="rm-stat-patient-treatments">
                            <div>
                                <small>Tratamiento</small>
                                <p>{{ $row['service'] }}</p>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rm-empty-state">
                        <strong>Sin resultados</strong>
                        <span>No hay consultas para este profesional con el tratamiento seleccionado.</span>
                    </div>
                @endforelse
            </div>
        </section>
    @endif

    @if ($showNewPatientsModal)
        <div class="rm-modal-backdrop" wire:click="closeNewPatientsModal"></div>
        <section class="rm-modal-panel rm-stat-patient-modal" role="dialog" aria-modal="true">
            <div class="rm-modal-title">
                <div>
                    <span>Pacientes nuevos</span>
                    <h2>Registrados del {{ $dateLabel }}</h2>
                </div>
                <button type="button" wire:click="closeNewPatientsModal">x</button>
            </div>

            <div class="rm-stat-patient-export-actions">
                <button class="rm-button rm-button-primary" type="button" wire:click="exportNewPatientsExcel">
                    Exportar Excel
                </button>
                <button class="rm-button rm-button-outline" type="button" wire:click="exportNewPatientsPdf">
                    Exportar PDF
                </button>
            </div>

            <div class="rm-stat-patient-list">
                @forelse ($newPatientRows as $patient)
                    <article class="rm-stat-patient-card">
                        <header>
                            <div>
                                <strong>{{ $patient['name'] }}</strong>
                                <span>{{ $patient['phone'] ?: 'Sin telefono' }}</span>
                            </div>
                            <small>{{ $patient['registered_at'] }}</small>
                        </header>

                        <div class="rm-stat-patient-meta">
                            <span>{{ $patient['branch'] }}</span>
                            <span>{{ count($patient['appointments']) }} cita(s) en el rango</span>
                        </div>

                        <div class="rm-stat-patient-treatments">
                            @forelse ($patient['appointments'] as $appointment)
                                <div>
                                    <small>{{ $appointment['date'] }} - {{ $appointment['status'] }}</small>
                                    @if (count($appointment['services']) > 0)
                                        <p>{{ implode(', ', $appointment['services']) }}</p>
                                    @else
                                        <p>Sin tratamientos registrados.</p>
                                    @endif
                                </div>
                            @empty
                                <div>
                                    <small>Sin citas en el rango</small>
                                    <p>Paciente registrado sin tratamientos realizados todavia.</p>
                                </div>
                            @endforelse
                        </div>
                    </article>
                @empty
                    <div class="rm-empty-state">
                        <strong>Sin pacientes nuevos</strong>
                        <span>No hay pacientes registrados en el rango seleccionado.</span>
                    </div>
                @endforelse
            </div>
        </section>
    @endif
</div>
