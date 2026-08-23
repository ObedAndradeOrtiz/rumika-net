<div class="rm-content rm-settings-page">
    <section class="rm-settings-hero">
        <div>
            <span>Administracion</span>
            <h1>Bitacora del sistema</h1>
            <p>Registra ingresos, cierres de sesion, creaciones, ediciones y eliminaciones realizadas por el personal.</p>
        </div>
        <div class="rm-settings-summary">
            <strong>{{ $logs->count() }}</strong>
            <span>Ultimos registros visibles</span>
        </div>
    </section>

    <section class="rm-panel rm-catalog-panel">
        <div class="rm-filter-row rm-audit-filter-row">
            <label class="rm-field">
                <span>Desde</span>
                <input type="date" wire:model.live="dateFrom">
            </label>
            <label class="rm-field">
                <span>Hasta</span>
                <input type="date" wire:model.live="dateTo">
            </label>
            <label class="rm-field">
                <span>Personal</span>
                <select wire:model.live="userFilter">
                    <option value="">Todos</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="rm-field">
                <span>Modulo</span>
                <select wire:model.live="moduleFilter">
                    <option value="">Todos</option>
                    @foreach ($modules as $module)
                        <option value="{{ $module }}">{{ ucfirst($module) }}</option>
                    @endforeach
                </select>
            </label>
            <label class="rm-field">
                <span>Accion</span>
                <select wire:model.live="eventFilter">
                    <option value="">Todas</option>
                    @foreach ($events as $event)
                        <option value="{{ $event }}">{{ $event }}</option>
                    @endforeach
                </select>
            </label>
            <label class="rm-search-field rm-audit-search">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Buscar descripcion, modulo o personal">
            </label>
            <button class="rm-button rm-button-primary" type="button" wire:click="exportExcel">Exportar Excel</button>
        </div>

        <div class="rm-commerce-list">
            @forelse ($logs as $log)
                <article class="rm-commerce-row rm-audit-row">
                    <div class="rm-commerce-icon">{{ $log->occurred_at?->format('H:i') }}</div>
                    <div class="rm-row-main">
                        <strong>{{ $log->description }}</strong>
                        <span>{{ $log->occurred_at?->format('d/m/Y H:i:s') }} - {{ $log->user?->name ?? 'Sistema' }}</span>
                        <div class="rm-commerce-meta">
                            <span>{{ ucfirst($log->module) }}</span>
                            <span>{{ $log->event }}</span>
                            @if ($log->branch)
                                <span>{{ $log->branch->name }}</span>
                            @endif
                            @if ($log->ip_address)
                                <span>{{ $log->ip_address }}</span>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="rm-empty-state">
                    <strong>Sin registros</strong>
                    <span>No hay actividad en el rango seleccionado.</span>
                </div>
            @endforelse
        </div>
    </section>
</div>
