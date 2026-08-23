<div class="rm-content rm-settings-page">
    <div class="rm-settings-hero">
        <div>
            <span>Gestion financiera</span>
            <h1>Gastos</h1>
            <p>Registra gastos de caja, gastos externos y pagos vinculados al personal por sucursal.</p>
        </div>
        <div class="rm-settings-summary">
            <strong>{{ \App\Support\Money::symbol() }} {{ number_format($summary['total'], 2) }}</strong>
            <span>{{ ucfirst($periodLabel) }}</span>
        </div>
    </div>

    <div class="rm-kpi-strip rm-inventory-kpis">
        <div class="rm-kpi"><strong>{{ \App\Support\Money::symbol() }} {{ number_format($summary['cashbox'], 2) }}</strong><span>Gasto de caja</span></div>
        <div class="rm-kpi"><strong>{{ \App\Support\Money::symbol() }} {{ number_format($summary['external'], 2) }}</strong><span>Gasto externo</span></div>
        <div class="rm-kpi"><strong>{{ \App\Support\Money::symbol() }} {{ number_format($summary['staff'], 2) }}</strong><span>Personal</span></div>
        <div class="rm-kpi"><strong>{{ $branch->name }}</strong><span>Sucursal actual</span></div>
    </div>

    <section class="rm-panel rm-catalog-panel">
        <div class="rm-tab-switcher rm-tab-switcher-four" role="tablist" aria-label="Gastos">
            <button class="{{ $activeTab === 'register' ? 'is-active' : '' }}" type="button" wire:click="setActiveTab('register')">Registrar</button>
            <button class="{{ $activeTab === 'history' ? 'is-active' : '' }}" type="button" wire:click="setActiveTab('history')">Historial</button>
            <button class="{{ $activeTab === 'types' ? 'is-active' : '' }}" type="button" wire:click="setActiveTab('types')">Tipos</button>
            <button class="{{ $activeTab === 'staff' ? 'is-active' : '' }}" type="button" wire:click="setActiveTab('staff')">Personal</button>
        </div>

        <div class="rm-action-row">
            <label class="rm-field">
                <span>Desde</span>
                <input wire:model.live="dateFrom" type="date">
            </label>
            <label class="rm-field">
                <span>Hasta</span>
                <input wire:model.live="dateTo" type="date">
            </label>
            @if (in_array($activeTab, ['history', 'staff'], true))
                <div class="rm-field rm-labeled-search-field">
                    <span>Buscador</span>
                    <label class="rm-search-field">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Buscar gasto, tipo o personal">
                    </label>
                </div>
            @endif
        </div>

        @if ($activeTab === 'register')
            <div class="rm-panel-title">
                <div><h2>Nuevo gasto</h2></div>
                <button class="rm-button rm-button-primary" type="button" wire:click="createExpense">Registrar gasto</button>
            </div>
            <div class="rm-empty-state">
                <strong>Elige el origen del dinero en cada gasto.</strong>
                <span>Caja descuenta del efectivo operativo. Externo queda como gasto general sin tocar caja.</span>
            </div>
        @endif

        @if ($activeTab === 'history')
            <div class="rm-panel-title">
                <div><h2>Historial de gastos {{ $periodLabel }}</h2></div>
                <button class="rm-button rm-button-primary" type="button" wire:click="createExpense">Registrar gasto</button>
            </div>
            <div class="rm-commerce-list">
                @forelse ($expenses as $expense)
                    <article class="rm-commerce-row">
                        <div class="rm-commerce-icon">{{ $expense->spent_at->format('d') }}</div>
                        <div class="rm-row-main">
                            <strong>{{ $expense->type?->name }} - {{ \App\Support\Money::symbol() }} {{ number_format((float) $expense->amount, 2) }}</strong>
                            <span>{{ $expense->spent_at->format('d/m/Y') }} - {{ $expense->source === 'cashbox' ? 'Caja' : 'Externo' }}</span>
                            <div class="rm-commerce-meta">
                                <span>Responsable: {{ $expense->createdBy?->name ?? 'Sin responsable' }}</span>
                                @if ($expense->staffUser)
                                    <span>Personal: {{ $expense->staffUser->name }}</span>
                                @endif
                                <span>{{ $expense->reference ?? 'Sin referencia' }}</span>
                                @if ($expense->description)<span>{{ $expense->description }}</span>@endif
                            </div>
                        </div>
                        <div class="rm-commerce-actions">
                            <button type="button" wire:click="editExpense({{ $expense->id }})">Editar</button>
                            <button type="button" wire:click="confirmDeleteExpense({{ $expense->id }})">Eliminar</button>
                        </div>
                    </article>
                @empty
                    <div class="rm-empty-state"><strong>Sin gastos</strong><span>Registra gastos de caja, externos o relacionados con personal.</span></div>
                @endforelse
            </div>
            <div class="rm-pagination-wrap">{{ $expenses->links('vendor.pagination.rumika') }}</div>
        @endif

        @if ($activeTab === 'types')
            <div class="rm-panel-title">
                <div><h2>Tipos de gasto</h2></div>
                <button class="rm-button rm-button-primary" type="button" wire:click="createType">Nuevo tipo</button>
            </div>
            <div class="rm-commerce-list">
                @forelse ($types as $type)
                    <article class="rm-commerce-row">
                        <div class="rm-commerce-icon">{{ strtoupper(substr($type->name, 0, 1)) }}</div>
                        <div class="rm-row-main">
                            <strong>{{ $type->name }}</strong>
                            <span>{{ $type->description ?? 'Sin descripcion' }}</span>
                            <div class="rm-commerce-meta">
                                <span>{{ $type->default_source === 'cashbox' ? 'Caja por defecto' : 'Externo por defecto' }}</span>
                                <span>{{ $type->requires_staff ? 'Requiere personal' : 'No requiere personal' }}</span>
                                <span>{{ ucfirst($type->status) }}</span>
                            </div>
                        </div>
                        <div class="rm-commerce-actions">
                            <button type="button" wire:click="editType({{ $type->id }})">Editar</button>
                            <button type="button" wire:click="confirmDeleteType({{ $type->id }})">Eliminar</button>
                        </div>
                    </article>
                @empty
                    <div class="rm-empty-state"><strong>Sin tipos</strong><span>Crea tipos como adelanto, pago al personal, limpieza, servicios basicos o compras externas.</span></div>
                @endforelse
            </div>
        @endif

        @if ($activeTab === 'staff')
            <div class="rm-panel-title">
                <div><h2>Resumen por personal {{ $periodLabel }}</h2></div>
            </div>
            <div class="rm-commerce-list">
                @forelse ($staffTotals as $row)
                    <article class="rm-commerce-row">
                        <div class="rm-user-avatar">{{ strtoupper(substr($row['name'], 0, 1)) }}</div>
                        <div class="rm-row-main">
                            <strong>{{ $row['name'] }}</strong>
                            <span>{{ \App\Support\Money::symbol() }} {{ number_format($row['total'], 2) }} {{ $periodLabel }}</span>
                            <div class="rm-commerce-meta">
                                <span>{{ $row['count'] }} movimiento(s)</span>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rm-empty-state"><strong>Sin pagos a personal</strong><span>Los tipos marcados como personal se sumaran aqui mensualmente.</span></div>
                @endforelse
            </div>
        @endif
    </section>

    @include('livewire.finance.partials.expense-modals')
</div>
