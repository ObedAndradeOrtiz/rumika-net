<div class="rm-content rm-report-page">
    <section class="rm-panel rm-report-head">
        <div>
            <span class="rm-kicker">Gerencia</span>
            <h1>Reportes del negocio</h1>
            <p>Resumen general y por sucursal con ingresos, egresos, deudas, asistencia y comisiones.</p>
        </div>
        <a class="rm-button rm-button-primary" href="{{ $pdfUrl }}" target="_blank">Exportar PDF</a>
    </section>

    <section class="rm-panel rm-report-filters">
        <div class="rm-filter-row">
            <label class="rm-field"><span>Desde</span><input wire:model.live="dateFrom" type="date"></label>
            <label class="rm-field"><span>Hasta</span><input wire:model.live="dateTo" type="date"></label>
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

    <section class="rm-kpi-strip rm-report-kpis">
        <div class="rm-kpi"><strong>{{ $currency }} {{ number_format((float) $kpis['income'], 2) }}</strong><span>Ingresos</span></div>
        <div class="rm-kpi"><strong>{{ $currency }} {{ number_format((float) $kpis['services'], 2) }}</strong><span>Servicios</span></div>
        <div class="rm-kpi"><strong>{{ $currency }} {{ number_format((float) $kpis['products'], 2) }}</strong><span>Productos</span></div>
        <div class="rm-kpi"><strong>{{ $currency }} {{ number_format((float) $kpis['expenses'], 2) }}</strong><span>Egresos</span></div>
        <div class="rm-kpi"><strong>{{ $currency }} {{ number_format((float) $kpis['net'], 2) }}</strong><span>Neto</span></div>
        <div class="rm-kpi"><strong>{{ $currency }} {{ number_format((float) $kpis['debts'], 2) }}</strong><span>Deudas</span></div>
        <div class="rm-kpi"><strong>{{ $currency }} {{ number_format((float) $kpis['commissions'], 2) }}</strong><span>Comisiones</span></div>
        <div class="rm-kpi"><strong>{{ $kpis['attended'] }}/{{ $kpis['appointments'] }}</strong><span>Asistencia</span></div>
    </section>

    <section class="rm-panel rm-report-section">
        <div class="rm-panel-title"><div><h2>Resumen por sucursal</h2><p>{{ $rangeLabel }}</p></div></div>
        <div class="rm-report-table">
            <div class="rm-report-table-head">
                <span>Sucursal</span><span>Servicios</span><span>Productos</span><span>Gastos</span><span>Neto</span><span>Asistencia</span><span>Deudas</span>
            </div>
            @foreach ($branchRows as $row)
                <div class="rm-report-table-row">
                    <strong>{{ $row['name'] }}</strong>
                    <span>{{ $currency }} {{ number_format((float) $row['services'], 2) }}</span>
                    <span>{{ $currency }} {{ number_format((float) $row['products'], 2) }}</span>
                    <span>{{ $currency }} {{ number_format((float) $row['expenses'], 2) }}</span>
                    <span>{{ $currency }} {{ number_format((float) $row['net'], 2) }}</span>
                    <span>{{ $row['attended'] }}/{{ $row['appointments'] }}</span>
                    <span>{{ $currency }} {{ number_format((float) $row['debts'], 2) }}</span>
                </div>
            @endforeach
        </div>
    </section>

    <div class="rm-report-grid">
        <section class="rm-panel rm-report-section">
            <div class="rm-panel-title"><div><h2>Servicios mas vendidos</h2></div></div>
            @forelse ($serviceRows as $row)
                <div class="rm-report-mini-row"><span>{{ $row['name'] }}</span><strong>{{ $currency }} {{ number_format((float) $row['total'], 2) }}</strong><small>{{ $row['count'] }} venta(s)</small></div>
            @empty
                <div class="rm-empty-state">Sin servicios.</div>
            @endforelse
        </section>

        <section class="rm-panel rm-report-section">
            <div class="rm-panel-title"><div><h2>Productos mas vendidos</h2></div></div>
            @forelse ($productRows as $row)
                <div class="rm-report-mini-row"><span>{{ $row['name'] }}</span><strong>{{ $currency }} {{ number_format((float) $row['total'], 2) }}</strong><small>{{ number_format((float) $row['count'], 2) }} unidad(es)</small></div>
            @empty
                <div class="rm-empty-state">Sin productos.</div>
            @endforelse
        </section>

        <section class="rm-panel rm-report-section">
            <div class="rm-panel-title"><div><h2>Rendimiento por personal</h2></div></div>
            @forelse ($staffRows as $row)
                <div class="rm-report-mini-row"><span>{{ $row['name'] }}</span><strong>{{ $currency }} {{ number_format((float) ($row['services'] + $row['products']), 2) }}</strong><small>Comision {{ $currency }} {{ number_format((float) $row['commission'], 2) }}</small></div>
            @empty
                <div class="rm-empty-state">Sin personal.</div>
            @endforelse
        </section>
    </div>
</div>
