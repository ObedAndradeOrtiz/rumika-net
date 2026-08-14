<div class="rm-content rm-settings-page">
    <div class="rm-settings-hero">
        <div>
            <span>{{ $context === 'records' ? 'Registros administrativos' : 'Caja diaria' }}</span>
            <h1>{{ $context === 'records' ? 'Registros de caja' : 'Caja de tratamientos' }}</h1>
            <p>{{ $context === 'records' ? 'Consulta ingresos de servicios, productos y gastos por fecha, separados por tipo de pago.' : 'Resumen separado de efectivo, QR, gastos de caja y montos marcados para facturar.' }}</p>
        </div>
        <label class="rm-field">
            <span>Fecha</span>
            <input wire:model.live="selectedDate" type="date">
        </label>
    </div>

    <div class="rm-kpi-strip rm-inventory-kpis">
        <div class="rm-kpi"><strong>Bs {{ number_format((float) $cashTotal, 2) }}</strong><span>Efectivo bruto</span></div>
        <div class="rm-kpi"><strong>Bs {{ number_format((float) $qrTotal, 2) }}</strong><span>QR</span></div>
        <div class="rm-kpi"><strong>Bs {{ number_format((float) $cashboxExpenseTotal, 2) }}</strong><span>Gastos caja</span></div>
        <div class="rm-kpi"><strong>Bs {{ number_format((float) $netCashTotal, 2) }}</strong><span>Caja neta</span></div>
        <div class="rm-kpi"><strong>Bs {{ number_format((float) $netTotal, 2) }}</strong><span>Total neto</span></div>
        <div class="rm-kpi"><strong>Bs {{ number_format((float) $invoiceTotal, 2) }}</strong><span>Para facturar</span></div>
    </div>

    <section class="rm-panel rm-catalog-panel">
        <div class="rm-panel-title"><div><h2>{{ $context === 'records' ? 'Historial por categoria' : 'Pagos del dia' }}</h2></div></div>
        <div class="rm-tab-switcher rm-cashbox-history-tabs" role="tablist" aria-label="Historial de caja">
            <button class="{{ $historyTab === 'services' ? 'is-active' : '' }}" type="button" wire:click="setHistoryTab('services')">
                Servicios <span>{{ $historyRows['services']->count() }}</span>
            </button>
            <button class="{{ $historyTab === 'products' ? 'is-active' : '' }}" type="button" wire:click="setHistoryTab('products')">
                Productos <span>{{ $historyRows['products']->count() }}</span>
            </button>
            <button class="{{ $historyTab === 'expenses' ? 'is-active' : '' }}" type="button" wire:click="setHistoryTab('expenses')">
                Gastos del mes <span>{{ $historyRows['expenses']->count() }}</span>
            </button>
        </div>

        <div class="rm-cashbox-history-filters">
            @if (in_array($historyTab, ['services', 'products'], true))
                <label class="rm-field">
                    <span>Tipo de ingreso</span>
                    <select wire:model.live="paymentMethodFilter">
                        <option value="">Todos</option>
                        <option value="cash">Efectivo</option>
                        <option value="qr">QR</option>
                        <option value="mixed">Mixto</option>
                    </select>
                </label>
            @else
                <label class="rm-field">
                    <span>Tipo de gasto</span>
                    <select wire:model.live="expenseSourceFilter">
                        <option value="">Todos</option>
                        <option value="cashbox">Gasto de caja</option>
                        <option value="external">Gasto externo</option>
                    </select>
                </label>
            @endif
        </div>

        <div class="rm-commerce-list">
            @if ($historyTab === 'services')
                @forelse ($historyRows['services'] as $row)
                    <article class="rm-commerce-row rm-cashbox-record-row">
                        <div class="rm-commerce-icon rm-cashbox-record-icon">{{ $this->paymentMethodLabel($row['method']) }}</div>
                        <div class="rm-row-main">
                            <strong>{{ $row['client'] }} - Bs {{ number_format($row['total'], 2) }}</strong>
                            <span>{{ $row['date'] }} {{ $row['time'] }} - {{ $row['name'] }} @if ($row['quantity'] > 1) x {{ number_format($row['quantity'], 2) }} @endif</span>
                            <div class="rm-commerce-meta">
                                <span>Efectivo Bs {{ number_format($row['cash'], 2) }}</span>
                                <span>QR Bs {{ number_format($row['qr'], 2) }}</span>
                                <span>{{ $row['staff'] }}</span>
                                <span>{{ $row['invoice'] }}</span>
                                <span>{{ $row['reference'] }}</span>
                            </div>
                        </div>
                        <div class="rm-commerce-actions">
                            <button class="rm-button rm-button-outline" type="button" wire:click="previewPaymentTicket({{ $row['payment_id'] }})">Ticket</button>
                            @if ($canManageRecords)
                                <a class="rm-button rm-button-outline" href="{{ route('clinic.agenda', ['editar_cobro' => $row['payment_id']]) }}">Editar</a>
                                <button class="rm-button rm-button-danger" type="button" wire:click="confirmDeletePayment({{ $row['payment_id'] }})">Eliminar</button>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="rm-empty-state"><strong>Sin ingresos de servicios</strong><span>No hay servicios para esta fecha o filtro.</span></div>
                @endforelse
            @endif

            @if ($historyTab === 'products')
                @forelse ($historyRows['products'] as $row)
                    <article class="rm-commerce-row rm-cashbox-record-row">
                        <div class="rm-commerce-icon rm-cashbox-record-icon">{{ $this->paymentMethodLabel($row['method']) }}</div>
                        <div class="rm-row-main">
                            <strong>{{ $row['client'] }} - Bs {{ number_format($row['total'], 2) }}</strong>
                            <span>{{ $row['date'] }} {{ $row['time'] }} - {{ $row['name'] }} x {{ number_format($row['quantity'], 2) }}</span>
                            <div class="rm-commerce-meta">
                                <span>Efectivo Bs {{ number_format($row['cash'], 2) }}</span>
                                <span>QR Bs {{ number_format($row['qr'], 2) }}</span>
                                <span>{{ $row['staff'] }}</span>
                                <span>{{ $row['invoice'] }}</span>
                                <span>{{ $row['reference'] }}</span>
                            </div>
                        </div>
                        <div class="rm-commerce-actions">
                            <button class="rm-button rm-button-outline" type="button" wire:click="previewPaymentTicket({{ $row['payment_id'] }})">Ticket</button>
                            @if ($canManageRecords)
                                <a class="rm-button rm-button-outline" href="{{ route('clinic.agenda', ['editar_cobro' => $row['payment_id']]) }}">Editar</a>
                                <button class="rm-button rm-button-danger" type="button" wire:click="confirmDeletePayment({{ $row['payment_id'] }})">Eliminar</button>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="rm-empty-state"><strong>Sin ingresos de productos</strong><span>No hay productos para esta fecha o filtro.</span></div>
                @endforelse
            @endif

            @if ($historyTab === 'expenses')
                @forelse ($historyRows['expenses'] as $expense)
                    <article class="rm-commerce-row rm-cashbox-record-row">
                        <div class="rm-commerce-icon rm-cashbox-record-icon">{{ $this->expenseSourceLabel($expense->source) }}</div>
                        <div class="rm-row-main">
                            <strong>{{ $expense->type?->name ?? 'Gasto' }} - Bs {{ number_format((float) $expense->amount, 2) }}</strong>
                            <span>{{ $expense->spent_at?->format('d/m/Y') }} - Responsable {{ $expense->createdBy?->name ?? 'Sin responsable' }}</span>
                            <div class="rm-commerce-meta">
                                <span>{{ $this->expenseSourceLabel($expense->source) }}</span>
                                @if ($expense->staffUser)<span>Personal {{ $expense->staffUser->name }}</span>@endif
                                <span>{{ $expense->reference ?: 'Sin referencia' }}</span>
                                @if ($expense->description)<span>{{ $expense->description }}</span>@endif
                            </div>
                        </div>
                        @if ($canManageRecords)
                            <div class="rm-commerce-actions">
                                <button class="rm-button rm-button-outline" type="button" wire:click="editExpense({{ $expense->id }})">Editar</button>
                                <button class="rm-button rm-button-danger" type="button" wire:click="confirmDeleteExpense({{ $expense->id }})">Eliminar</button>
                            </div>
                        @endif
                    </article>
                @empty
                    <div class="rm-empty-state"><strong>Sin gastos</strong><span>No hay gastos para este mes o filtro.</span></div>
                @endforelse
            @endif
        </div>
    </section>

    @if ($showExpenseModal)
        <div class="rm-modal-backdrop" wire:click="closeExpenseModal"></div>
        <section class="rm-modal-panel rm-modal-panel-wide" role="dialog" aria-modal="true">
            <div class="rm-modal-title">
                <div><span>Registro administrativo</span><h2>Editar gasto</h2></div>
                <button type="button" wire:click="closeExpenseModal" aria-label="Cerrar">x</button>
            </div>
            <form wire:submit="saveExpense" class="rm-form-stack">
                <label class="rm-field">
                    <span>Tipo</span>
                    <select wire:model.live="expenseTypeId">
                        <option value="">Seleccionar tipo</option>
                        @foreach ($expenseTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                    @error('expenseTypeId') <small>{{ $message }}</small> @enderror
                </label>
                <div class="rm-form-row">
                    <label class="rm-field">
                        <span>Origen</span>
                        <select wire:model="expenseSource">
                            <option value="cashbox">Caja</option>
                            <option value="external">Externo</option>
                        </select>
                        @error('expenseSource') <small>{{ $message }}</small> @enderror
                    </label>
                    <label class="rm-field">
                        <span>Monto</span>
                        <input wire:model="expenseAmount" type="number" min="0.01" step="0.01">
                        @error('expenseAmount') <small>{{ $message }}</small> @enderror
                    </label>
                </div>
                <div class="rm-form-row">
                    <label class="rm-field">
                        <span>Fecha</span>
                        <input wire:model="expenseSpentAt" type="date">
                        @error('expenseSpentAt') <small>{{ $message }}</small> @enderror
                    </label>
                    <label class="rm-field">
                        <span>Personal</span>
                        <select wire:model="staffUserId">
                            <option value="">Sin personal</option>
                            @foreach ($staffUsers as $staff)
                                <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                            @endforeach
                        </select>
                        @error('staffUserId') <small>{{ $message }}</small> @enderror
                    </label>
                </div>
                <label class="rm-field"><span>Referencia</span><input wire:model="expenseReference" type="text">@error('expenseReference') <small>{{ $message }}</small> @enderror</label>
                <label class="rm-field"><span>Descripcion</span><textarea wire:model="expenseDescription"></textarea>@error('expenseDescription') <small>{{ $message }}</small> @enderror</label>
                <div class="rm-form-actions">
                    <button class="rm-button rm-button-primary" type="submit">Guardar gasto</button>
                    <button class="rm-button rm-button-outline" type="button" wire:click="closeExpenseModal">Cancelar</button>
                </div>
            </form>
        </section>
    @endif

    @if ($showTicketPreview)
        <div class="rm-modal-backdrop" wire:click="closeTicketPreview"></div>
        <section class="rm-modal-panel rm-modal-panel-wide rm-print-preview-modal" role="dialog" aria-modal="true">
            <div class="rm-modal-title">
                <div>
                    <span>Previsualizacion</span>
                    <h2>{{ $ticketPreview['title'] ?? 'Ticket de cobro' }}</h2>
                    <p class="rm-modal-subtitle">{{ $ticketPreview['branch'] ?? '' }} - {{ $ticketPreview['business_date'] ?? '' }}</p>
                </div>
                <button type="button" wire:click="closeTicketPreview" aria-label="Cerrar">x</button>
            </div>

            <div class="rm-print-preview-paper">
                <div class="rm-print-header">
                    <strong>Rumika - Ticket de cobro</strong>
                    <span>{{ $ticketPreview['ticket_number'] ?? 'Ticket sin numero' }}</span>
                    <span>Cliente: {{ $ticketPreview['client'] ?? 'Cliente' }}</span>
                    <span>Atendido por: {{ $ticketPreview['performed_by'] ?? 'Sin profesional' }}</span>
                    <span>Cajero: {{ $ticketPreview['received_by'] ?? 'Sin cajero' }}</span>
                </div>

                <div class="rm-print-section">
                    <h3>Detalle</h3>
                    <div class="rm-print-table">
                        <div class="rm-print-row rm-print-row-head"><span>Item</span><span>Total</span><span>Efectivo</span><span>QR</span></div>
                        @foreach (($ticketPreview['rows'] ?? []) as $row)
                            <div class="rm-print-row">
                                <span>{{ $row['type'] }} - {{ $row['name'] }} @if($row['quantity'] > 1) x {{ number_format($row['quantity'], 2) }} @endif</span>
                                <span>Bs {{ number_format($row['total'], 2) }}</span>
                                <span>Bs {{ number_format($row['cash'], 2) }}</span>
                                <span>Bs {{ number_format($row['qr'], 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                @if (! empty($ticketPreview['outstanding_charges']))
                    <div class="rm-print-section">
                        <h3>Saldos pendientes</h3>
                        <div class="rm-print-table">
                            <div class="rm-print-row rm-print-row-head"><span>Detalle</span><span>Total</span><span>Pagado</span><span>Saldo</span></div>
                            @foreach ($ticketPreview['outstanding_charges'] as $charge)
                                <div class="rm-print-row">
                                    <span>{{ $charge['type'] }} - {{ $charge['name'] }}</span>
                                    <span>Bs {{ number_format($charge['total'], 2) }}</span>
                                    <span>Bs {{ number_format($charge['paid'], 2) }}</span>
                                    <span>Bs {{ number_format($charge['balance'], 2) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="rm-print-totals">
                    <span>Efectivo Bs {{ number_format($ticketPreview['totals']['cash'] ?? 0, 2) }}</span>
                    <span>QR Bs {{ number_format($ticketPreview['totals']['qr'] ?? 0, 2) }}</span>
                    <strong>Total Bs {{ number_format($ticketPreview['totals']['total'] ?? 0, 2) }}</strong>
                    @if (! empty($ticketPreview['printer_enabled']))<span>Impresora {{ $ticketPreview['printer_name'] ?: 'sin seleccionar' }}</span>@endif
                </div>
            </div>

            <div class="rm-form-actions">
                <button
                    class="rm-button rm-button-primary"
                    type="button"
                    wire:click="markTicketPrinted"
                    data-use-qz="{{ ! empty($ticketPreview['printer_enabled']) && ! empty($ticketPreview['printer_name']) ? '1' : '0' }}"
                    data-printer-name="{{ $ticketPreview['printer_name'] ?? '' }}"
                    onclick="event.preventDefault(); window.RumikaQz.printFromButton(this)"
                >Imprimir ahora</button>
                <button class="rm-button rm-button-outline" type="button" wire:click="closeTicketPreview">Volver</button>
            </div>
        </section>
    @endif

    @if ($confirmingPaymentDeleteId)
        <div class="rm-modal-backdrop" wire:click="cancelDeletePayment"></div>
        <section class="rm-modal-panel rm-modal-panel-small" role="dialog" aria-modal="true">
            <div class="rm-confirm-icon">!</div>
            <h2>Eliminar cobro</h2>
            <p>Se eliminara el cobro completo, sus items, pagos aplicados y movimientos de inventario vinculados.</p>
            <div class="rm-form-actions">
                <button class="rm-button rm-button-danger" type="button" wire:click="deletePayment({{ $confirmingPaymentDeleteId }})">Eliminar</button>
                <button class="rm-button rm-button-outline" type="button" wire:click="cancelDeletePayment">Cancelar</button>
            </div>
        </section>
    @endif

    @if ($confirmingExpenseDeleteId)
        <div class="rm-modal-backdrop" wire:click="cancelDeleteExpense"></div>
        <section class="rm-modal-panel rm-modal-panel-small" role="dialog" aria-modal="true">
            <div class="rm-confirm-icon">!</div>
            <h2>Eliminar gasto</h2>
            <p>Se eliminara este gasto del historial y de los calculos de caja.</p>
            <div class="rm-form-actions">
                <button class="rm-button rm-button-danger" type="button" wire:click="deleteExpense({{ $confirmingExpenseDeleteId }})">Eliminar</button>
                <button class="rm-button rm-button-outline" type="button" wire:click="cancelDeleteExpense">Cancelar</button>
            </div>
        </section>
    @endif
</div>
