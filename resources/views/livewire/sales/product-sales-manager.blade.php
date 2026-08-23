<div class="rm-content rm-settings-page">
    <div class="rm-sales-compact-head">
        <div class="rm-tab-switcher rm-sales-tabs">
            <button type="button" wire:click="setTab('sale')" class="{{ $tab === 'sale' ? 'is-active' : '' }}">Venta</button>
            <button type="button" wire:click="setTab('buyers')" class="{{ $tab === 'buyers' ? 'is-active' : '' }}">Compradores</button>
        </div>
        <span class="rm-sales-branch-pill">{{ $branch->name }}</span>
    </div>

    @if ($message)
        <div class="rm-panel rm-success-panel">{{ $message }}</div>
    @endif

    @if ($tab === 'sale')
    <section class="rm-commerce-sale-grid">
        <div class="rm-panel rm-catalog-panel">
            <div class="rm-panel-title">
                <div>
                    <h2>Comprador de venta directa</h2>
                    <p>Busca por NIT, telefono o nombre. Esto no se mezcla con pacientes clinicos.</p>
                </div>
            </div>

            <label class="rm-search-field">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input wire:model.live.debounce.300ms="buyerSearch" type="search" placeholder="Buscar comprador por NIT, telefono o nombre">
            </label>

            @if ($buyers->isNotEmpty())
                <div class="rm-commerce-list compact">
                    @foreach ($buyers as $buyer)
                        <button class="rm-commerce-row rm-buyer-result" type="button" wire:click="selectBuyer({{ $buyer->id }})">
                            <div class="rm-row-main">
                                <strong>{{ $buyer->full_name ?: 'Sin nombre' }}</strong>
                                <span>NIT {{ $buyer->nit ?: 'Sin NIT' }} - {{ $buyer->phone ?: 'Sin telefono' }}</span>
                            </div>
                        </button>
                    @endforeach
                </div>
            @elseif (trim($buyerSearch) !== '')
                <div class="rm-empty-state">No existe ese comprador. Puedes llenar sus datos abajo y se guardara si tiene NIT o telefono.</div>
            @endif

            <div class="rm-form-grid two">
                <label class="rm-field"><span>Nombre completo</span><input wire:model="buyerName" type="text" placeholder="Nombre o razon social">@error('buyerName') <small>{{ $message }}</small> @enderror</label>
                <label class="rm-field"><span>NIT</span><input wire:model="buyerNit" type="text" placeholder="NIT / documento">@error('buyerNit') <small>{{ $message }}</small> @enderror</label>
                <label class="rm-field"><span>Telefono</span><input wire:model="buyerPhone" type="text" placeholder="Opcional">@error('buyerPhone') <small>{{ $message }}</small> @enderror</label>
                <label class="rm-field"><span>Email</span><input wire:model="buyerEmail" type="email" placeholder="Opcional">@error('buyerEmail') <small>{{ $message }}</small> @enderror</label>
            </div>

            <div class="rm-panel-title">
                <div>
                    <h2>Productos</h2>
                    <p>Busca por nombre, codigo, marca, zona o lote.</p>
                </div>
            </div>

            <label class="rm-search-field">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input wire:model.live.debounce.300ms="productSearch" type="search" placeholder="Buscar producto por nombre, codigo, marca, zona o lote">
            </label>

            @if ($products->isNotEmpty())
                <div class="rm-commerce-list compact">
                    @foreach ($products as $product)
                        @php $stock = (float) $product->batches->sum('current_quantity'); @endphp
                        <button class="rm-commerce-row rm-product-result" type="button" wire:click="addProduct({{ $product->id }})">
                            <div class="rm-row-main">
                                <strong>{{ $product->name }}</strong>
                                <span>{{ $product->code }} - {{ $product->brand?->name ?? 'Sin marca' }} - {{ $product->useArea?->name ?? 'Sin zona' }} - Stock {{ number_format($stock, 2) }}</span>
                            </div>
                            <span class="rm-soft-pill">{{ $product->useArea?->name ?? 'Sin area' }}</span>
                        </button>
                    @endforeach
                </div>
            @endif

            <div class="rm-sale-lines">
                @forelse ($lines as $index => $line)
                    <div class="rm-sale-line">
                        <div>
                            <strong>{{ $line['name'] }}</strong>
                            <span>{{ $line['code'] }} - {{ $line['brand'] }} - {{ $line['area'] }} - {{ $line['lot'] }} - Stock {{ number_format((float) $line['available'], 2) }}</span>
                        </div>
                        <label class="rm-field"><span>Cantidad</span><input wire:model.live="lines.{{ $index }}.quantity" type="number" min="0.01" step="0.01"></label>
                        <label class="rm-field"><span>Precio</span><input wire:model.live="lines.{{ $index }}.unit_price" type="number" min="0" step="0.01"></label>
                        <label class="rm-field"><span>Motivo faltante</span><input wire:model="lines.{{ $index }}.missing_reason" type="text" placeholder="Si no alcanza stock"></label>
                        <button class="rm-button rm-button-outline" type="button" wire:click="removeLine({{ $index }})">Quitar</button>
                    </div>
                @empty
                    <div class="rm-empty-state">Agrega productos para iniciar la venta.</div>
                @endforelse
            </div>
            @error('lines') <small class="rm-error-text">{{ $message }}</small> @enderror
        </div>

        <aside class="rm-panel rm-sale-summary">
            <div class="rm-panel-title">
                <div>
                    <h2>Resumen de venta</h2>
                    <p>{{ $buyerName ?: 'Consumidor final' }}</p>
                </div>
            </div>

            <div class="rm-total-card">
                <span>Total</span>
                <strong>{{ \App\Support\Money::symbol() }} {{ number_format($this->total, 2) }}</strong>
            </div>

            <label class="rm-field">
                <span>Vendido por</span>
                <select wire:model="soldByUserId">
                    @foreach ($staffUsers as $staff)
                        <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                    @endforeach
                </select>
                @error('soldByUserId') <small>{{ $message }}</small> @enderror
            </label>

            <div class="rm-form-grid two">
                <label class="rm-field"><span>Efectivo</span><input wire:model.live="cashAmount" type="number" min="0" step="0.01">@error('cashAmount') <small>{{ $message }}</small> @enderror</label>
                <label class="rm-field"><span>QR</span><input wire:model.live="qrAmount" type="number" min="0" step="0.01">@error('qrAmount') <small>{{ $message }}</small> @enderror</label>
            </div>

            <label class="rm-check-row rm-template-active-row">
                <input wire:model="invoiceRequested" type="checkbox">
                <span>
                    <strong>Solicita factura</strong>
                    <small>Usa NIT o nombre del comprador si fue registrado.</small>
                </span>
            </label>

            <label class="rm-field"><span>Referencia</span><input wire:model="reference" type="text" placeholder="QR, recibo, nota">@error('reference') <small>{{ $message }}</small> @enderror</label>
            <label class="rm-field"><span>Notas</span><textarea wire:model="notes" rows="3" placeholder="Opcional"></textarea>@error('notes') <small>{{ $message }}</small> @enderror</label>

            <button class="rm-button rm-button-primary" type="button" wire:click="saveSale">Confirmar venta</button>
        </aside>
    </section>

    <section class="rm-panel rm-catalog-panel">
        <div class="rm-panel-title">
            <div>
                <h2>Ventas recientes</h2>
                <p>Ultimas ventas directas de productos en esta sucursal.</p>
            </div>
        </div>
        <div class="rm-commerce-list">
            @forelse ($recentSales as $sale)
                <article class="rm-commerce-row rm-cashbox-record-row">
                    <div class="rm-commerce-icon rm-cashbox-record-icon">{{ strtoupper($sale->method) }}</div>
                    <div class="rm-row-main">
                        <strong>{{ $sale->buyer_name ?: 'Consumidor final' }} - {{ \App\Support\Money::symbol() }} {{ number_format((float) $sale->subtotal, 2) }}</strong>
                        <span>{{ $sale->sold_at->format('d/m/Y H:i') }} - {{ $sale->items->count() }} producto(s)</span>
                        <div class="rm-commerce-meta">
                            <span>Efectivo {{ \App\Support\Money::symbol() }} {{ number_format((float) $sale->cash_amount, 2) }}</span>
                            <span>QR {{ \App\Support\Money::symbol() }} {{ number_format((float) $sale->qr_amount, 2) }}</span>
                            <span>{{ $sale->soldBy?->name ?? 'Sin vendedor' }}</span>
                            <span>{{ $sale->invoice_requested ? 'Para facturar' : 'Sin factura' }}</span>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rm-empty-state">Aun no hay ventas directas de productos.</div>
            @endforelse
        </div>
    </section>
    @else
        <section class="rm-panel rm-catalog-panel">
            <div class="rm-panel-title">
                <div>
                    <h2>Compradores por NIT</h2>
                    <p>Directorio comercial separado de los clientes clinicos.</p>
                </div>
            </div>

            <label class="rm-search-field">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input wire:model.live.debounce.300ms="buyerDirectorySearch" type="search" placeholder="Buscar por NIT, telefono, nombre o email">
            </label>

            <div class="rm-commerce-list rm-buyer-directory-list">
                @forelse ($buyerDirectory as $buyer)
                    <article class="rm-commerce-row rm-buyer-directory-row">
                        <div class="rm-commerce-icon">{{ strtoupper(substr($buyer->full_name ?: ($buyer->nit ?: 'CF'), 0, 2)) }}</div>
                        <div class="rm-row-main">
                            <strong>{{ $buyer->full_name ?: 'Sin nombre' }}</strong>
                            <span>NIT {{ $buyer->nit ?: 'Sin NIT' }} - {{ $buyer->phone ?: 'Sin telefono' }}</span>
                            <div class="rm-commerce-meta">
                                <span>{{ $buyer->email ?: 'Sin email' }}</span>
                                <span>{{ $buyer->status === 'active' ? 'Activo' : 'Inactivo' }}</span>
                            </div>
                        </div>
                        <div class="rm-commerce-actions">
                            <button type="button" wire:click="useBuyerForSale({{ $buyer->id }})">Usar en venta</button>
                        </div>
                    </article>
                @empty
                    <div class="rm-empty-state">Aun no hay compradores guardados. Se crean al vender con NIT o telefono.</div>
                @endforelse
            </div>
        </section>
    @endif
</div>
