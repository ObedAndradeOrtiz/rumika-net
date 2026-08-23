<div class="rm-content rm-settings-page">
    <div class="rm-settings-hero">
        <div>
            <span>Gestion de inventario</span>
            <h1>{{ $screen === 'operations' ? 'Operaciones de inventario' : 'Inventario de productos y activos' }}</h1>
            <p>{{ $screen === 'operations' ? 'Registra uso interno de gabinete, traspasos entre sucursales y desechos con explicacion.' : 'Control por sucursal con lotes, vencimientos, entradas, activos, reparaciones y cierres de inventario.' }}</p>
        </div>
        <div class="rm-settings-summary">
            <strong>{{ \App\Support\Money::symbol() }} {{ number_format($summary['stock_value'], 2) }}</strong>
            <span>Valor actual en productos</span>
        </div>
    </div>

    <div class="rm-kpi-strip rm-inventory-kpis">
        <div class="rm-kpi"><strong>{{ \App\Support\Money::symbol() }} {{ number_format($summary['asset_value'], 2) }}</strong><span>Activos vigentes</span></div>
        <div class="rm-kpi"><strong>{{ \App\Support\Money::symbol() }} {{ number_format($summary['loss_value'], 2) }}</strong><span>Perdidas/desechos</span></div>
        <div class="rm-kpi"><strong>{{ \App\Support\Money::symbol() }} {{ number_format($summary['repair_value'], 2) }}</strong><span>Reparaciones</span></div>
        <div class="rm-kpi"><strong>{{ $currentCount->status === 'in_process' ? 'En proceso' : 'Cerrado' }}</strong><span>{{ $currentCount->name }}</span></div>
    </div>

    <section class="rm-panel rm-catalog-panel">
        <div class="rm-tab-switcher {{ $screen === 'operations' ? '' : 'rm-tab-switcher-five' }}" role="tablist" aria-label="Inventario">
            @if ($screen === 'catalog')
                <button class="{{ $activeTab === 'products' ? 'is-active' : '' }}" type="button" wire:click="setActiveTab('products')">Productos</button>
                <button class="{{ $activeTab === 'suppliers' ? 'is-active' : '' }}" type="button" wire:click="setActiveTab('suppliers')">Proveedores</button>
                <button class="{{ $activeTab === 'movements' ? 'is-active' : '' }}" type="button" wire:click="setActiveTab('movements')">Movimientos</button>
                <button class="{{ $activeTab === 'assets' ? 'is-active' : '' }}" type="button" wire:click="setActiveTab('assets')">Activos</button>
                <button class="{{ $activeTab === 'counts' ? 'is-active' : '' }}" type="button" wire:click="setActiveTab('counts')">Cierres</button>
            @else
                <button class="{{ $activeTab === 'movements' ? 'is-active' : '' }}" type="button" wire:click="setActiveTab('movements')">Operaciones</button>
                <button class="{{ $activeTab === 'waste' ? 'is-active' : '' }}" type="button" wire:click="setActiveTab('waste')">Desechos</button>
            @endif
        </div>

        @if (in_array($activeTab, ['products', 'assets'], true))
            <label class="rm-search-field">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input wire:model.live.debounce.300ms="search" type="search" placeholder="Buscar por nombre o codigo">
            </label>
        @endif
        @if ($activeTab === 'products')
            <div class="rm-filter-row rm-inventory-filter-row">
                <label class="rm-field">
                    <span>Marca</span>
                    <select wire:model.live="brandFilter">
                        <option value="">Todas las marcas</option>
                        @foreach ($brands as $brand)
                            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="rm-field">
                    <span>Zona</span>
                    <select wire:model.live="useAreaFilter">
                        <option value="">Todas las zonas</option>
                        @foreach ($useAreas as $area)
                            <option value="{{ $area->id }}">{{ $area->name }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
        @endif

        @if ($activeTab === 'products')
            <div class="rm-panel-title">
                <div>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="M12 22V12"/></svg>
                    <h2>Productos por lote</h2>
                </div>
                <div class="rm-action-row">
                    <button class="rm-button rm-button-primary" type="button" wire:click="createProduct">Nuevo producto</button>
                </div>
            </div>

            <div class="rm-action-row">
                <button class="rm-button rm-button-primary" type="button" wire:click="openMovement('purchase')">Entrada</button>
            </div>

            <div class="rm-commerce-list">
                @forelse ($products as $product)
                    <article class="rm-commerce-row">
                        <div class="rm-commerce-icon"><svg width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/></svg></div>
                        <div class="rm-row-main">
                            <strong>{{ $product->name }}</strong>
                            <span>{{ $product->code }} - Stock {{ number_format((float) ($product->current_stock ?? 0), 2) }} {{ $product->unit_name }}</span>
                            <div class="rm-commerce-meta">
                                <span>{{ $product->brand?->name ?? 'Sin marca' }}</span>
                                <span>{{ $product->supplier?->name ?? 'Sin proveedor' }}</span>
                                <span>{{ $product->useArea?->name ?? 'Sin area de uso' }}</span>
                                <span>{{ $product->package_name ? $product->package_name.' x '.$product->units_per_package : 'Unidad simple' }}</span>
                            </div>
                        </div>
                        <div class="rm-commerce-actions">
                            <button type="button" wire:click="openProductMovements({{ $product->id }})">Movimientos</button>
                            <button type="button" wire:click="editProduct({{ $product->id }})">Editar</button>
                            <button type="button" wire:click="confirmDeleteProduct({{ $product->id }})">Eliminar</button>
                        </div>
                    </article>
                @empty
                    <div class="rm-empty-state"><strong>Sin productos</strong><span>Crea productos y registra entradas con lote para empezar.</span></div>
                @endforelse
            </div>
            <div class="rm-pagination-wrap">{{ $products->links() }}</div>
        @endif

        @if ($activeTab === 'suppliers')
            <div class="rm-panel-title">
                <div>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 7h18"/><path d="M6 7v12h12V7"/><path d="M9 11h6"/></svg>
                    <h2>Proveedores, marcas y areas</h2>
                </div>
                <div class="rm-action-row">
                    @if ($supplierTab === 'areas')
                        <button class="rm-button rm-button-primary" type="button" wire:click="createUseArea">Nueva area</button>
                    @elseif ($supplierTab === 'brands')
                        <button class="rm-button rm-button-primary" type="button" wire:click="createBrand">Nueva marca</button>
                    @else
                        <button class="rm-button rm-button-primary" type="button" wire:click="createSupplier">Nuevo proveedor</button>
                    @endif
                </div>
            </div>

            <div class="rm-tab-switcher rm-inventory-subtabs" role="tablist" aria-label="Catalogos de inventario">
                <button class="{{ $supplierTab === 'suppliers' ? 'is-active' : '' }}" type="button" wire:click="setSupplierTab('suppliers')">
                    Proveedores
                    <span>{{ $suppliers->count() }}</span>
                </button>
                <button class="{{ $supplierTab === 'brands' ? 'is-active' : '' }}" type="button" wire:click="setSupplierTab('brands')">
                    Marcas
                    <span>{{ $brands->count() }}</span>
                </button>
                <button class="{{ $supplierTab === 'areas' ? 'is-active' : '' }}" type="button" wire:click="setSupplierTab('areas')">
                    Areas de uso
                    <span>{{ $useAreas->count() }}</span>
                </button>
            </div>

            <section class="rm-panel rm-nested-panel rm-inventory-subpanel">
                @if ($supplierTab === 'suppliers')
                    <div class="rm-commerce-list">
                        @forelse ($suppliers as $supplier)
                            <article class="rm-commerce-row">
                                <div class="rm-commerce-icon"><svg width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 7h18"/><path d="M6 7v12h12V7"/></svg></div>
                                <div class="rm-row-main">
                                    <strong>{{ $supplier->name }}</strong>
                                    <span>{{ $supplier->contact_name ?? 'Sin contacto' }}</span>
                                    <div class="rm-commerce-meta">
                                        <span>{{ $supplier->phone ?? 'Sin telefono' }}</span>
                                        <span>{{ $supplier->email ?? 'Sin correo' }}</span>
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="rm-empty-state"><strong>Sin proveedores</strong><span>Registra proveedores para relacionarlos con productos y marcas.</span></div>
                        @endforelse
                    </div>
                @elseif ($supplierTab === 'brands')
                    <div class="rm-commerce-list">
                        @forelse ($brands as $brand)
                            <article class="rm-commerce-row">
                                <div class="rm-commerce-icon"><svg width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 2 4 5v6c0 5 3.5 9 8 11 4.5-2 8-6 8-11V5z"/></svg></div>
                                <div class="rm-row-main">
                                    <strong>{{ $brand->name }}</strong>
                                    <span>{{ $brand->supplier?->name ?? 'Sin proveedor fijo' }}</span>
                                    <div class="rm-commerce-meta">
                                        <span>{{ ucfirst($brand->status) }}</span>
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="rm-empty-state"><strong>Sin marcas</strong><span>Las marcas pueden asociarse a un proveedor.</span></div>
                        @endforelse
                    </div>
                @else
                    <div class="rm-commerce-list">
                        @forelse ($useAreas as $area)
                            <article class="rm-commerce-row">
                                <div class="rm-commerce-icon"><svg width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M4 6h16M4 12h16M4 18h16"/><path d="M8 4v4M16 10v4M11 16v4"/></svg></div>
                                <div class="rm-row-main">
                                    <strong>{{ $area->name }}</strong>
                                    <span>{{ $area->description ?? 'Sin descripcion' }}</span>
                                    <div class="rm-commerce-meta">
                                        <span>{{ $area->status === 'active' ? 'Activo' : 'Inactivo' }}</span>
                                    </div>
                                </div>
                                <div class="rm-commerce-actions">
                                    <button type="button" wire:click="editUseArea({{ $area->id }})">Editar</button>
                                    <button type="button" wire:click="confirmDeleteUseArea({{ $area->id }})">Eliminar</button>
                                </div>
                            </article>
                        @empty
                            <div class="rm-empty-state"><strong>Sin areas de uso</strong><span>Crea areas como venta, gabinete, limpieza o consumo interno.</span></div>
                        @endforelse
                    </div>
                @endif
            </section>
        @endif

        @if (in_array($activeTab, ['movements', 'waste'], true))
            <div class="rm-panel-title">
                <div>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="m3 17 6-6 4 4 8-8"/><path d="M14 7h7v7"/></svg>
                    <h2>{{ $activeTab === 'waste' ? 'Historial de desechos' : 'Movimientos del inventario' }}</h2>
                </div>
                <div class="rm-export-controls rm-movement-export-controls">
                    <label class="rm-field rm-movement-search-field">
                        <span>Producto</span>
                        <div class="rm-search-field">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                            <input wire:model.live.debounce.300ms="movementListSearch" type="search" placeholder="Buscar producto, codigo o lote">
                        </div>
                    </label>
                    <label class="rm-field">
                        <span>Desde</span>
                        <input type="date" wire:model="movementExportFrom">
                    </label>
                    <label class="rm-field">
                        <span>Hasta</span>
                        <input type="date" wire:model="movementExportTo">
                    </label>
                    <button class="rm-button rm-button-success rm-export-excel-button" type="button" wire:click="exportMovements">Descargar Excel</button>
                </div>
            </div>

            @if ($screen === 'operations' && $activeTab === 'movements')
                <div class="rm-action-row">
                    <button class="rm-button rm-button-primary" type="button" wire:click="openMovement('cabinet')">Gabinete</button>
                    <button class="rm-button rm-button-outline" type="button" wire:click="openMovement('stock_out')">Salida</button>
                    <button class="rm-button rm-button-outline" type="button" wire:click="openMovement('transfer')">Traspaso</button>
                    <button class="rm-button rm-button-outline" type="button" wire:click="openMovement('waste')">Desecho</button>
                    <button class="rm-button rm-button-outline" type="button" wire:click="openMovement('adjustment')">Ajuste</button>
                </div>
            @endif

            <div class="rm-commerce-list">
                @forelse ($movements as $movement)
                    <article class="rm-commerce-row rm-movement-row">
                        <div class="rm-commerce-icon"><svg width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M7 7h10v10H7z"/><path d="M3 3h4v4M17 3h4v4M17 17h4v4M3 17h4v4"/></svg></div>
                        <div class="rm-row-main">
                            <strong>{{ $movement->product?->name }} - {{ $this->movementLabel($movement->type) }}</strong>
                            <span>{{ $movement->moved_at?->format('d/m/Y H:i') }} - {{ number_format((float) $movement->quantity, 2) }} unidad(es) - {{ \App\Support\Money::symbol() }} {{ number_format((float) $movement->total_cost, 2) }}</span>
                            <div class="rm-commerce-meta">
                                <span>Lote {{ $movement->batch?->lot_code ?? 'N/A' }}</span>
                                <span>{{ $movement->relatedBranch?->name ?? $branch->name }}</span>
                                @if ($movement->reason)<span>{{ $movement->reason }}</span>@endif
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rm-empty-state"><strong>Sin movimientos</strong><span>Las entradas, ventas, traspasos y desechos apareceran aqui.</span></div>
                @endforelse
            </div>
            <div class="rm-pagination-wrap">{{ $movements->links() }}</div>
        @endif

        @if ($activeTab === 'assets')
            <div class="rm-panel-title">
                <div>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/><path d="M9 21v-6h6v6"/></svg>
                    <h2>Inventario de activos</h2>
                </div>
                <button class="rm-button rm-button-primary" type="button" wire:click="createAsset">Nuevo activo</button>
            </div>

            <div class="rm-commerce-list">
                @forelse ($assets as $asset)
                    <article class="rm-commerce-row">
                        <div class="rm-commerce-icon"><svg width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/></svg></div>
                        <div class="rm-row-main">
                            <strong>{{ $asset->name }}</strong>
                            <span>{{ $asset->code }} - Compra {{ \App\Support\Money::symbol() }} {{ number_format((float) $asset->purchase_amount, 2) }} - Reparacion {{ \App\Support\Money::symbol() }} {{ number_format((float) $asset->repair_total, 2) }}</span>
                            <div class="rm-commerce-meta">
                                <span>{{ ucfirst($asset->status) }}</span>
                                <span>{{ $asset->category ?? 'Sin categoria' }}</span>
                                @if ($asset->waste_reason)<span>{{ $asset->waste_reason }}</span>@endif
                            </div>
                        </div>
                        <div class="rm-commerce-actions">
                            <button type="button" wire:click="editAsset({{ $asset->id }})">Editar</button>
                            <button type="button" wire:click="openRepair({{ $asset->id }})">Reparar</button>
                            <button type="button" wire:click="openWasteAsset({{ $asset->id }})">Desechar</button>
                        </div>
                    </article>
                @empty
                    <div class="rm-empty-state"><strong>Sin activos</strong><span>Registra equipos, camillas, maquinas, muebles y herramientas.</span></div>
                @endforelse
            </div>
            <div class="rm-pagination-wrap">{{ $assets->links() }}</div>
        @endif

        @if ($activeTab === 'counts')
            <div class="rm-panel-title">
                <div>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M8 3h8v4H8z"/><path d="M6 7h12v14H6z"/><path d="M9 12h6M9 16h6"/></svg>
                    <h2>Cierres por sucursal</h2>
                </div>
                <button class="rm-button rm-button-danger" type="button" wire:click="confirmCloseInventory">Cerrar inventario actual</button>
            </div>

            <div class="rm-inventory-open-row">
                <label class="rm-field">
                    <span>Abrir inventario por zona</span>
                    <select wire:model="countUseAreaId">
                        <option value="">Inventario general de la sucursal</option>
                        @foreach ($useAreas as $area)
                            <option value="{{ $area->id }}">{{ $area->name }}</option>
                        @endforeach
                    </select>
                    @error('countUseAreaId')<small>{{ $message }}</small>@enderror
                </label>
                <button class="rm-button rm-button-primary" type="button" wire:click="openInventoryCount">Abrir inventario</button>
            </div>

            <div class="rm-commerce-list">
                @forelse ($counts as $count)
                    <article class="rm-commerce-row">
                        <div class="rm-commerce-icon"><svg width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M8 3h8v4H8z"/><path d="M6 7h12v14H6z"/></svg></div>
                        <div class="rm-row-main">
                            <strong>{{ $count->name }}</strong>
                            <span>{{ $count->opened_at?->format('d/m/Y') }} - {{ $count->closed_at?->format('d/m/Y') ?? 'En proceso' }}</span>
                            <div class="rm-commerce-meta">
                                <span>{{ $count->status === 'in_process' ? 'En proceso' : 'Cerrado' }}</span>
                                <span>Zona {{ $count->useArea?->name ?? 'General' }}</span>
                                <span>Stock {{ \App\Support\Money::symbol() }} {{ number_format((float) data_get($count->totals, 'stock_value', 0), 2) }}</span>
                                <span>Perdida {{ \App\Support\Money::symbol() }} {{ number_format((float) data_get($count->totals, 'waste_value', 0), 2) }}</span>
                                <span>{{ (int) data_get($count->totals, 'products', $count->items_count ?? 0) }} producto(s)</span>
                            </div>
                        </div>
                        <div class="rm-commerce-actions">
                            <button type="button" wire:click="openCountDetails({{ $count->id }})">Ver detalle</button>
                            @if ($count->status === 'in_process')
                                <button type="button" wire:click="confirmCloseInventory({{ $count->id }})">Cerrar</button>
                            @endif
                            @if ($canManageInventoryClosures)
                                <button type="button" wire:click="confirmDeleteInventoryCount({{ $count->id }})">Eliminar</button>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="rm-empty-state"><strong>Sin cierres</strong><span>El inventario actual se genera automaticamente por sucursal.</span></div>
                @endforelse
            </div>
            <div class="rm-pagination-wrap">{{ $counts->links() }}</div>
        @endif
    </section>

    @include('livewire.inventory.partials.modals')
</div>
