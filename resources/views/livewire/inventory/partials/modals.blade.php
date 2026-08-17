@if ($showSupplierModal)
    <div class="rm-modal-backdrop" wire:click="$set('showSupplierModal', false)"></div>
    <section class="rm-modal-panel rm-modal-panel-wide" role="dialog" aria-modal="true">
        <div class="rm-modal-title"><div><span>Proveedor</span><h2>Nuevo proveedor</h2></div><button type="button" wire:click="$set('showSupplierModal', false)">x</button></div>
        <form wire:submit="saveSupplier" class="rm-form-stack">
            <label class="rm-field"><span>Nombre</span><input wire:model="supplierName" type="text">@error('supplierName')<small>{{ $message }}</small>@enderror</label>
            <div class="rm-form-row">
                <label class="rm-field"><span>Contacto</span><input wire:model="supplierContact" type="text"></label>
                <label class="rm-field"><span>Telefono</span><input wire:model="supplierPhone" type="text"></label>
            </div>
            <label class="rm-field"><span>Correo</span><input wire:model="supplierEmail" type="email"></label>
            <div class="rm-form-actions"><button class="rm-button rm-button-primary" type="submit">Guardar proveedor</button></div>
        </form>
    </section>
@endif

@if ($showBrandModal)
    <div class="rm-modal-backdrop" wire:click="$set('showBrandModal', false)"></div>
    <section class="rm-modal-panel rm-modal-panel-wide" role="dialog" aria-modal="true">
        <div class="rm-modal-title"><div><span>Marca</span><h2>Nueva marca</h2></div><button type="button" wire:click="$set('showBrandModal', false)">x</button></div>
        <form wire:submit="saveBrand" class="rm-form-stack">
            <label class="rm-field"><span>Nombre</span><input wire:model="brandName" type="text">@error('brandName')<small>{{ $message }}</small>@enderror</label>
            <label class="rm-field"><span>Proveedor</span><select wire:model="brandSupplierId"><option value="">Sin proveedor fijo</option>@foreach ($suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->name }}</option>@endforeach</select></label>
            <div class="rm-form-actions"><button class="rm-button rm-button-primary" type="submit">Guardar marca</button></div>
        </form>
    </section>
@endif

@if ($showUseAreaModal)
    <div class="rm-modal-backdrop" wire:click="closeUseAreaModal"></div>
    <section class="rm-modal-panel rm-modal-panel-wide" role="dialog" aria-modal="true">
        <div class="rm-modal-title"><div><span>Area de uso</span><h2>{{ $editingUseAreaId ? 'Editar area de uso' : 'Nueva area de uso' }}</h2></div><button type="button" wire:click="closeUseAreaModal">x</button></div>
        <form wire:submit="saveUseArea" class="rm-form-stack">
            <label class="rm-field"><span>Nombre</span><input wire:model="useAreaName" type="text" placeholder="Venta, gabinete, limpieza">@error('useAreaName')<small>{{ $message }}</small>@enderror</label>
            <label class="rm-field"><span>Descripcion</span><input wire:model="useAreaDescription" type="text" placeholder="Uso principal del producto"></label>
            <div class="rm-form-actions"><button class="rm-button rm-button-primary" type="submit">{{ $editingUseAreaId ? 'Guardar cambios' : 'Guardar area' }}</button></div>
        </form>
    </section>
@endif

