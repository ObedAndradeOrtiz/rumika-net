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
            <button class="rm-button rm-button-danger" type="button" wire:click="openRollbackPreview" wire:loading.attr="disabled">
                Preparar rollback
            </button>
        </div>

        @if ($rollbackMessage)
            <div class="rm-inline-success">{{ $rollbackMessage }}</div>
        @endif

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

    @if ($showRollbackModal)
        <div class="rm-modal-backdrop" wire:click="closeRollbackPreview"></div>
        <section class="rm-modal-panel rm-modal-panel-wide" role="dialog" aria-modal="true">
            <header class="rm-modal-header">
                <div>
                    <span>Rollback de bitacora</span>
                    <h2>Revertir movimientos de {{ $rollbackSummary['user'] ?? 'usuario' }}</h2>
                    <p class="rm-modal-subtitle">
                        Se usaran los filtros actuales. Esta accion modifica datos reales y debe hacerse solo si estas seguro.
                    </p>
                </div>
                <button class="rm-modal-close" type="button" wire:click="closeRollbackPreview" aria-label="Cerrar">×</button>
            </header>

            <div class="rm-audit-rollback-summary">
                <span><strong>{{ $rollbackSummary['total'] ?? 0 }}</strong> movimientos reversibles</span>
                <span><strong>{{ $rollbackSummary['created'] ?? 0 }}</strong> creados: se eliminaran</span>
                <span><strong>{{ $rollbackSummary['updated'] ?? 0 }}</strong> editados: volveran al valor anterior</span>
                <span><strong>{{ $rollbackSummary['deleted'] ?? 0 }}</strong> eliminados: se intentaran restaurar</span>
            </div>

            <div class="rm-audit-rollback-warning">
                Recomendado: filtra por persona, fecha y modulo antes de aplicar. El rollback no puede adivinar si otro usuario ya corrigio manualmente un registro.
            </div>

            <div class="rm-commerce-list rm-audit-rollback-list">
                @forelse ($rollbackPreviewRows as $row)
                    <article class="rm-commerce-row rm-audit-row">
                        <div class="rm-commerce-icon">{{ $row['event'] }}</div>
                        <div class="rm-row-main">
                            <strong>{{ $row['description'] }}</strong>
                            <span>{{ $row['date'] }} - {{ $row['module'] }}</span>
                            @if ($row['branch'])
                                <div class="rm-commerce-meta"><span>{{ $row['branch'] }}</span></div>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="rm-empty-state">
                        <strong>Sin movimientos reversibles</strong>
                        <span>Con estos filtros no hay creaciones, ediciones o eliminaciones para revertir.</span>
                    </div>
                @endforelse
            </div>

            <label class="rm-field">
                <span>Confirmacion</span>
                <input type="text" wire:model="rollbackConfirmation" placeholder="Escribe REVERSAR para aplicar">
            </label>

            <div class="rm-modal-actions">
                <button class="rm-button rm-button-outline" type="button" wire:click="closeRollbackPreview">Cancelar</button>
                <button class="rm-button rm-button-danger" type="button" wire:click="confirmRollback" wire:loading.attr="disabled">
                    Revertir movimientos
                </button>
            </div>
        </section>
    @endif
</div>
