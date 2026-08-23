<div class="rm-content rm-report-page">
    <section class="rm-settings-hero">
        <div>
            <span class="rm-kicker">Cuentas por cobrar</span>
            <h1>Deudas de clientes</h1>
            <p>Tratamientos, servicios y productos que todavia tienen saldo pendiente.</p>
        </div>
    </section>

    <section class="rm-kpi-strip rm-report-kpis">
        <div class="rm-kpi"><strong>{{ $currency }} {{ number_format((float) $totalDebt, 2) }}</strong><span>Saldo pendiente</span></div>
        <div class="rm-kpi"><strong>{{ $currency }} {{ number_format((float) $serviceDebt, 2) }}</strong><span>Servicios</span></div>
        <div class="rm-kpi"><strong>{{ $currency }} {{ number_format((float) $productDebt, 2) }}</strong><span>Productos</span></div>
        <div class="rm-kpi"><strong>{{ $rows->count() }}</strong><span>Registros pendientes</span></div>
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
            <label class="rm-field">
                <span>Tipo</span>
                <select wire:model.live="typeFilter">
                    <option value="">Todos</option>
                    <option value="service">Servicios</option>
                    <option value="product">Productos</option>
                </select>
            </label>
        </div>
        <label class="rm-search-field">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Buscar cliente, telefono, CI o detalle">
        </label>
    </section>

    <section class="rm-panel rm-report-list">
        @forelse ($rows as $row)
            <article class="rm-report-row">
                <div class="rm-report-row-kind {{ $row['type'] === 'product' ? 'is-product' : '' }}">{{ $row['type'] === 'product' ? 'Producto' : 'Servicio' }}</div>
                <div class="rm-row-main">
                    <strong>{{ $row['client'] }}</strong>
                    <span>{{ $row['name'] }} - {{ $row['date'] }} - {{ $row['phone'] }}</span>
                    <div class="rm-commerce-meta">
                        <span>Total {{ $currency }} {{ number_format((float) $row['total'], 2) }}</span>
                        <span>Pagado {{ $currency }} {{ number_format((float) $row['paid'], 2) }}</span>
                        <span>Saldo {{ $currency }} {{ number_format((float) $row['balance'], 2) }}</span>
                        <span>{{ $row['status'] }}</span>
                        <span>{{ $row['responsible'] }}</span>
                    </div>
                </div>
            </article>
        @empty
            <div class="rm-empty-state"><strong>Sin deudas</strong><span>No hay saldos pendientes para este filtro.</span></div>
        @endforelse
    </section>
</div>
