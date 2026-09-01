<div class="rm-content rm-dashboard-page">
    <div class="rm-dashboard-hero">
        <div>
            <span>Panel inicial</span>
            <h1>{{ $branch->name }}</h1>
            <p>Resumen operativo de hoy y alertas principales del mes.</p>
        </div>
        <div class="rm-dashboard-attendance-card">
            <div class="rm-stat-ring" style="--value: {{ $attendanceRateToday }};">
                <span>{{ $attendanceRateToday }}%</span>
            </div>
            <div>
                <strong>{{ $attendedToday }}/{{ $appointmentsToday->count() }}</strong>
                <span>Asistidos de hoy</span>
            </div>
        </div>
    </div>

    @if (auth()->user()?->tracks_attendance)
        <div class="rm-dashboard-punch-strip">
            <livewire:hr.attendance-punch />
        </div>
    @endif

    @if ($outsideScheduleLocked)
        <section class="rm-panel rm-attendance-lock-panel">
            <div>
                <span class="rm-attendance-lock-icon">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3">
                        <circle cx="12" cy="12" r="9" />
                        <path d="M12 7v5l3 2" />
                    </svg>
                </span>
                <div>
                    <h2>Fuera de horario laboral</h2>
                    <p>Tu usuario no tiene permiso para usar Rumika fuera de su horario configurado. Solicita a administracion activar la excepcion si necesitas ingresar.</p>
                </div>
            </div>
        </section>
    @elseif ($attendanceLocked)
        <section class="rm-panel rm-attendance-lock-panel">
            <div>
                <span class="rm-attendance-lock-icon">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3">
                        <rect x="4" y="11" width="16" height="9" rx="2" />
                        <path d="M8 11V8a4 4 0 0 1 8 0v3" />
                    </svg>
                </span>
                <div>
                    <h2>Registra tu asistencia para usar Rumika</h2>
                    <p>Tu usuario tiene control de asistencia activo. Valida rostro y ubicacion desde tu sucursal para habilitar los modulos del sistema.</p>
                </div>
            </div>
        </section>
    @else

    <section class="rm-dashboard-kpis" aria-label="Resumen por sucursal">
        <article>
            <span class="rm-kpi-icon is-agenda">
                <svg width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M8 3v4M16 3v4M3 10h18"/></svg>
            </span>
            <div><strong>{{ $appointmentsToday->count() }}</strong><span>Citas de hoy</span></div>
        </article>
        <article>
            <span class="rm-kpi-icon is-cash">
                <svg width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="6" width="18" height="12" rx="3"/><path d="M7 12h.01M17 12h.01M12 10a2 2 0 1 0 0 4 2 2 0 0 0 0-4Z"/></svg>
            </span>
            <div><strong>{{ \App\Support\Money::symbol() }} {{ number_format($cashbox['net'], 2) }}</strong><span>Caja neta del dia</span></div>
        </article>
        <article>
            <span class="rm-kpi-icon is-client">
                <svg width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9.5" cy="7" r="4"/><path d="M19 8v6M16 11h6"/></svg>
            </span>
            <div><strong>{{ $newClientsToday }}</strong><span>Clientes nuevos</span></div>
        </article>
        <article>
            <span class="rm-kpi-icon is-stock">
                <svg width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5M12 22V12"/></svg>
            </span>
            <div><strong>{{ $lowStockProducts->count() }}</strong><span>Productos bajo stock</span></div>
        </article>
        <article>
            <span class="rm-kpi-icon is-expire">
                <svg width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
            </span>
            <div><strong>{{ $upcomingExpirations->count() }}</strong><span>Proximos vencimientos</span></div>
        </article>
        <article>
            <span class="rm-kpi-icon is-expense">
                <svg width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M4 19V5a2 2 0 0 1 2-2h12v18l-3-2-3 2-3-2-3 2Z"/><path d="M8 8h8M8 12h5"/></svg>
            </span>
            <div><strong>{{ \App\Support\Money::symbol() }} {{ number_format($monthExpenses, 2) }}</strong><span>Gastos del mes</span></div>
        </article>
        <article>
            <span class="rm-kpi-icon is-professional">
                <svg width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9.5" cy="7" r="4"/><path d="M20 8v8M16 12h8"/></svg>
            </span>
            <div>
                <strong>{{ $topProfessional['name'] ?? 'Sin datos' }}</strong>
                <span>
                    @if ($topProfessional)
                        {{ $topProfessional['count'] }} consulta(s) - {{ $topProfessional['percentage'] }}%
                    @else
                        Profesional con mas consultas
                    @endif
                </span>
            </div>
        </article>
    </section>

    <div class="rm-dashboard-grid">
        <section class="rm-panel rm-dashboard-appointments-panel">
            <div class="rm-panel-title">
                <div>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M8 3v4M16 3v4M3 10h18"/></svg>
                    <h2>Citas de hoy</h2>
                </div>
                <span>{{ now()->format('d/m/Y') }}</span>
            </div>
            <div class="rm-agenda-list">
                @forelse ($appointmentsToday as $appointment)
                    <article class="rm-agenda-row rm-dashboard-agenda-row">
                        <span class="rm-agenda-time">{{ $appointment->scheduled_at->format('H:i') }}</span>
                        <div class="rm-dashboard-agenda-detail">
                            <div class="rm-row-main">
                                <strong>{{ $appointment->client->full_name }}</strong>
                                <span>{{ $appointment->services->pluck('name')->take(2)->join(' + ') ?: 'Sin tratamientos' }}</span>
                            </div>
                            <span class="rm-status {{ $appointment->attended ? 'ok' : ($appointment->status === 'no_show' ? 'danger' : 'warn') }}">
                                {{ $appointment->attended ? 'Asistió' : ($appointment->status === 'no_show' ? 'No asistió' : 'Pendiente') }}
                            </span>
                        </div>
                    </article>
                @empty
                    <div class="rm-dashboard-empty">Sin citas para hoy.</div>
                @endforelse
            </div>
        </section>

        <section class="rm-panel">
            <div class="rm-panel-title">
                <div>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="6" width="18" height="12" rx="3"/><path d="M7 12h.01M17 12h.01M12 10a2 2 0 1 0 0 4 2 2 0 0 0 0-4Z"/></svg>
                    <h2>Caja del dia</h2>
                </div>
                <span>Hoy</span>
            </div>
            <div class="rm-cash-summary-list">
                <div><span>Efectivo</span><strong>{{ \App\Support\Money::symbol() }} {{ number_format($cashbox['cash'], 2) }}</strong></div>
                <div><span>QR</span><strong>{{ \App\Support\Money::symbol() }} {{ number_format($cashbox['qr'], 2) }}</strong></div>
                <div><span>Gastos caja</span><strong>{{ \App\Support\Money::symbol() }} {{ number_format($cashbox['expenses'], 2) }}</strong></div>
                <div class="is-total"><span>Total neto</span><strong>{{ \App\Support\Money::symbol() }} {{ number_format($cashbox['net'], 2) }}</strong></div>
            </div>
        </section>

        <section class="rm-panel">
            <div class="rm-panel-title">
                <div>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 3v18h18"/><path d="M7 15l3-4 4 3 5-7"/></svg>
                    <h2>Asistencia semanal</h2>
                </div>
                <span>Sucursales</span>
            </div>
            <div class="rm-weekly-branch-list">
                @forelse ($weeklyBranchAttendance as $row)
                    <article>
                        <div class="rm-stat-ring rm-stat-ring-small" style="--value: {{ $row['rate'] }};">
                            <span>{{ $row['rate'] }}%</span>
                        </div>
                        <div>
                            <strong>{{ $row['name'] }}</strong>
                            <small>{{ $row['type'] }} - {{ $row['attended'] }}/{{ $row['scheduled'] }} asistidos</small>
                        </div>
                    </article>
                @empty
                    <div class="rm-dashboard-empty">Sin citas esta semana.</div>
                @endforelse
            </div>
        </section>

        <section class="rm-panel">
            <div class="rm-panel-title">
                <div>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/></svg>
                    <h2>Alertas de inventario</h2>
                </div>
                <span>{{ $lowStockProducts->count() + $upcomingExpirations->count() }}</span>
            </div>
            <div class="rm-alert-list">
                @forelse ($lowStockProducts as $product)
                    <article>
                        <span class="rm-alert-dot is-stock"></span>
                        <div><strong>{{ $product->name }}</strong><small>Stock {{ number_format((float) $product->current_stock, 2) }} / Min. {{ $product->minimum_stock ?? 0 }}</small></div>
                    </article>
                @empty
                    <div class="rm-dashboard-empty">Sin productos bajo stock.</div>
                @endforelse
                @foreach ($upcomingExpirations as $batch)
                    <article>
                        <span class="rm-alert-dot is-expire"></span>
                        <div><strong>{{ $batch->product?->name ?? 'Producto' }}</strong><small>Vence {{ $batch->expires_at->format('d/m/Y') }} - Lote {{ $batch->lot_code }}</small></div>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="rm-panel">
            <div class="rm-panel-title">
                <div>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M4 19V5a2 2 0 0 1 2-2h12v18l-3-2-3 2-3-2-3 2Z"/><path d="M8 8h8M8 12h5"/></svg>
                    <h2>Gastos recientes</h2>
                </div>
                <span>Mes</span>
            </div>
            <div class="rm-alert-list">
                @forelse ($recentExpenses as $expense)
                    <article>
                        <span class="rm-alert-dot is-expense"></span>
                        <div><strong>{{ $expense->type?->name ?? 'Gasto' }} - {{ \App\Support\Money::symbol() }} {{ number_format((float) $expense->amount, 2) }}</strong><small>{{ $expense->spent_at->format('d/m/Y') }} - {{ $expense->source === 'cashbox' ? 'Caja' : 'Externo' }}</small></div>
                    </article>
                @empty
                    <div class="rm-dashboard-empty">Sin gastos registrados este mes.</div>
                @endforelse
            </div>
        </section>
    </div>
    @endif
</div>
