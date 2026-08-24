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
                        <article class="rm-commerce-row rm-product-result {{ $stock <= 0 ? 'is-stock-empty' : '' }}">
                            <button class="rm-product-photo-button" type="button" wire:click="previewProductImage({{ $product->id }})" title="Ver imagen">
                                @if ($product->image_path)
                                    <img src="{{ asset('storage/'.$product->image_path) }}" alt="{{ $product->name }}">
                                @else
                                    <span>{{ strtoupper(substr($product->name, 0, 1)) }}</span>
                                @endif
                            </button>
                            <div class="rm-row-main">
                                <strong>{{ $product->name }}</strong>
                                <span>{{ $product->code }} - {{ $product->brand?->name ?? 'Sin marca' }} - {{ $product->useArea?->name ?? 'Sin zona' }} - Stock {{ number_format($stock, 2) }} {{ $product->unit_name }}</span>
                            </div>
                            <span class="rm-soft-pill">{{ $stock <= 0 ? 'Sin stock' : ($product->useArea?->name ?? 'Sin area') }}</span>
                            <button class="rm-button rm-button-outline rm-product-add-button" type="button" wire:click="addProduct({{ $product->id }})">Agregar</button>
                        </article>
                    @endforeach
                </div>
            @endif

            <div class="rm-sale-lines">
                @forelse ($lines as $index => $line)
                    @php
                        $lineQuantity = (float) ($line['quantity'] ?: 0);
                        $lineAvailable = (float) ($line['available'] ?? 0);
                        $lineSaleMode = $line['sale_mode'] ?? 'unit';
                        $lineStockQuantity = $lineSaleMode === 'container'
                            ? $lineQuantity * max(0.01, (float) ($line['content_quantity'] ?? 1))
                            : $lineQuantity;
                        $hasStockShortage = $lineStockQuantity > $lineAvailable;
                        $lineUnitLabel = $lineSaleMode === 'container'
                            ? 'frasco(s)'
                            : ($lineSaleMode === 'volume' ? ($line['content_unit_name'] ?? 'ml') : ($line['unit_name'] ?? 'unidad'));
                    @endphp
                    <div class="rm-sale-line {{ $hasStockShortage ? 'is-stock-short' : '' }}">
                        <button class="rm-product-photo-button" type="button" wire:click="previewProductImage({{ $line['product_id'] }})" title="Ver imagen">
                            @if (! empty($line['image_path']))
                                <img src="{{ asset('storage/'.$line['image_path']) }}" alt="{{ $line['name'] }}">
                            @else
                                <span>{{ strtoupper(substr($line['name'], 0, 1)) }}</span>
                            @endif
                        </button>
                        <div>
                            <strong>{{ $line['name'] }}</strong>
                            <span>{{ $line['code'] }} - {{ $line['brand'] }} - {{ $line['area'] }} - {{ $line['lot'] }} - Stock {{ number_format((float) $line['available'], 2) }} {{ $line['unit_name'] ?? 'unidad' }}</span>
                        </div>
                        @if (($line['sale_unit_type'] ?? 'unit') === 'mixed')
                            <label class="rm-field">
                                <span>Vender como</span>
                                <select wire:model.live="lines.{{ $index }}.sale_mode">
                                    <option value="volume">Por {{ $line['content_unit_name'] ?? 'ml' }}</option>
                                    <option value="container">Frasco completo</option>
                                </select>
                            </label>
                        @elseif (($line['sale_unit_type'] ?? 'unit') === 'volume')
                            <div class="rm-sale-unit-note">
                                <strong>Por {{ $line['content_unit_name'] ?? 'ml' }}</strong>
                                <span>Descuenta directo del contenido.</span>
                            </div>
                        @endif
                        <label class="rm-field">
                            <span>Cantidad {{ $lineUnitLabel }}</span>
                            <input wire:model.live="lines.{{ $index }}.quantity" type="number" min="0.01" step="0.01">
                            @if ($lineSaleMode === 'container')
                                <small>Descuenta {{ number_format($lineStockQuantity, 2) }} {{ $line['unit_name'] ?? 'ml' }}</small>
                            @endif
                        </label>
                        <label class="rm-field"><span>Precio</span><input wire:model.live="lines.{{ $index }}.unit_price" type="number" min="0" step="0.01"></label>
                        <label class="rm-field"><span>Motivo faltante</span><input wire:model="lines.{{ $index }}.missing_reason" type="text" placeholder="{{ $hasStockShortage ? 'Obligatorio por falta de stock' : 'Si no alcanza stock' }}">@error("lines.$index.missing_reason") <small>{{ $message }}</small> @enderror</label>
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
                <input wire:model.live="invoiceRequested" type="checkbox">
                <span>
                    <strong>Solicita factura</strong>
                    <small>Pedira NIT y nombre fiscal antes de guardar.</small>
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
                    <div class="rm-commerce-actions">
                        <button class="rm-button rm-button-outline" type="button" wire:click="previewProductSaleTicket({{ $sale->id }})">Ticket</button>
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

    @if ($showTicketPreview)
        <div class="rm-modal-backdrop" wire:click="closeTicketPreview"></div>
        <section class="rm-modal-panel rm-modal-panel-wide rm-print-preview-modal" role="dialog" aria-modal="true">
            <div class="rm-modal-title">
                <div>
                    <span>Previsualizacion</span>
                    <h2>{{ $ticketPreview['title'] ?? 'Ticket de venta' }}</h2>
                    <p class="rm-modal-subtitle">{{ $ticketPreview['branch'] ?? $branch->name }} - {{ $ticketPreview['business_date'] ?? '' }}</p>
                </div>
                <button type="button" wire:click="closeTicketPreview" aria-label="Cerrar">x</button>
            </div>

            <div class="rm-print-preview-paper">
                <div class="rm-print-header">
                    <strong>{{ $ticketPreview['branch'] ?? $branch->name }}</strong>
                    <span>{{ $ticketPreview['ticket_number'] ?? 'Ticket sin numero' }}</span>
                    <span>{{ $ticketPreview['business_date'] ?? '' }}</span>
                    <span>Comprador: {{ $ticketPreview['buyer'] ?? 'Consumidor final' }}</span>
                    <span>NIT: {{ $ticketPreview['buyer_nit'] ?? 'Sin NIT' }}</span>
                    <span>Vendido por: {{ $ticketPreview['sold_by'] ?? 'Sin vendedor' }}</span>
                </div>

                <div class="rm-print-section">
                    <h3>Productos</h3>
                    <div class="rm-print-table">
                        <div class="rm-print-row rm-print-row-head"><span>Producto</span><span>Cant.</span><span>Total</span></div>
                        @foreach (($ticketPreview['rows'] ?? []) as $row)
                            <div class="rm-print-row">
                                <span>{{ \Illuminate\Support\Str::limit($row['name'], 32, '') }}</span>
                                <span>{{ number_format($row['quantity'], 2) }} {{ $row['unit_name'] ?? '' }}</span>
                                <span>{{ $ticketPreview['currency_symbol'] ?? \App\Support\Money::symbol() }} {{ number_format($row['total'], 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rm-print-totals">
                    <span>Efectivo {{ $ticketPreview['currency_symbol'] ?? \App\Support\Money::symbol() }} {{ number_format($ticketPreview['totals']['cash'] ?? 0, 2) }}</span>
                    <span>QR {{ $ticketPreview['currency_symbol'] ?? \App\Support\Money::symbol() }} {{ number_format($ticketPreview['totals']['qr'] ?? 0, 2) }}</span>
                    <strong>Total {{ $ticketPreview['currency_symbol'] ?? \App\Support\Money::symbol() }} {{ number_format($ticketPreview['totals']['total'] ?? 0, 2) }}</strong>
                    @if (! empty($ticketPreview['printer_enabled']))<span>Impresora {{ $ticketPreview['printer_name'] ?: 'sin seleccionar' }}</span>@endif
                </div>
            </div>

            <div class="rm-form-actions">
                <button
                    class="rm-button rm-button-primary rm-auto-print-ticket"
                    type="button"
                    wire:click="markTicketPrinted"
                    data-use-qz="{{ ! empty($ticketPreview['printer_enabled']) && ! empty($ticketPreview['printer_name']) ? '1' : '0' }}"
                    data-printer-name="{{ $ticketPreview['printer_name'] ?? '' }}"
                    data-ticket="{{ base64_encode($ticketPreview['raw_ticket'] ?? '') }}"
                    onclick="event.preventDefault(); window.RumikaQz.printFromButton(this)"
                >Imprimir ahora</button>
                <button class="rm-button rm-button-outline" type="button" wire:click="closeTicketPreview">Volver</button>
            </div>
        </section>
    @endif

    @if ($showProductImageModal)
        <div class="rm-modal-backdrop" wire:click="closeProductImagePreview"></div>
        <section class="rm-modal-panel rm-modal-panel-small rm-product-image-modal" role="dialog" aria-modal="true">
            <div class="rm-modal-title">
                <div><span>Producto</span><h2>{{ $previewProductName }}</h2></div>
                <button type="button" wire:click="closeProductImagePreview" aria-label="Cerrar">x</button>
            </div>
            <div class="rm-product-image-large">
                @if ($previewProductImagePath)
                    <img src="{{ asset('storage/'.$previewProductImagePath) }}" alt="{{ $previewProductName }}">
                @else
                    <span>{{ strtoupper(substr($previewProductName ?: 'P', 0, 1)) }}</span>
                @endif
            </div>
        </section>
    @endif
</div>
