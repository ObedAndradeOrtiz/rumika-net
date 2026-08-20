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
        </div>
    </section>

    <section class="rm-stat-grid">
        <article class="rm-stat-circle-card">
            <div class="rm-stat-ring" style="--value: {{ $attendance['rate'] }};">
                <span>{{ $attendance['rate'] }}%</span>
            </div>
            <div>
                <strong>Asistencia</strong>
                <span>{{ $dateLabel }}</span>
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
            <strong>Bs {{ number_format((float) $finance['income'], 2) }}</strong>
            <div>
                <small>Servicios Bs {{ number_format((float) $finance['services'], 2) }}</small>
                <small>Productos Bs {{ number_format((float) $finance['products'], 2) }}</small>
            </div>
        </article>
        <article class="rm-stat-money-card">
            <span>Egresos</span>
            <strong>Bs {{ number_format((float) $finance['expenses'], 2) }}</strong>
            <div>
                <small>Gastos registrados</small>
                <small>Neto Bs {{ number_format((float) $finance['net'], 2) }}</small>
            </div>
        </article>
        <article class="rm-stat-money-card">
            <span>Metodos de pago</span>
            <strong>Bs {{ number_format((float) $finance['cash'] + (float) $finance['qr'], 2) }}</strong>
            <div>
                <small>Efectivo Bs {{ number_format((float) $finance['cash'], 2) }}</small>
                <small>QR Bs {{ number_format((float) $finance['qr'], 2) }}</small>
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
                    <span>Bs {{ number_format((float) $row['income'], 2) }}</span>
                    <span>Bs {{ number_format((float) $row['expenses'], 2) }}</span>
                    <span>Bs {{ number_format((float) $row['net'], 2) }}</span>
                </article>
            @empty
                <div class="rm-empty-state"><strong>Sin datos</strong><span>No hay movimientos en el rango seleccionado.</span></div>
            @endforelse
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
</div>
