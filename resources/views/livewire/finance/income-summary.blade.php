<div class="rm-content rm-finance-summary-page">
    <div class="rm-settings-hero">
        <div>
            <span>Gestion financiera</span>
            <h1>Resumen de ingresos</h1>
            <p>Vista administrativa de ingresos por servicios, productos y gastos en todas las sucursales del negocio.</p>
        </div>
        <div class="rm-settings-summary">
            <strong>{{ \App\Support\Money::symbol() }} {{ number_format((float) $totals['net'], 2) }}</strong>
            <span>Neto general</span>
        </div>
    </div>

    <section class="rm-panel">
        <div class="rm-filter-row rm-finance-range-row">
            <label class="rm-field">
                <span>Desde</span>
                <input wire:model.live="dateFrom" type="date">
                @error('dateFrom') <small>{{ $message }}</small> @enderror
            </label>
            <label class="rm-field">
                <span>Hasta</span>
                <input wire:model.live="dateTo" type="date">
                @error('dateTo') <small>{{ $message }}</small> @enderror
            </label>
            <div class="rm-finance-range-label">
                <span>Periodo seleccionado</span>
                <strong>{{ $dateLabel }}</strong>
            </div>
        </div>
    </section>

    <div class="rm-kpi-strip rm-finance-summary-kpis">
        <div class="rm-kpi"><strong>{{ \App\Support\Money::symbol() }} {{ number_format((float) $totals['services'], 2) }}</strong><span>Ingresos servicios</span></div>
        <div class="rm-kpi"><strong>{{ \App\Support\Money::symbol() }} {{ number_format((float) $totals['products'], 2) }}</strong><span>Ingresos productos</span></div>
        <div class="rm-kpi"><strong>{{ \App\Support\Money::symbol() }} {{ number_format((float) $totals['expenses'], 2) }}</strong><span>Gastos</span></div>
        <div class="rm-kpi"><strong>{{ \App\Support\Money::symbol() }} {{ number_format((float) $totals['income'], 2) }}</strong><span>Ingreso bruto</span></div>
        <div class="rm-kpi"><strong>{{ \App\Support\Money::symbol() }} {{ number_format((float) $totals['cash'], 2) }}</strong><span>Efectivo</span></div>
        <div class="rm-kpi"><strong>{{ \App\Support\Money::symbol() }} {{ number_format((float) $totals['qr'], 2) }}</strong><span>QR</span></div>
    </div>

    <section class="rm-panel">
        <div class="rm-panel-title">
            <div>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 3v18h18"/><path d="m7 15 4-4 3 3 5-7"/></svg>
                <h2>Resumen por sucursal</h2>
            </div>
        </div>

        <div class="rm-finance-summary-table">
            <div class="rm-finance-summary-head">
                <span>Sucursal</span>
                <span>Servicios</span>
                <span>Productos</span>
                <span>Gastos</span>
                <span>Ingreso bruto</span>
                <span>Neto</span>
                <span>Movimientos</span>
            </div>

            @forelse ($branchRows as $row)
                <article class="rm-finance-summary-row">
                    <div>
                        <strong>{{ $row['name'] }}</strong>
                        <span>{{ $row['type'] }}</span>
                    </div>
                    <span>{{ \App\Support\Money::symbol() }} {{ number_format((float) $row['services'], 2) }}</span>
                    <span>{{ \App\Support\Money::symbol() }} {{ number_format((float) $row['products'], 2) }}</span>
                    <span>{{ \App\Support\Money::symbol() }} {{ number_format((float) $row['expenses'], 2) }}</span>
                    <span>{{ \App\Support\Money::symbol() }} {{ number_format((float) $row['income'], 2) }}</span>
                    <strong class="{{ $row['net'] < 0 ? 'is-negative' : 'is-positive' }}">{{ \App\Support\Money::symbol() }} {{ number_format((float) $row['net'], 2) }}</strong>
                    <small>{{ $row['payments_count'] }} cobro(s) / {{ $row['expenses_count'] }} gasto(s)</small>
                </article>
            @empty
                <div class="rm-empty-state">
                    <strong>Sin sucursales</strong>
                    <span>Aun no existen sucursales para resumir.</span>
                </div>
            @endforelse
        </div>
    </section>
</div>
