<div class="rm-content rm-settings-page">
    <div class="rm-settings-hero">
        <div>
            <span>Catalogo comercial</span>
            <h1>Servicios y paquetes</h1>
            <p>Configura servicios individuales y paquetes de servicios con vigencia, estado y valor general para cualquier tipo de negocio.</p>
        </div>
        <div class="rm-settings-summary">
            <strong>{{ $servicesTotal }}</strong>
            <span>Servicios registrados</span>
        </div>
    </div>

    <section class="rm-panel rm-catalog-panel">
        <div class="rm-tab-switcher" role="tablist" aria-label="Catalogo de servicios">
            <button
                class="{{ $activeTab === 'services' ? 'is-active' : '' }}"
                type="button"
                role="tab"
                aria-selected="{{ $activeTab === 'services' ? 'true' : 'false' }}"
                wire:click="setActiveTab('services')"
            >
                Servicios
                <span>{{ $servicesTotal }}</span>
            </button>
            <button
                class="{{ $activeTab === 'packages' ? 'is-active' : '' }}"
                type="button"
                role="tab"
                aria-selected="{{ $activeTab === 'packages' ? 'true' : 'false' }}"
                wire:click="setActiveTab('packages')"
            >
                Paquetes
                <span>{{ $packagesTotal }}</span>
            </button>
        </div>

        @if ($activeTab === 'services')
            <section class="rm-tab-panel" role="tabpanel">
            <div class="rm-panel-title">
                <div>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 7h-9"/><path d="M14 17H5"/><circle cx="17" cy="17" r="3"/><circle cx="7" cy="7" r="3"/></svg>
                    <h2>Servicios</h2>
                </div>
                <button class="rm-button rm-button-primary" type="button" wire:click="createService">Nuevo servicio</button>
            </div>

            <label class="rm-search-field">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input wire:model.live.debounce.300ms="serviceSearch" type="search" placeholder="Buscar servicio por nombre o descripcion">
            </label>

            <div class="rm-commerce-list">
                @forelse ($services as $service)
                    <article class="rm-commerce-row">
                        <div class="rm-commerce-icon">
                            <svg width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 7h-9"/><path d="M14 17H5"/><circle cx="17" cy="17" r="3"/><circle cx="7" cy="7" r="3"/></svg>
                        </div>
                        <div class="rm-row-main">
                            <strong>{{ $service->name }}</strong>
                            <span>Bs {{ $service->price }}{{ $service->duration_minutes ? ' - '.$service->duration_minutes.' min' : '' }}</span>
                            <div class="rm-commerce-meta">
                                <span>{{ $service->status === 'available' ? 'Disponible' : 'No disponible' }}</span>
                                <span>{{ $service->branch?->name ?? 'Todas las sucursales' }}</span>
                            </div>
                        </div>
                        <div class="rm-commerce-actions">
                            <button type="button" wire:click="editService({{ $service->id }})">Editar</button>
                            <button type="button" wire:click="confirmDeleteService({{ $service->id }})">Eliminar</button>
                        </div>
                    </article>
                @empty
                    <div class="rm-empty-state">
                        <strong>Sin servicios</strong>
                        <span>{{ trim($serviceSearch) !== '' ? 'No encontramos servicios con esa busqueda.' : 'Crea el primer servicio para empezar a armar paquetes.' }}</span>
                    </div>
                @endforelse
            </div>

            <div class="rm-pagination-wrap">
                {{ $services->links('vendor.pagination.rumika') }}
            </div>
            </section>
        @endif

        @if ($activeTab === 'packages')
            <section class="rm-tab-panel" role="tabpanel">
            <div class="rm-panel-title">
                <div>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 12v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-7"/><path d="M2 7h20v5H2z"/><path d="M12 22V7"/><path d="M12 7H7.5a2.5 2.5 0 1 1 0-5C11 2 12 7 12 7"/><path d="M12 7h4.5a2.5 2.5 0 1 0 0-5C13 2 12 7 12 7"/></svg>
                    <h2>Paquetes</h2>
                </div>
                <button class="rm-button rm-button-primary" type="button" wire:click="createPackage">Nuevo paquete</button>
            </div>

            <label class="rm-search-field">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input wire:model.live.debounce.300ms="packageSearch" type="search" placeholder="Buscar paquete por nombre o descripcion">
            </label>

            <div class="rm-commerce-list">
                @forelse ($packages as $package)
                    <article class="rm-commerce-row">
                        <div class="rm-commerce-icon">
                            <svg width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 12v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-7"/><path d="M2 7h20v5H2z"/><path d="M12 22V7"/></svg>
                        </div>
                        <div class="rm-row-main">
                            <strong>{{ $package->name }}</strong>
                            <span>Bs {{ $package->price }} - {{ $package->services->count() }} servicio(s)</span>
                            <div class="rm-commerce-meta">
                                <span>{{ $package->status === 'available' ? 'Disponible' : 'No disponible' }}</span>
                                <span>{{ $package->starts_at?->format('d/m/Y') ?? 'Sin inicio' }} - {{ $package->expires_at?->format('d/m/Y') ?? 'Sin fin' }}</span>
                            </div>
                        </div>
                        <div class="rm-commerce-actions">
                            <button type="button" wire:click="editPackage({{ $package->id }})">Editar</button>
                            <button type="button" wire:click="confirmDeletePackage({{ $package->id }})">Eliminar</button>
                        </div>
                    </article>
                @empty
                    <div class="rm-empty-state">
                        <strong>Sin paquetes</strong>
                        <span>{{ trim($packageSearch) !== '' ? 'No encontramos paquetes con esa busqueda.' : 'Los paquetes pueden agrupar varios servicios con un valor y vigencia.' }}</span>
                    </div>
                @endforelse
            </div>

            <div class="rm-pagination-wrap">
                {{ $packages->links('vendor.pagination.rumika') }}
            </div>
            </section>
        @endif
    </section>

    @if ($showServiceModal)
        <div class="rm-modal-backdrop" wire:click="closeServiceModal"></div>
        <section class="rm-modal-panel rm-modal-panel-wide" role="dialog" aria-modal="true">
            <div class="rm-modal-title">
                <div>
                    <span>{{ $editingServiceId ? 'Editar servicio' : 'Nuevo servicio' }}</span>
                    <h2>{{ $editingServiceId ? 'Editar servicio' : 'Crear servicio' }}</h2>
                </div>
                <button type="button" wire:click="closeServiceModal" aria-label="Cerrar modal">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <form wire:submit="saveService" class="rm-form-stack">
                <label class="rm-field">
                    <span>Nombre</span>
                    <input wire:model="serviceName" type="text" placeholder="Limpieza facial">
                    @error('serviceName') <small>{{ $message }}</small> @enderror
                </label>

                <label class="rm-field">
                    <span>Descripcion</span>
                    <input wire:model="serviceDescription" type="text" placeholder="Detalle interno o comercial">
                    @error('serviceDescription') <small>{{ $message }}</small> @enderror
                </label>

                <div class="rm-form-row">
                    <label class="rm-field">
                        <span>Valor</span>
                        <input wire:model="servicePrice" type="number" min="0" step="0.01" placeholder="0.00">
                        @error('servicePrice') <small>{{ $message }}</small> @enderror
                    </label>

                    <label class="rm-field">
                        <span>Duracion</span>
                        <input wire:model="serviceDuration" type="number" min="1" placeholder="Minutos">
                        @error('serviceDuration') <small>{{ $message }}</small> @enderror
                    </label>
                </div>

                <div class="rm-form-row">
                    <label class="rm-field">
                        <span>Sucursal</span>
                        <select wire:model="serviceBranchId">
                            <option value="">Todas las sucursales</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        @error('serviceBranchId') <small>{{ $message }}</small> @enderror
                    </label>

                    <label class="rm-field">
                        <span>Estado</span>
                        <select wire:model="serviceStatus">
                            <option value="available">Disponible</option>
                            <option value="unavailable">No disponible</option>
                        </select>
                        @error('serviceStatus') <small>{{ $message }}</small> @enderror
                    </label>
                </div>

                <div class="rm-form-actions">
                    <button class="rm-button rm-button-primary" type="submit">{{ $editingServiceId ? 'Guardar servicio' : 'Crear servicio' }}</button>
                    <button class="rm-button rm-button-outline" type="button" wire:click="closeServiceModal">Cancelar</button>
                </div>
            </form>
        </section>
    @endif

    @if ($showPackageModal)
        <div class="rm-modal-backdrop" wire:click="closePackageModal"></div>
        <section class="rm-modal-panel rm-modal-panel-wide" role="dialog" aria-modal="true">
            <div class="rm-modal-title">
                <div>
                    <span>{{ $editingPackageId ? 'Editar paquete' : 'Nuevo paquete' }}</span>
                    <h2>{{ $editingPackageId ? 'Editar paquete' : 'Crear paquete' }}</h2>
                </div>
                <button type="button" wire:click="closePackageModal" aria-label="Cerrar modal">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <form wire:submit="savePackage" class="rm-form-stack">
                <label class="rm-field">
                    <span>Nombre</span>
                    <input wire:model="packageName" type="text" placeholder="Paquete renovacion mensual">
                    @error('packageName') <small>{{ $message }}</small> @enderror
                </label>

                <label class="rm-field">
                    <span>Descripcion</span>
                    <input wire:model="packageDescription" type="text" placeholder="Detalle del paquete">
                    @error('packageDescription') <small>{{ $message }}</small> @enderror
                </label>

                <div class="rm-form-row">
                    <label class="rm-field">
                        <span>Valor del paquete</span>
                        <input wire:model="packagePrice" type="number" min="0" step="0.01" placeholder="0.00">
                        @error('packagePrice') <small>{{ $message }}</small> @enderror
                    </label>

                    <label class="rm-field">
                        <span>Estado</span>
                        <select wire:model="packageStatus">
                            <option value="available">Disponible</option>
                            <option value="unavailable">No disponible</option>
                        </select>
                        @error('packageStatus') <small>{{ $message }}</small> @enderror
                    </label>
                </div>

                <div class="rm-form-row">
                    <label class="rm-field">
                        <span>Disponible desde</span>
                        <input wire:model="startsAt" type="date">
                        @error('startsAt') <small>{{ $message }}</small> @enderror
                    </label>

                    <label class="rm-field">
                        <span>Disponible hasta</span>
                        <input wire:model="expiresAt" type="date">
                        @error('expiresAt') <small>{{ $message }}</small> @enderror
                    </label>
                </div>

                <label class="rm-field">
                    <span>Sucursal</span>
                    <select wire:model="packageBranchId">
                        <option value="">Todas las sucursales</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                    @error('packageBranchId') <small>{{ $message }}</small> @enderror
                </label>

                <div class="rm-field">
                    <span>Servicios incluidos</span>
                    <div class="rm-check-grid">
                        @forelse ($packageServiceOptions as $service)
                            <label class="rm-check-option">
                                <input wire:model="packageServiceIds" type="checkbox" value="{{ $service->id }}">
                                <span>{{ $service->name }}</span>
                                <small>Bs {{ $service->price }}</small>
                            </label>
                        @empty
                            <div class="rm-empty-state">
                                <strong>Sin servicios disponibles</strong>
                                <span>Crea servicios antes de armar paquetes.</span>
                            </div>
                        @endforelse
                    </div>
                    @error('packageServiceIds.*') <small>{{ $message }}</small> @enderror
                </div>

                <div class="rm-form-actions">
                    <button class="rm-button rm-button-primary" type="submit">{{ $editingPackageId ? 'Guardar paquete' : 'Crear paquete' }}</button>
                    <button class="rm-button rm-button-outline" type="button" wire:click="closePackageModal">Cancelar</button>
                </div>
            </form>
        </section>
    @endif

    @if ($confirmingServiceDeleteId)
        <div class="rm-modal-backdrop" wire:click="$set('confirmingServiceDeleteId', null)"></div>
        <section class="rm-modal-panel rm-modal-panel-small" role="dialog" aria-modal="true">
            <div class="rm-confirm-icon">!</div>
            <h2>Eliminar servicio</h2>
            <p>Se eliminara este servicio del catalogo de este negocio.</p>
            <div class="rm-form-actions">
                <button class="rm-button rm-button-danger" type="button" wire:click="deleteService({{ $confirmingServiceDeleteId }})">Eliminar</button>
                <button class="rm-button rm-button-outline" type="button" wire:click="$set('confirmingServiceDeleteId', null)">Cancelar</button>
            </div>
        </section>
    @endif

    @if ($confirmingPackageDeleteId)
        <div class="rm-modal-backdrop" wire:click="$set('confirmingPackageDeleteId', null)"></div>
        <section class="rm-modal-panel rm-modal-panel-small" role="dialog" aria-modal="true">
            <div class="rm-confirm-icon">!</div>
            <h2>Eliminar paquete</h2>
            <p>Se eliminara este paquete y su configuracion de servicios incluidos.</p>
            <div class="rm-form-actions">
                <button class="rm-button rm-button-danger" type="button" wire:click="deletePackage({{ $confirmingPackageDeleteId }})">Eliminar</button>
                <button class="rm-button rm-button-outline" type="button" wire:click="$set('confirmingPackageDeleteId', null)">Cancelar</button>
            </div>
        </section>
    @endif
</div>