@if ($showProductModal)
    <div class="rm-modal-backdrop" wire:click="$set('showProductModal', false)"></div>
    <section class="rm-modal-panel rm-modal-panel-wide" role="dialog" aria-modal="true">
        <div class="rm-modal-title"><div><span>Producto</span><h2>{{ $editingProductId ? 'Editar producto' : 'Nuevo producto' }}</h2></div><button type="button" wire:click="$set('showProductModal', false)">x</button></div>
        <form wire:submit="saveProduct" class="rm-form-stack">
            <div class="rm-empty-state">
                <strong>Catalogo del producto</strong>
                <span>El stock no se modifica desde esta ventana. La cantidad se mueve solo por entradas, salidas, traspasos, desechos o ajustes.</span>
            </div>
            <label class="rm-field"><span>Nombre</span><input wire:model="productName" type="text" placeholder="Aguja mesoterapia 30G">@error('productName')<small>{{ $message }}</small>@enderror</label>
            <label class="rm-field"><span>Descripcion</span><input wire:model="productDescription" type="text"></label>
            <div class="rm-form-row">
                <label class="rm-field"><span>Proveedor</span><select wire:model="productSupplierId"><option value="">Sin proveedor</option>@foreach ($suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->name }}</option>@endforeach</select></label>
                <label class="rm-field"><span>Marca</span><select wire:model="productBrandId"><option value="">Sin marca</option>@foreach ($brands as $brand)<option value="{{ $brand->id }}">{{ $brand->name }}</option>@endforeach</select></label>
            </div>
            <label class="rm-field"><span>Area de uso</span><select wire:model="productUseAreaId"><option value="">Sin area de uso</option>@foreach ($useAreas as $area)<option value="{{ $area->id }}">{{ $area->name }}</option>@endforeach</select></label>
            <div class="rm-form-row">
                <label class="rm-field"><span>Unidad minima</span><input wire:model="unitName" type="text" placeholder="unidad, ml, vial">@error('unitName')<small>{{ $message }}</small>@enderror</label>
                <label class="rm-field"><span>Stock minimo</span><input wire:model="minimumStock" type="number" min="0"></label>
            </div>
            <div class="rm-form-row">
                <label class="rm-field"><span>Paquete/caja</span><input wire:model="packageName" type="text" placeholder="Caja"></label>
                <label class="rm-field"><span>Contenido por paquete</span><input wire:model="unitsPerPackage" type="number" min="1">@error('unitsPerPackage')<small>{{ $message }}</small>@enderror</label>
            </div>
            <label class="rm-field"><span>Costo de compra unitario</span><input wire:model="purchaseCost" type="number" min="0" step="0.01"></label>
            <div class="rm-form-actions"><button class="rm-button rm-button-primary" type="submit">Guardar producto</button></div>
        </form>
    </section>
@endif

