<div class="rm-content rm-report-page rm-commission-page">
    <section class="rm-settings-hero">
        <div>
            <span class="rm-kicker">Administracion</span>
            <h1>Metas y comisiones</h1>
            <p>Controla ventas de productos, servicios agregados y comisiones por personal.</p>
        </div>
    </section>

    <section class="rm-panel rm-report-filters">
        <div class="rm-filter-row rm-commission-filter-row">
            <label class="rm-field"><span>Desde</span><input wire:model.live="dateFrom" type="date"></label>
            <label class="rm-field"><span>Hasta opcional</span><input wire:model.live="dateTo" type="date"></label>
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
                <span>Meta</span>
                <select wire:model.live="periodFilter">
                    @foreach ($periods as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <button class="rm-button rm-button-primary rm-report-download-button" type="button" wire:click="createTarget">Nueva meta</button>
        </div>
    </section>

    <section class="rm-kpi-strip rm-report-kpis">
        <div class="rm-kpi"><strong>{{ $currency }} {{ number_format((float) $totals['sales'], 2) }}</strong><span>Ventas del rango</span></div>
        <div class="rm-kpi"><strong>{{ $currency }} {{ number_format((float) $totals['services'], 2) }}</strong><span>Servicios agregados</span></div>
        <div class="rm-kpi"><strong>{{ $currency }} {{ number_format((float) $totals['products'], 2) }}</strong><span>Productos vendidos</span></div>
        <div class="rm-kpi"><strong>{{ $currency }} {{ number_format((float) $totals['commission'], 2) }}</strong><span>Comision estimada</span></div>
        <div class="rm-kpi"><strong>{{ $currency }} {{ number_format((float) $totals['pending_sales'], 2) }}</strong><span>Falta para metas</span></div>
    </section>

    <section class="rm-panel rm-report-section">
        <div class="rm-panel-title"><div><h2>Cumplimiento por personal</h2><p>{{ $rangeLabel }}</p></div></div>
        <div class="rm-report-table rm-commission-table">
            <div class="rm-report-table-head">
                <span>Personal</span><span>Sucursal</span><span>Servicios</span><span>Productos</span><span>Total</span><span>Comision</span><span>Meta ventas</span><span>Estado</span>
            </div>
            @forelse ($rows as $row)
                <div class="rm-report-table-row">
                    <strong>{{ $row['name'] }}</strong>
                    <span>{{ $row['branch'] }}</span>
                    <span>{{ $currency }} {{ number_format((float) $row['services'], 2) }}</span>
                    <span>{{ $currency }} {{ number_format((float) $row['products'], 2) }}</span>
                    <span>{{ $currency }} {{ number_format((float) $row['total_sales'], 2) }}</span>
                    <span>{{ $currency }} {{ number_format((float) $row['commission'], 2) }}</span>
                    <span>{{ $currency }} {{ number_format((float) $row['target_sales'], 2) }}</span>
                    <span class="{{ $row['target_met'] ? 'is-ok' : 'is-alert' }}">{{ $row['target_met'] ? 'Cumplida' : 'Falta '.$currency.' '.number_format((float) $row['sales_shortfall'], 2) }}</span>
                </div>
            @empty
                <div class="rm-empty-state">Sin ventas ni metas para este filtro.</div>
            @endforelse
        </div>
    </section>

    <section class="rm-panel rm-report-section">
        <div class="rm-panel-title"><div><h2>Metas configuradas</h2><p>Semanal, quincenal o mensual por personal y opcionalmente por sucursal.</p></div></div>
        <div class="rm-commerce-list">
            @forelse ($targets as $target)
                <article class="rm-commerce-row">
                    <div class="rm-commerce-icon">{{ strtoupper(substr($target->user?->name ?? 'M', 0, 1)) }}</div>
                    <div class="rm-row-main">
                        <strong>{{ $target->user?->name ?? 'Sin personal' }}</strong>
                        <span>{{ $target->branch?->name ?? 'Todas las sucursales' }} - {{ $periods[$target->period_type] ?? $target->period_type }}</span>
                        <div class="rm-commerce-meta">
                            <span>Venta minima {{ $currency }} {{ number_format((float) $target->minimum_sales_amount, 2) }}</span>
                            <span>Comision minima {{ $currency }} {{ number_format((float) $target->minimum_commission_amount, 2) }}</span>
                            <span>{{ $target->status === 'active' ? 'Activa' : 'Inactiva' }}</span>
                        </div>
                    </div>
                    <div class="rm-commerce-actions">
                        <button type="button" wire:click="editTarget({{ $target->id }})">Editar</button>
                        <button type="button" wire:click="confirmDeleteTarget({{ $target->id }})">Eliminar</button>
                    </div>
                </article>
            @empty
                <div class="rm-empty-state">Aun no hay metas. Crea una para empezar a medir cumplimiento.</div>
            @endforelse
        </div>
    </section>

    @if ($showTargetModal)
        <div class="rm-modal-backdrop" wire:click="$set('showTargetModal', false)"></div>
        <section class="rm-modal-panel rm-modal-panel-wide" role="dialog" aria-modal="true">
            <div class="rm-modal-title">
                <div><span>Comisiones</span><h2>{{ $editingTargetId ? 'Editar meta' : 'Nueva meta' }}</h2></div>
                <button type="button" wire:click="$set('showTargetModal', false)">x</button>
            </div>
            <form wire:submit="saveTarget" class="rm-form-stack">
                <div class="rm-form-row">
                    <label class="rm-field">
                        <span>Personal</span>
                        <select wire:model="targetUserId">
                            <option value="">Seleccionar personal</option>
                            @foreach ($staffUsers as $staff)
                                <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                            @endforeach
                        </select>
                        @error('targetUserId')<small>{{ $message }}</small>@enderror
                    </label>
                    <label class="rm-field">
                        <span>Sucursal</span>
                        <select wire:model="targetBranchId">
                            <option value="">Todas las sucursales</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
                <div class="rm-form-row">
                    <label class="rm-field">
                        <span>Periodo</span>
                        <select wire:model="targetPeriodType">
                            @foreach ($periods as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="rm-field">
                        <span>Estado</span>
                        <select wire:model="targetStatus">
                            <option value="active">Activa</option>
                            <option value="inactive">Inactiva</option>
                        </select>
                    </label>
                </div>
                <div class="rm-form-row">
                    <label class="rm-field"><span>Venta minima</span><input wire:model="targetMinimumSales" type="number" min="0" step="0.01">@error('targetMinimumSales')<small>{{ $message }}</small>@enderror</label>
                    <label class="rm-field"><span>Comision minima</span><input wire:model="targetMinimumCommission" type="number" min="0" step="0.01">@error('targetMinimumCommission')<small>{{ $message }}</small>@enderror</label>
                </div>
                <div class="rm-form-actions"><button class="rm-button rm-button-primary" type="submit">Guardar meta</button></div>
            </form>
        </section>
    @endif

    @if ($confirmingTargetDeleteId)
        <div class="rm-modal-backdrop" wire:click="cancelDeleteTarget"></div>
        <section class="rm-modal-panel rm-modal-panel-small" role="dialog" aria-modal="true">
            <div class="rm-confirm-icon">!</div>
            <h2>Eliminar meta</h2>
            <p>Esto solo elimina la meta configurada. Las ventas y comisiones registradas no se modifican.</p>
            <div class="rm-form-actions">
                <button class="rm-button rm-button-danger" type="button" wire:click="deleteTarget({{ $confirmingTargetDeleteId }})">Eliminar</button>
                <button class="rm-button rm-button-outline" type="button" wire:click="cancelDeleteTarget">Cancelar</button>
            </div>
        </section>
    @endif
</div>
