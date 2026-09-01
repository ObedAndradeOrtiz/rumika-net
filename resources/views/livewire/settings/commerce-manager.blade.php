<div class="rm-content rm-settings-page">
    <div class="rm-settings-hero">
        <div>
            <span>Configuracion base</span>
            <h1>Comercios y sucursales</h1>
            <p>Cada sucursal puede usar un tipo de negocio distinto. Luego Rumika mostrara menus y modulos segun esa seleccion.</p>
        </div>
        <div class="rm-settings-summary">
            <strong>{{ $branches->count() }}</strong>
            <span>Comercios activos en {{ $company->name }}</span>
        </div>
    </div>

    <section class="rm-panel">
        <div class="rm-panel-title">
            <div>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                <h2>Lista de comercios</h2>
            </div>
            <button class="rm-button rm-button-primary" type="button" wire:click="create">Nuevo comercio</button>
        </div>

        @error('delete') <div class="rm-inline-error">{{ $message }}</div> @enderror

        <div class="rm-commerce-list">
            @foreach ($branches as $branch)
                <article class="rm-commerce-row {{ $activeBranchId === $branch->id ? 'is-selected' : '' }}">
                    <div class="rm-commerce-icon">
                        @if ($branch->logo_path)
                            <img src="{{ asset('storage/'.$branch->logo_path) }}" alt="{{ $branch->name }}">
                        @else
                            <svg width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 21h18M5 21V8l7-5 7 5v13M9 21v-6h6v6"/></svg>
                        @endif
                    </div>
                    <div class="rm-row-main">
                        <strong>{{ $branch->name }}</strong>
                        <span>{{ $branch->businessType?->name ?? 'Sin tipo' }}{{ $branch->address ? ' - '.$branch->address : '' }}</span>
                        <div class="rm-commerce-meta">
                            <span>{{ $branch->status === 'active' ? 'Activo' : 'Inactivo' }}</span>
                            <span>{{ $branch->country_code ?? 'BO' }} - {{ $branch->currency_code ?? 'BOB' }}</span>
                            <span>{{ $branch->uses_ticket_printer ? 'Impresora activa' : 'Sin impresora' }}</span>
                            @if ($activeBranchId === $branch->id)
                                <span>Panel actual</span>
                            @endif
                        </div>
                    </div>
                    <div class="rm-commerce-actions">
                        <button type="button" wire:click="selectBranch({{ $branch->id }})">Usar</button>
                        <button type="button" wire:click="edit({{ $branch->id }})">Editar</button>
                        <button type="button" wire:click="confirmDelete({{ $branch->id }})">Eliminar</button>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <section class="rm-panel" id="roles">
        <div class="rm-panel-title">
            <div>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-5"/></svg>
                <h2>Roles del negocio</h2>
            </div>
            <span>Base inicial</span>
        </div>

        <div class="rm-role-grid">
            @foreach ($roles as $role)
                <article class="rm-role-mini">
                    <strong>{{ $role->name }}</strong>
                    <span>{{ $role->description }}</span>
                </article>
            @endforeach
        </div>
    </section>

    @if ($showCommerceModal)
        <div class="rm-modal-backdrop" wire:click="closeCommerceModal"></div>
        <section class="rm-modal-panel rm-modal-panel-wide" role="dialog" aria-modal="true" aria-labelledby="commerce-modal-title">
            <div class="rm-modal-title">
                <div>
                    <span>{{ $editingId ? 'Editar comercio' : 'Nuevo comercio' }}</span>
                    <h2 id="commerce-modal-title">{{ $editingId ? 'Editar sucursal o comercio' : 'Crear sucursal o comercio' }}</h2>
                </div>
                <button type="button" wire:click="closeCommerceModal" aria-label="Cerrar modal">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <form wire:submit="save" class="rm-form-stack" enctype="multipart/form-data">
                <div class="rm-upload-preview-row">
                    <div class="rm-media-preview">
                        @if ($logo)
                            <img src="{{ $logo->temporaryUrl() }}" alt="Logo temporal">
                        @elseif ($currentLogoPath)
                            <img src="{{ asset('storage/'.$currentLogoPath) }}" alt="Logo actual">
                        @else
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 21h18M5 21V8l7-5 7 5v13M9 21v-6h6v6"/></svg>
                        @endif
                    </div>
                    <label class="rm-field">
                        <span>Logo o icono</span>
                        <input wire:key="branch-logo-input-{{ $editingId ?? 'new' }}" wire:model="logo" type="file" accept="image/*">
                        @error('logo') <small>{{ $message }}</small> @enderror
                    </label>
                </div>

                <label class="rm-field">
                    <span>Nombre</span>
                    <input wire:model="name" type="text" placeholder="Sucursal Centro">
                    @error('name') <small>{{ $message }}</small> @enderror
                </label>

                <label class="rm-field">
                    <span>Tipo de negocio</span>
                    <select wire:model="businessTypeId">
                        <option value="">Seleccionar tipo</option>
                        @foreach ($businessTypes as $businessType)
                            <option value="{{ $businessType->id }}">{{ $businessType->name }}</option>
                        @endforeach
                    </select>
                    @error('businessTypeId') <small>{{ $message }}</small> @enderror
                </label>

                <div class="rm-form-row">
                    <label class="rm-field">
                        <span>Pais y moneda</span>
                        <select wire:model.live="countryCode">
                            @foreach ($phoneCountries as $countryCodeOption => $countryRule)
                                <option value="{{ $countryCodeOption }}">{{ $countryRule['name'] }} - {{ $countryRule['currency'] }}</option>
                            @endforeach
                        </select>
                        @error('countryCode') <small>{{ $message }}</small> @enderror
                    </label>

                    <label class="rm-field">
                        <span>Telefono</span>
                        <input wire:model="phone" type="text" placeholder="Con codigo pais o numero local">
                        @error('phone') <small>{{ $message }}</small> @enderror
                    </label>
                </div>

                <div class="rm-form-row">
                    <label class="rm-field">
                        <span>Estado</span>
                        <select wire:model="status">
                            <option value="active">Activo</option>
                            <option value="inactive">Inactivo</option>
                        </select>
                        @error('status') <small>{{ $message }}</small> @enderror
                    </label>
                </div>

                <label class="rm-field">
                    <span>Direccion</span>
                    <input wire:model="address" type="text" placeholder="Zona, avenida o referencia">
                    @error('address') <small>{{ $message }}</small> @enderror
                </label>

                <section class="rm-printer-settings rm-branch-geofence" data-branch-geofence>
                    <div class="rm-panel-title rm-panel-title-compact">
                        <div>
                            <h3>Radio para asistencia</h3>
                            <p>Define el punto de la sucursal y cuantos metros alrededor permiten registrar entrada o salida.</p>
                        </div>
                        @if ($attendanceLatitude !== '' && $attendanceLongitude !== '')
                            <a class="rm-button rm-button-outline" target="_blank" rel="noopener"
                                href="https://www.google.com/maps?q={{ $attendanceLatitude }},{{ $attendanceLongitude }}">
                                Ver mapa
                            </a>
                        @endif
                    </div>
                    <div class="rm-geofence-preview">
                        <div>
                            <strong>{{ $attendanceLatitude !== '' && $attendanceLongitude !== '' ? 'Ubicacion configurada' : 'Sin ubicacion de asistencia' }}</strong>
                            <span>{{ $attendanceLatitude !== '' && $attendanceLongitude !== '' ? $attendanceLatitude.', '.$attendanceLongitude : 'Usa la ubicacion actual estando en la sucursal.' }}</span>
                        </div>
                        <button class="rm-button rm-button-primary" type="button" data-branch-location-button>
                            Usar mi ubicacion
                        </button>
                    </div>
                    <div class="rm-form-row">
                        <label class="rm-field">
                            <span>Latitud</span>
                            <input wire:model="attendanceLatitude" data-branch-latitude type="number" step="0.0000001" placeholder="-17.783327">
                            @error('attendanceLatitude') <small>{{ $message }}</small> @enderror
                        </label>
                        <label class="rm-field">
                            <span>Longitud</span>
                            <input wire:model="attendanceLongitude" data-branch-longitude type="number" step="0.0000001" placeholder="-63.182140">
                            @error('attendanceLongitude') <small>{{ $message }}</small> @enderror
                        </label>
                        <label class="rm-field">
                            <span>Radio en metros</span>
                            <input wire:model="attendanceRadiusMeters" type="number" min="20" max="5000" step="10">
                            @error('attendanceRadiusMeters') <small>{{ $message }}</small> @enderror
                        </label>
                    </div>
                </section>

                <section class="rm-printer-settings">
                    <label class="rm-checkline">
                        <input wire:model.live="usesTicketPrinter" type="checkbox">
                        <span>Usar impresora de tickets en esta sucursal</span>
                    </label>

                    @if ($usesTicketPrinter)
                        <label class="rm-field">
                            <span>Nombre exacto de impresora en QZ Tray</span>
                            <input wire:model="printerName" type="text" placeholder="EPSON TM-T20, POS-58, etc.">
                            @error('printerName') <small>{{ $message }}</small> @enderror
                        </label>
                    @endif
                </section>

                <section class="rm-printer-settings">
                    <div class="rm-panel-title rm-panel-title-compact">
                        <div>
                            <h3>Comisiones de esta sucursal</h3>
                            <p>Se aplican al guardar cobros o ventas nuevas. Los productos y servicios pueden desactivarse luego uno por uno.</p>
                        </div>
                    </div>
                    <div class="rm-form-row">
                        <label class="rm-field">
                            <span>% venta de productos</span>
                            <input wire:model="productCommissionPercent" type="number" min="0" max="100" step="0.01">
                            @error('productCommissionPercent') <small>{{ $message }}</small> @enderror
                        </label>
                        <label class="rm-field">
                            <span>Minimo por producto</span>
                            <input wire:model="productCommissionMinSale" type="number" min="0" step="0.01">
                            @error('productCommissionMinSale') <small>{{ $message }}</small> @enderror
                        </label>
                    </div>
                    <div class="rm-form-row">
                        <label class="rm-field">
                            <span>% servicios/tratamientos</span>
                            <input wire:model="serviceCommissionPercent" type="number" min="0" max="100" step="0.01">
                            @error('serviceCommissionPercent') <small>{{ $message }}</small> @enderror
                        </label>
                        <label class="rm-field">
                            <span>Minimo por servicio</span>
                            <input wire:model="serviceCommissionMinSale" type="number" min="0" step="0.01">
                            @error('serviceCommissionMinSale') <small>{{ $message }}</small> @enderror
                        </label>
                    </div>
                </section>

                <div class="rm-form-actions">
                    <button class="rm-button rm-button-primary" type="submit">{{ $editingId ? 'Guardar cambios' : 'Crear comercio' }}</button>
                    <button class="rm-button rm-button-outline" type="button" wire:click="closeCommerceModal">Cancelar</button>
                </div>
            </form>
        </section>
    @endif

    @if ($confirmingDeleteId)
        <div class="rm-modal-backdrop" wire:click="cancelDelete"></div>
        <section class="rm-modal-panel rm-modal-panel-small" role="dialog" aria-modal="true">
            <div class="rm-confirm-icon">!</div>
            <h2>Eliminar comercio</h2>
            <p>Se eliminara esta sucursal o comercio y sus accesos asociados. Debe quedar al menos uno activo.</p>
            <div class="rm-form-actions">
                <button class="rm-button rm-button-danger" type="button" wire:click="delete({{ $confirmingDeleteId }})">Eliminar</button>
                <button class="rm-button rm-button-outline" type="button" wire:click="cancelDelete">Cancelar</button>
            </div>
        </section>
    @endif
</div>
