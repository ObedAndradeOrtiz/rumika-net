<div class="rm-content rm-dashboard-page">
    <div class="rm-dashboard-hero">
        <div>
            <span>Panel inicial</span>
            <h1>{{ $branch->name }}</h1>
            <p>Resumen operativo de hoy y alertas principales del mes.</p>
        </div>
        <div class="rm-dashboard-branch-pill">
            <span>Sucursal activa</span>
            <strong>{{ $branch->businessType?->name ?? 'Negocio' }}</strong>
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
            <div><strong>Bs {{ number_format($cashbox['net'], 2) }}</strong><span>Caja neta del dia</span></div>
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
            <div><strong>Bs {{ number_format($monthExpenses, 2) }}</strong><span>Gastos del mes</span></div>
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
                    <article class="rm-agenda-row">
                        <span class="rm-agenda-time">{{ $appointment->scheduled_at->format('H:i') }}</span>
                        <div class="rm-row-main">
                            <strong>{{ $appointment->client->full_name }}</strong>
                            <span>{{ $appointment->services->pluck('name')->take(2)->join(' + ') ?: 'Sin tratamientos' }}</span>
                        </div>
                        <span class="rm-status {{ $appointment->attended ? 'ok' : ($appointment->status === 'no_show' ? 'danger' : 'warn') }}">
                            {{ $appointment->attended ? 'Asistio' : ($appointment->status === 'no_show' ? 'No asistio' : 'Pendiente') }}
                        </span>
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
                <div><span>Efectivo</span><strong>Bs {{ number_format($cashbox['cash'], 2) }}</strong></div>
                <div><span>QR</span><strong>Bs {{ number_format($cashbox['qr'], 2) }}</strong></div>
                <div><span>Gastos caja</span><strong>Bs {{ number_format($cashbox['expenses'], 2) }}</strong></div>
                <div class="is-total"><span>Total neto</span><strong>Bs {{ number_format($cashbox['net'], 2) }}</strong></div>
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
                        <div><strong>{{ $expense->type?->name ?? 'Gasto' }} - Bs {{ number_format((float) $expense->amount, 2) }}</strong><small>{{ $expense->spent_at->format('d/m/Y') }} - {{ $expense->source === 'cashbox' ? 'Caja' : 'Externo' }}</small></div>
                    </article>
                @empty
                    <div class="rm-dashboard-empty">Sin gastos registrados este mes.</div>
                @endforelse
            </div>
        </section>
    </div>
</div>
