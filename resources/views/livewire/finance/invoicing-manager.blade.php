<div class="rm-content rm-invoicing-page">
    <section class="rm-panel rm-invoicing-head">
        <div>
            <span class="rm-kicker">Facturacion</span>
            <h1>Ventas por facturar</h1>
            <p>Revisa servicios y productos marcados para factura antes de crear el documento fiscal.</p>
        </div>
        <div class="rm-invoicing-totals">
            <span>Pendiente <strong>{{ $currency }} {{ number_format((float) $pendingTotal, 2) }}</strong></span>
            <span>Facturado <strong>{{ $currency }} {{ number_format((float) $invoicedTotal, 2) }}</strong></span>
        </div>
    </section>

    <section class="rm-panel rm-invoicing-filters">
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
                    <option value="">Servicios y productos</option>
                    <option value="services">Solo servicios</option>
                    <option value="products">Solo productos</option>
                </select>
            </label>
            <label class="rm-field">
                <span>Estado</span>
                <select wire:model.live="statusFilter">
                    <option value="pending">Pendientes</option>
                    <option value="invoiced">Facturados</option>
                    <option value="">Todos</option>
                </select>
            </label>
        </div>

        <label class="rm-search-field">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Buscar por nombre, NIT, telefono, producto o servicio">
        </label>
    </section>

    <section class="rm-panel rm-invoicing-list">
        @forelse ($rows as $row)
            <article class="rm-invoice-row">
                <div class="rm-invoice-kind {{ $row['kind'] === 'products' ? 'is-product' : '' }}">
                    {{ $row['label'] }}
                </div>
                <div class="rm-row-main">
                    <strong>{{ $row['customer'] }} - {{ $currency }} {{ number_format((float) $row['total'], 2) }}</strong>
                    <span>{{ $row['date'] }} - {{ $row['branch'] }}</span>
                    <div class="rm-commerce-meta">
                        <span>NIT: {{ $row['nit'] }}</span>
                        <span>Efectivo {{ $currency }} {{ number_format((float) $row['cash'], 2) }}</span>
                        <span>QR {{ $currency }} {{ number_format((float) $row['qr'], 2) }}</span>
                        <span>{{ $row['invoice_status'] === 'invoiced' ? 'Facturado' : 'Pendiente' }}</span>
                    </div>
                    <small>{{ $row['detail'] }}</small>
                    @if ($row['invoice_status'] === 'invoiced')
                        <small>Facturado por {{ $row['invoiced_by'] ?: 'Sin usuario' }} {{ $row['invoiced_at'] ? 'el '.$row['invoiced_at'] : '' }}</small>
                    @endif
                </div>
                <div class="rm-commerce-actions">
                    @if ($row['invoice_status'] === 'invoiced')
                        <button class="rm-button rm-button-outline" type="button" wire:click="markAsPending('{{ $row['kind'] }}', {{ $row['id'] }})">Volver a pendiente</button>
                    @else
                        <button class="rm-button rm-button-primary" type="button" wire:click="markAsInvoiced('{{ $row['kind'] }}', {{ $row['id'] }})">Marcar facturado</button>
                    @endif
                </div>
            </article>
        @empty
            <div class="rm-empty-state">
                <strong>Sin ventas pendientes</strong>
                <span>Cuando un cobro o venta directa tenga marcada la opcion de factura aparecera aqui.</span>
            </div>
        @endforelse
    </section>
</div>