@if ($showMovementModal)
    <div class="rm-modal-backdrop" wire:click="$set('showMovementModal', false)"></div>
    <section class="rm-modal-panel rm-modal-panel-wide" role="dialog" aria-modal="true">
        <div class="rm-modal-title">
            <div>
                <span>Movimiento</span>
                <h2>{{ $this->movementTypeLabel() }}</h2>
            </div>
            <button type="button" wire:click="$set('showMovementModal', false)">x</button>
        </div>

        <form wire:submit="saveMovement" class="rm-form-stack">
            <label class="rm-field">
                <span>Buscar producto</span>
                <input
                    wire:model.live.debounce.300ms="movementProductSearch"
                    type="search"
                    placeholder="Buscar por nombre o codigo"
                    autocomplete="off"
                >
            </label>

            <label class="rm-field">
                <span>Producto</span>
                <select wire:model.live="movementProductId">
                    <option value="">Seleccionar producto</option>
                    @foreach ($movementProducts as $product)
                        <option value="{{ $product->id }}">
                            {{ $product->name }} - {{ $product->code }}
                        </option>
                    @endforeach
                </select>
                @error('movementProductId')<small>{{ $message }}</small>@enderror
            </label>

            @if ($movementType !== 'purchase')
                <label class="rm-field">
                    <span>Lote disponible</span>
                    <select wire:model="movementBatchId" @disabled(!$movementProductId)>
                        <option value="">
                            {{ $movementProductId ? 'Seleccionar lote' : 'Primero selecciona un producto' }}
                        </option>
                        @foreach ($movementBatches as $batch)
                            <option value="{{ $batch->id }}">
                                {{ $batch->lot_code }} - Stock {{ number_format((float) $batch->current_quantity, 2) }}
                                @if ($batch->expires_at)
                                    - Vence {{ $batch->expires_at->format('d/m/Y') }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error('movementBatchId')<small>{{ $message }}</small>@enderror

                    @if ($movementProductId && $movementBatches->isEmpty())
                        <small>Este producto no tiene lotes disponibles con stock en esta sucursal.</small>
                    @endif
                </label>
            @endif

            <div class="rm-form-row">
                <label class="rm-field">
                    <span>Cantidad</span>
                    <input wire:model="movementQuantity" type="number" min="0.01" step="0.01">
                    @error('movementQuantity')<small>{{ $message }}</small>@enderror
                </label>
                <label class="rm-field">
                    <span>Costo unitario</span>
                    <input wire:model="movementUnitCost" type="number" min="0" step="0.01">
                </label>
            </div>

            @if ($movementType === 'purchase')
                <div class="rm-form-row">
                    <label class="rm-field">
                        <span>Lote</span>
                        <input wire:model="movementLotCode" type="text" placeholder="Automatico si queda vacio">
                        @error('movementLotCode')<small>{{ $message }}</small>@enderror
                    </label>
                    <label class="rm-field">
                        <span>Vencimiento</span>
                        <input wire:model="movementExpiresAt" type="date">
                        @error('movementExpiresAt')<small>{{ $message }}</small>@enderror
                    </label>
                </div>
                <label class="rm-field">
                    <span>Fecha de entrada</span>
                    <input wire:model="movementReceivedAt" type="date">
                    @error('movementReceivedAt')<small>{{ $message }}</small>@enderror
                </label>
            @endif

            @if ($movementType === 'transfer')
                <label class="rm-field">
                    <span>Sucursal destino</span>
                    <select wire:model="relatedBranchId">
                        <option value="">Seleccionar</option>
                        @foreach ($branches as $targetBranch)
                            @if ($targetBranch->id !== $branch->id)
                                <option value="{{ $targetBranch->id }}">{{ $targetBranch->name }}</option>
                            @endif
                        @endforeach
                    </select>
                    @error('relatedBranchId')<small>{{ $message }}</small>@enderror
                </label>
            @endif

            <label class="rm-field">
                <span>Referencia</span>
                <input wire:model="movementReference" type="text" placeholder="Factura, venta, orden interna">
            </label>

            <label class="rm-field">
                <span>Motivo/explicacion</span>
                <input wire:model="movementReason" type="text" placeholder="Obligatorio para salida, desecho o ajuste">
                @error('movementReason')<small>{{ $message }}</small>@enderror
            </label>

            <div class="rm-form-actions">
                <button class="rm-button rm-button-primary" type="submit">Guardar movimiento</button>
            </div>
        </form>
    </section>
@endif

@if ($confirmingProductDeleteId)
    <div class="rm-modal-backdrop" wire:click="cancelDeleteProduct"></div>
    <section class="rm-modal-panel rm-modal-panel-small" role="dialog" aria-modal="true">
        <div class="rm-confirm-icon">!</div>
        <h2>Eliminar producto</h2>
        <p>Esta accion eliminara el producto del catalogo. Si tuvo uso de gabinete o ventas en el mes actual, Rumika no permitira eliminarlo.</p>
        @error('deleteProduct')<p class="rm-inline-error">{{ $message }}</p>@enderror
        <div class="rm-form-actions">
            <button class="rm-button rm-button-danger" type="button" wire:click="deleteProduct({{ $confirmingProductDeleteId }})">Eliminar</button>
            <button class="rm-button rm-button-outline" type="button" wire:click="cancelDeleteProduct">Cancelar</button>
        </div>
    </section>
@endif

@if ($confirmingUseAreaDeleteId)
    <div class="rm-modal-backdrop" wire:click="cancelDeleteUseArea"></div>
    <section class="rm-modal-panel rm-modal-panel-small" role="dialog" aria-modal="true">
        <div class="rm-confirm-icon">!</div>
        <h2>Eliminar zona</h2>
        <p>Esta accion eliminara la zona del inventario. Si tiene productos o inventarios asociados, Rumika no permitira eliminarla.</p>
        @error('deleteUseArea')<p class="rm-inline-error">{{ $message }}</p>@enderror
        <div class="rm-form-actions">
            <button class="rm-button rm-button-danger" type="button" wire:click="deleteUseArea({{ $confirmingUseAreaDeleteId }})">Eliminar</button>
            <button class="rm-button rm-button-outline" type="button" wire:click="cancelDeleteUseArea">Cancelar</button>
        </div>
    </section>
@endif

@if ($showAssetModal)
    <div class="rm-modal-backdrop" wire:click="$set('showAssetModal', false)"></div>
    <section class="rm-modal-panel rm-modal-panel-wide" role="dialog" aria-modal="true">
        <div class="rm-modal-title"><div><span>Activo</span><h2>{{ $editingAssetId ? 'Editar activo' : 'Nuevo activo' }}</h2></div><button type="button" wire:click="$set('showAssetModal', false)">x</button></div>
        <form wire:submit="saveAsset" class="rm-form-stack">
            <label class="rm-field"><span>Nombre</span><input wire:model="assetName" type="text">@error('assetName')<small>{{ $message }}</small>@enderror</label>
            <div class="rm-form-row">
                <label class="rm-field"><span>Categoria</span><input wire:model="assetCategory" type="text" placeholder="Equipo, mueble, maquina"></label>
                <label class="rm-field"><span>Estado</span><select wire:model="assetStatus"><option value="active">Activo</option><option value="maintenance">Mantenimiento</option><option value="wasted">Desechado</option></select></label>
            </div>
            <div class="rm-form-row">
                <label class="rm-field"><span>Monto compra</span><input wire:model="assetPurchaseAmount" type="number" min="0" step="0.01"></label>
                <label class="rm-field"><span>Fecha compra</span><input wire:model="assetPurchaseDate" type="date"></label>
            </div>
            <div class="rm-form-actions"><button class="rm-button rm-button-primary" type="submit">Guardar activo</button></div>
        </form>
    </section>
@endif

@if ($showRepairModal)
    <div class="rm-modal-backdrop" wire:click="$set('showRepairModal', false)"></div>
    <section class="rm-modal-panel rm-modal-panel-wide" role="dialog" aria-modal="true">
        <div class="rm-modal-title"><div><span>Reparacion</span><h2>Registrar reparacion</h2></div><button type="button" wire:click="$set('showRepairModal', false)">x</button></div>
        <form wire:submit="saveRepair" class="rm-form-stack">
            <label class="rm-field"><span>Monto</span><input wire:model="repairAmount" type="number" min="0.01" step="0.01">@error('repairAmount')<small>{{ $message }}</small>@enderror</label>
            <label class="rm-field"><span>Fecha</span><input wire:model="repairDate" type="date"></label>
            <label class="rm-field"><span>Descripcion</span><input wire:model="repairDescription" type="text"></label>
            <div class="rm-form-actions"><button class="rm-button rm-button-primary" type="submit">Guardar reparacion</button></div>
        </form>
    </section>
@endif

@if ($showWasteAssetModal)
    <div class="rm-modal-backdrop" wire:click="$set('showWasteAssetModal', false)"></div>
    <section class="rm-modal-panel rm-modal-panel-small" role="dialog" aria-modal="true">
        <div class="rm-confirm-icon">!</div>
        <h2>Desechar activo</h2>
        <label class="rm-field"><span>Explicacion</span><input wire:model="assetWasteReason" type="text">@error('assetWasteReason')<small>{{ $message }}</small>@enderror</label>
        <div class="rm-form-actions"><button class="rm-button rm-button-danger" type="button" wire:click="wasteAsset">Confirmar</button><button class="rm-button rm-button-outline" type="button" wire:click="$set('showWasteAssetModal', false)">Cancelar</button></div>
    </section>
@endif

@if ($showCountDetailsModal && $selectedCount)
    <div class="rm-modal-backdrop" wire:click="$set('showCountDetailsModal', false)"></div>
    <section class="rm-modal-panel rm-modal-panel-xl rm-inventory-detail-modal" role="dialog" aria-modal="true">
        <div class="rm-modal-title">
            <div>
                <span>Historial de inventario</span>
                <h2>{{ $selectedCount->name }}</h2>
            </div>
            <button type="button" wire:click="$set('showCountDetailsModal', false)">x</button>
        </div>

        <div class="rm-kpi-strip rm-inventory-kpis">
            <div class="rm-kpi"><strong>{{ $selectedCount->status === 'in_process' ? 'En proceso' : 'Cerrado' }}</strong><span>Estado</span></div>
            <div class="rm-kpi"><strong>{{ $selectedCount->useArea?->name ?? 'General' }}</strong><span>Zona</span></div>
            <div class="rm-kpi"><strong>{{ $selectedCount->items->count() }}</strong><span>Productos</span></div>
            <div class="rm-kpi"><strong>{{ $selectedCount->items->where('status', 'new')->count() }}</strong><span>Nuevos</span></div>
        </div>
        @if ($selectedCount->status === 'in_process')
            <div class="rm-inline-notice">
                Edita la apertura como planilla. Al guardar se ajusta el stock real para que caja pueda vender con esa cantidad.
            </div>
            @error('countOpeningQuantities')<p class="rm-inline-error">{{ $message }}</p>@enderror
        @endif
        <label class="rm-search-field">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input wire:model.live.debounce.250ms="countDetailSearch" type="search" placeholder="Buscar producto, codigo, marca o zona">
        </label>
        <div class="rm-count-detail-toolbar">
            <label class="rm-check-option rm-compact-check">
                <input wire:model.live="countOnlyDifferences" type="checkbox">
                <span>Solo diferencias</span>
            </label>
            <div class="rm-commerce-meta">
                <span>Mostrando {{ $selectedCountItems->count() }} de {{ $selectedCount->items->count() }} producto(s)</span>
            </div>
            <div class="rm-form-actions">
                <button class="rm-button rm-button-outline" type="button" wire:click="exportCountDetails('csv')">Descargar Excel</button>
                <button class="rm-button rm-button-outline" type="button" wire:click="exportCountDetails('pdf')">Descargar PDF</button>
            </div>
        </div>

        <form wire:submit="saveCountOpeningQuantities" class="rm-form-stack">
        <div class="rm-inventory-detail-table">
            <div class="rm-inventory-detail-row is-head">
                <span>Producto</span>
                <span>Marca</span>
                <span>Apertura</span>
                <span>Entradas</span>
                <span>Salidas</span>
                <span>Esperado</span>
                <span>Cierre</span>
                <span>Diferencia</span>
            </div>
            @forelse ($selectedCountItems as $item)
                <div class="rm-inventory-detail-row">
                    <span>
                        <strong>{{ $item->product?->name }}</strong>
                        <small>{{ $item->status === 'new' ? 'Producto nuevo' : ($item->product?->code ?? '') }}</small>
                    </span>
                    <span>{{ $item->brand?->name ?? $item->product?->brand?->name ?? 'GENERAL' }}</span>
                    <span>
                        @if ($selectedCount->status === 'in_process')
                            <input class="rm-inventory-sheet-input" wire:model.defer="countOpeningQuantities.{{ $item->id }}" type="number" min="0" step="0.01">
                            @error('countOpeningQuantities.'.$item->id)<small>{{ $message }}</small>@enderror
                        @else
                            {{ number_format((float) $item->opening_quantity, 2) }}
                        @endif
                    </span>
                    <span>{{ number_format((float) $item->movement_in_quantity, 2) }}</span>
                    <span>{{ number_format((float) $item->movement_out_quantity, 2) }}</span>
                    <span>{{ number_format((float) $item->expected_quantity, 2) }}</span>
                    <span>{{ number_format((float) $item->closed_quantity, 2) }}</span>
                    <span class="{{ (float) $item->difference_quantity === 0.0 ? 'is-ok' : 'is-alert' }}">{{ number_format((float) $item->difference_quantity, 2) }}</span>
                </div>
            @empty
                <div class="rm-empty-state"><strong>Sin resultados</strong><span>Prueba con otro producto, codigo, marca o zona.</span></div>
            @endforelse
        </div>
        @if ($selectedCount->status === 'in_process')
            <div class="rm-form-actions">
                <button class="rm-button rm-button-primary" type="submit">Guardar apertura manual</button>
            </div>
        @endif
        </form>
    </section>
@endif

@if ($showProductMovementsModal && $selectedProduct)
    <div class="rm-modal-backdrop" wire:click="$set('showProductMovementsModal', false)"></div>
    <section class="rm-modal-panel rm-modal-panel-xl rm-inventory-detail-modal" role="dialog" aria-modal="true">
        <div class="rm-modal-title">
            <div>
                <span>Movimientos por producto</span>
                <h2>{{ $selectedProduct->name }}</h2>
            </div>
            <button type="button" wire:click="$set('showProductMovementsModal', false)">x</button>
        </div>

        <div class="rm-commerce-meta">
            <span>{{ $selectedProduct->code }}</span>
            <span>{{ $selectedProduct->brand?->name ?? 'GENERAL' }}</span>
            <span>{{ $selectedProduct->useArea?->name ?? 'Sin zona' }}</span>
        </div>

        <div class="rm-tab-switcher rm-product-movement-tabs" role="tablist" aria-label="Movimientos del producto">
            <button class="{{ $productMovementTab === 'sales' ? 'is-active' : '' }}" type="button" wire:click="setProductMovementTab('sales')">Ventas</button>
            <button class="{{ $productMovementTab === 'entries' ? 'is-active' : '' }}" type="button" wire:click="setProductMovementTab('entries')">Entradas</button>
            <button class="{{ $productMovementTab === 'waste' ? 'is-active' : '' }}" type="button" wire:click="setProductMovementTab('waste')">Bajas</button>
            <button class="{{ $productMovementTab === 'transfers' ? 'is-active' : '' }}" type="button" wire:click="setProductMovementTab('transfers')">Traspasos</button>
            <button class="{{ $productMovementTab === 'adjustments' ? 'is-active' : '' }}" type="button" wire:click="setProductMovementTab('adjustments')">Ajustes</button>
        </div>

        @if ($productMovementTab === 'sales')
            <div class="rm-commerce-list">
                @forelse ($selectedProductMovements->get('sales', collect()) as $item)
                    <article class="rm-commerce-row">
                        <div class="rm-commerce-icon">$</div>
                        <div class="rm-row-main">
                            <strong>{{ $item->payment?->client?->full_name ?? 'Cliente no registrado' }}</strong>
                            <span>{{ $item->payment?->paid_at?->format('d/m/Y H:i') }} - {{ number_format((float) $item->quantity, 2) }} unidad(es) - Bs {{ number_format((float) $item->total, 2) }}</span>
                            <div class="rm-commerce-meta">
                                <span>Lote {{ $item->batch?->lot_code ?? 'N/A' }}</span>
                                <span>Vendido por {{ $item->soldBy?->name ?? 'Sin vendedor' }}</span>
                                <span>Cobro PAY-{{ $item->treatment_payment_id }}</span>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rm-empty-state"><strong>Sin ventas</strong><span>Cuando se venda este producto desde caja, aparecera aqui.</span></div>
                @endforelse
            </div>
        @else
            <div class="rm-commerce-list">
                @forelse ($selectedProductMovements->get('movements', collect()) as $movement)
                    <article class="rm-commerce-row">
                        <div class="rm-commerce-icon">{{ strtoupper(substr($this->movementLabel($movement->type), 0, 1)) }}</div>
                        <div class="rm-row-main">
                            <strong>{{ $this->movementLabel($movement->type) }}</strong>
                            <span>{{ $movement->moved_at?->format('d/m/Y H:i') }} - {{ number_format((float) $movement->quantity, 2) }} unidad(es) - Bs {{ number_format((float) $movement->total_cost, 2) }}</span>
                            <div class="rm-commerce-meta">
                                <span>Lote {{ $movement->batch?->lot_code ?? 'N/A' }}</span>
                                <span>{{ $movement->relatedBranch?->name ?? $branch->name }}</span>
                                @if ($movement->reference)<span>{{ $movement->reference }}</span>@endif
                                @if ($movement->reason)<span>{{ $movement->reason }}</span>@endif
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rm-empty-state"><strong>Sin movimientos</strong><span>No hay registros en este apartado para el producto.</span></div>
                @endforelse
            </div>
        @endif
    </section>
@endif

@if ($closingCountId)
    <div class="rm-modal-backdrop" wire:click="$set('closingCountId', null)"></div>
    <section class="rm-modal-panel rm-modal-panel-small" role="dialog" aria-modal="true">
        <div class="rm-confirm-icon">!</div>
        <h2>Cerrar inventario</h2>
        <p>Se guardara el inventario actual de esta sucursal como periodo cerrado y se abrira uno nuevo en proceso.</p>
        <div class="rm-form-actions"><button class="rm-button rm-button-danger" type="button" wire:click="closeInventory">Cerrar inventario</button><button class="rm-button rm-button-outline" type="button" wire:click="$set('closingCountId', null)">Cancelar</button></div>
    </section>
@endif

@if ($confirmingCountDeleteId)
    <div class="rm-modal-backdrop" wire:click="cancelDeleteInventoryCount"></div>
    <section class="rm-modal-panel rm-modal-panel-small" role="dialog" aria-modal="true">
        <div class="rm-confirm-icon">!</div>
        <h2>Eliminar cierre</h2>
        <p>Solo administrador o super administrador puede eliminar cierres abiertos o cerrados. Los movimientos quedaran en historial, pero el cierre y su detalle se quitaran.</p>
        @error('countDelete')<p class="rm-inline-error">{{ $message }}</p>@enderror
        <div class="rm-form-actions">
            <button class="rm-button rm-button-danger" type="button" wire:click="deleteInventoryCount">Eliminar cierre</button>
            <button class="rm-button rm-button-outline" type="button" wire:click="cancelDeleteInventoryCount">Cancelar</button>
        </div>
    </section>
@endif
