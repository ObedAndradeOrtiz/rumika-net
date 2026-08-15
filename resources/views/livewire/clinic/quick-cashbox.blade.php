<div>
    <button class="rm-top-wallet-button" type="button" wire:click="open" aria-label="Ver caja actual" title="Ver caja actual">
        <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><path d="M19 7V5a2 2 0 0 0-2-2H5a3 3 0 0 0 0 6h14a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H5a3 3 0 0 1-3-3V6"/><path d="M16 14h.01"/></svg>
        <span>Caja / turnos</span>
    </button>

    @if ($showModal)
        <div class="rm-modal-backdrop" wire:click="close"></div>
        <div class="rm-modal-panel rm-modal-panel-wide rm-quick-cashbox-modal">
            <div class="rm-modal-title">
                <div>
                    <span>Caja actual</span>
                    <h2>{{ $cashboxSession?->status === 'open' ? 'Caja abierta' : 'Resumen del dia' }}</h2>
                    <p class="rm-modal-subtitle">
                        {{ $cashboxSession?->status === 'closed' ? 'Cerrada' : ($cashboxSession ? 'Abierta' : 'Sin abrir') }}
                        @if ($cashboxSession)
                            - turno {{ $cashboxSession->shift_number }}
                        @endif
                        @if ($cashboxSession?->openedBy)
                            por {{ $cashboxSession->openedBy->name }}
                        @endif
                        @if ($cashboxSession?->closedBy)
                            - cierre: {{ $cashboxSession->closedBy->name }}
                        @endif
                    </p>
                </div>
                <button type="button" wire:click="close" aria-label="Cerrar">x</button>
            </div>

            <div class="rm-quick-cashbox-body">
                <div class="rm-quick-cashbox-toolbar">
                    <label class="rm-field">
                        <span>Fecha</span>
                        <input wire:model.live="selectedDate" type="date">
                    </label>
                    @if ($cashboxSession?->status === 'open')
                        <label class="rm-field">
                            <span>Conteo al cierre</span>
                            <input wire:model="countedCashAmount" type="number" step="0.01" min="0" placeholder="Opcional">
                            @error('countedCashAmount') <small>{{ $message }}</small> @enderror
                        </label>
                    @else
                        <label class="rm-field">
                            <span>Monto inicial</span>
                            <input wire:model="openingAmount" type="number" step="0.01" min="0" placeholder="0.00">
                            @error('openingAmount') <small>{{ $message }}</small> @enderror
                        </label>
                    @endif
                    <div class="rm-quick-cashbox-actions">
                        @if ($cashboxSession?->status === 'open')
                            <button class="rm-button rm-button-outline" type="button" wire:click="closeCashbox">Cerrar caja</button>
                        @else
                            <button class="rm-button rm-button-outline" type="button" wire:click="openCashbox">Abrir caja</button>
                        @endif
                        <button class="rm-button rm-button-primary" type="button" wire:click="previewPrint">Imprimir</button>
                    </div>
                </div>

                <div class="rm-form-row">
                    <label class="rm-field">
                        <span>Nota de apertura</span>
                        <input wire:model="openingNotes" type="text" placeholder="Opcional">
                        @error('openingNotes') <small>{{ $message }}</small> @enderror
                    </label>
                    <label class="rm-field">
                        <span>Nota de cierre</span>
                        <input wire:model="closingNotes" type="text" placeholder="Opcional">
                        @error('closingNotes') <small>{{ $message }}</small> @enderror
                    </label>
                </div>

                @if ($cashboxMessage)
                    <div class="rm-inline-notice">{{ $cashboxMessage }}</div>
                @endif

                <div class="rm-kpi-strip rm-inventory-kpis rm-quick-cash-kpis">
                    <div class="rm-kpi"><strong>Bs {{ number_format($cashTotal, 2) }}</strong><span>Efectivo bruto</span></div>
                    <div class="rm-kpi"><strong>Bs {{ number_format($qrTotal, 2) }}</strong><span>QR</span></div>
                    <div class="rm-kpi"><strong>Bs {{ number_format($cashboxExpenseTotal, 2) }}</strong><span>Gastos caja</span></div>
                    <div class="rm-kpi"><strong>Bs {{ number_format($netCashTotal, 2) }}</strong><span>Caja neta</span></div>
                    <div class="rm-kpi"><strong>Bs {{ number_format($netTotal, 2) }}</strong><span>Total neto</span></div>
                    <div class="rm-kpi"><strong>Bs {{ number_format($invoiceTotal, 2) }}</strong><span>Facturar</span></div>
                </div>

                <div class="rm-tab-switcher rm-cashbox-history-tabs" role="tablist" aria-label="Historial de caja">
                    <button class="{{ $historyTab === 'services' ? 'is-active' : '' }}" type="button" wire:click="setHistoryTab('services')">Servicios <span>{{ $historyRows['services']->count() }}</span></button>
                    <button class="{{ $historyTab === 'products' ? 'is-active' : '' }}" type="button" wire:click="setHistoryTab('products')">Productos <span>{{ $historyRows['products']->count() }}</span></button>
                    <button class="{{ $historyTab === 'expenses' ? 'is-active' : '' }}" type="button" wire:click="setHistoryTab('expenses')">Gastos <span>{{ $historyRows['expenses']->count() }}</span></button>
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
                            <article class="rm-quick-payment-row">
                                <div>
                                    <strong>{{ $row['client'] }}</strong>
                                    <span>{{ $row['time'] }} - {{ $row['name'] }} @if($row['quantity'] > 1) x {{ number_format($row['quantity'], 2) }} @endif</span>
                                </div>
                                <div class="rm-commerce-meta">
                                    <span>{{ $this->paymentMethodLabel($row['method']) }}</span>
                                    <span>Total Bs {{ number_format($row['total'], 2) }}</span>
                                    <span>Efectivo Bs {{ number_format($row['cash'], 2) }}</span>
                                    <span>QR Bs {{ number_format($row['qr'], 2) }}</span>
                                </div>
                            </article>
                        @empty
                            <div class="rm-empty-state"><strong>Sin ingresos de servicios</strong><span>No hay servicios para este filtro.</span></div>
                        @endforelse
                    @endif

                    @if ($historyTab === 'products')
                        @forelse ($historyRows['products'] as $row)
                            <article class="rm-quick-payment-row">
                                <div>
                                    <strong>{{ $row['client'] }}</strong>
                                    <span>{{ $row['time'] }} - {{ $row['name'] }} @if($row['quantity'] > 1) x {{ number_format($row['quantity'], 2) }} @endif</span>
                                </div>
                                <div class="rm-commerce-meta">
                                    <span>{{ $this->paymentMethodLabel($row['method']) }}</span>
                                    <span>Total Bs {{ number_format($row['total'], 2) }}</span>
                                    <span>Efectivo Bs {{ number_format($row['cash'], 2) }}</span>
                                    <span>QR Bs {{ number_format($row['qr'], 2) }}</span>
                                </div>
                            </article>
                        @empty
                            <div class="rm-empty-state"><strong>Sin ingresos de productos</strong><span>No hay productos para este filtro.</span></div>
                        @endforelse
                    @endif

                    @if ($historyTab === 'expenses')
                        @forelse ($historyRows['expenses'] as $expense)
                            <article class="rm-quick-payment-row">
                                <div>
                                    <strong>{{ $expense->type?->name ?? 'Gasto' }}</strong>
                                    <span>{{ $expense->spent_at?->format('d/m/Y') }} - Bs {{ number_format((float) $expense->amount, 2) }}</span>
                                </div>
                                <div class="rm-commerce-meta">
                                    <span>{{ $this->expenseSourceLabel($expense->source) }}</span>
                                    <span>Responsable {{ $expense->createdBy?->name ?? 'Sin responsable' }}</span>
                                    @if ($expense->staffUser)<span>Personal {{ $expense->staffUser->name }}</span>@endif
                                    @if ($expense->reference)<span>{{ $expense->reference }}</span>@endif
                                    @if ($expense->description)<span>{{ $expense->description }}</span>@endif
                                </div>
                            </article>
                        @empty
                            <div class="rm-empty-state"><strong>Sin gastos</strong><span>No hay gastos para este filtro.</span></div>
                        @endforelse
                    @endif
                </div>

                <section class="rm-ticket-history">
                    <div class="rm-panel-title rm-panel-title-compact">
                        <div>
                            <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M4 4h16v16l-4-2-4 2-4-2-4 2V4Z"/><path d="M8 8h8M8 12h8"/></svg>
                            <h3>Tickets guardados</h3>
                        </div>
                        <span>{{ $branch->uses_ticket_printer ? 'Impresora: '.($branch->printer_name ?: 'sin seleccionar') : 'Impresora desactivada' }}</span>
                    </div>
                    <div class="rm-commerce-list">
                        @forelse ($ticketRows as $ticket)
                            <article class="rm-quick-payment-row">
                                <div>
                                    <strong>{{ $ticket->title }} - {{ $ticket->ticket_number }}</strong>
                                    <span>{{ $ticket->created_at->format('d/m/Y H:i') }} @if($ticket->session) - turno {{ $ticket->session->shift_number }} @endif</span>
                                </div>
                                <div class="rm-commerce-meta">
                                    <span>{{ $ticket->status === 'printed' ? 'Impreso' : 'Generado' }}</span>
                                    <span>Reimpresiones {{ $ticket->reprint_count }}</span>
                                    @if ($ticket->printedBy)<span>{{ $ticket->printedBy->name }}</span>@endif
                                    <button type="button" wire:click="previewTicket({{ $ticket->id }})">Reimprimir</button>
                                    @if ($ticket->type === 'session_close' && $ticket->session?->status === 'closed' && $this->canManageCashboxClosures())
                                        <button type="button" wire:click="confirmDeleteClosedCashbox({{ $ticket->session->id }})">Eliminar cierre</button>
                                    @endif
                                </div>
                            </article>
                        @empty
                            <div class="rm-empty-state"><strong>Sin tickets</strong><span>Al abrir, cerrar o imprimir caja se guardara el ticket aqui.</span></div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    @endif

    @if ($showPrintPreview)
        @php
            $previewServices = collect($ticketPreview['services'] ?? $printSummary['services']['rows']);
            $previewProducts = collect($ticketPreview['products'] ?? $printSummary['products']['rows']);
            $previewTotals = $ticketPreview['totals'] ?? [
                'cash' => $cashTotal,
                'qr' => $qrTotal,
                'expenses' => $cashboxExpenseTotal,
                'net_cash' => $netCashTotal,
                'net_total' => $netTotal,
            ];
        @endphp
        <div class="rm-modal-backdrop" wire:click="closePrintPreview"></div>
        <section class="rm-modal-panel rm-modal-panel-wide rm-print-preview-modal" role="dialog" aria-modal="true">
            <div class="rm-modal-title">
                <div>
                    <span>Previsualizacion</span>
                    <h2>{{ $ticketPreview['title'] ?? 'Impresion de caja' }}</h2>
                    <p class="rm-modal-subtitle">
                        {{ $ticketPreview['branch'] ?? $branch->name }} - {{ $ticketPreview['business_date'] ?? \Illuminate\Support\Carbon::parse($selectedDate)->format('d/m/Y') }}
                        @if (! empty($ticketPreview['shift_number'])) - turno {{ $ticketPreview['shift_number'] }} @endif
                    </p>
                </div>
                <button type="button" wire:click="closePrintPreview" aria-label="Cerrar">x</button>
            </div>

            <div class="rm-print-preview-paper">
                <div class="rm-print-header">
                    <strong>Rumika - {{ $ticketPreview['title'] ?? 'Detalle de caja' }}</strong>
                    <span>{{ $ticketPreview['ticket_number'] ?? 'Ticket sin numero' }}</span>
                    <span>{{ $ticketPreview['branch'] ?? $branch->name }} - {{ $ticketPreview['business_date'] ?? \Illuminate\Support\Carbon::parse($selectedDate)->format('d/m/Y') }}</span>
                    @if (! empty($ticketPreview['opened_at']))<span>Desde: {{ $ticketPreview['opened_at'] }}</span>@endif
                    @if (! empty($ticketPreview['closed_at']))<span>Hasta: {{ $ticketPreview['closed_at'] }}</span>@endif
                    <span>Estado: {{ ($ticketPreview['status'] ?? $cashboxSession?->status) === 'closed' ? 'Cerrada' : (($ticketPreview['status'] ?? $cashboxSession?->status) ? 'Abierta' : 'Sin abrir') }}</span>
                    @if (! empty($ticketPreview['opened_by']))<span>Apertura: {{ $ticketPreview['opened_by'] }}</span>@endif
                    @if (! empty($ticketPreview['closed_by']))<span>Cierre: {{ $ticketPreview['closed_by'] }}</span>@endif
                </div>

                @foreach (['services' => 'Servicios', 'products' => 'Productos'] as $key => $title)
                    <div class="rm-print-section rm-print-compact-section">
                        <h3>{{ $title }}</h3>
                        <div class="rm-print-table">
                            <div class="rm-print-row rm-print-row-head"><span>Detalle</span><span>Efectivo</span><span>QR</span></div>
                            @forelse (($key === 'services' ? $previewServices : $previewProducts) as $row)
                                <div class="rm-print-row">
                                    <span>
                                        @if ($key === 'services')
                                            {{ $row['client'] }}
                                        @else
                                            {{ \Illuminate\Support\Str::limit($row['name'], 30, '') }}@if($row['quantity'] > 1) x {{ number_format($row['quantity'], 2) }}@endif
                                        @endif
                                    </span>
                                    <span>Bs {{ number_format($row['cash'], 2) }}</span>
                                    <span>Bs {{ number_format($row['qr'], 2) }}</span>
                                </div>
                            @empty
                                <div class="rm-print-empty">Sin {{ strtolower($title) }}.</div>
                            @endforelse
                            <div class="rm-print-row rm-print-row-total">
                                <span>Total {{ strtolower($title) }}</span>
                                <span>Bs {{ number_format(($key === 'services' ? $previewServices : $previewProducts)->sum('cash'), 2) }}</span>
                                <span>Bs {{ number_format(($key === 'services' ? $previewServices : $previewProducts)->sum('qr'), 2) }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach

                @if (! empty($ticketPreview['expenses']))
                    <div class="rm-print-section">
                        <h3>Gastos de caja</h3>
                        <div class="rm-print-table">
                            <div class="rm-print-row rm-print-row-head"><span>Detalle</span><span>Monto</span><span>Responsable</span><span>Ref.</span></div>
                            @foreach ($ticketPreview['expenses'] as $expense)
                                <div class="rm-print-row">
                                    <span>{{ $expense['name'] }}</span>
                                    <span>Bs {{ number_format($expense['amount'], 2) }}</span>
                                    <span>{{ $expense['responsible'] }}</span>
                                    <span>{{ $expense['reference'] ?: '-' }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="rm-print-totals">
                    @if (isset($previewTotals['opening_amount']))<span>Monto inicial Bs {{ number_format($previewTotals['opening_amount'], 2) }}</span>@endif
                    <span>Efectivo Bs {{ number_format($previewTotals['cash'] ?? 0, 2) }}</span>
                    <span>QR Bs {{ number_format($previewTotals['qr'] ?? 0, 2) }}</span>
                    <span>Gastos caja Bs {{ number_format($previewTotals['expenses'] ?? 0, 2) }}</span>
                    <strong>Caja neta Bs {{ number_format($previewTotals['net_cash'] ?? 0, 2) }}</strong>
                    @if (isset($previewTotals['expected_cash_amount']))<span>Esperado en caja Bs {{ number_format($previewTotals['expected_cash_amount'], 2) }}</span>@endif
                    @if (isset($previewTotals['counted_cash_amount']))<span>Contado Bs {{ number_format($previewTotals['counted_cash_amount'], 2) }}</span>@endif
                    @if (isset($previewTotals['cash_difference']))<span>Diferencia Bs {{ number_format($previewTotals['cash_difference'], 2) }}</span>@endif
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
                <button class="rm-button rm-button-outline" type="button" wire:click="closePrintPreview">Volver</button>
            </div>
        </section>
    @endif

    @if ($confirmingCashboxSessionDeleteId)
        <div class="rm-modal-backdrop" wire:click="cancelDeleteClosedCashbox"></div>
        <section class="rm-modal-panel rm-modal-panel-small" role="dialog" aria-modal="true">
            <div class="rm-confirm-icon">!</div>
            <h2>Eliminar caja</h2>
            <p>Se eliminara esta caja y sus tickets guardados. Los cobros, productos vendidos y movimientos no se borran. Si era la unica caja del dia, podras abrir nuevamente desde 0.</p>
            <div class="rm-form-actions">
                <button class="rm-button rm-button-danger" type="button" wire:click="deleteClosedCashbox">Eliminar caja</button>
                <button class="rm-button rm-button-outline" type="button" wire:click="cancelDeleteClosedCashbox">Cancelar</button>
            </div>
        </section>
    @endif
</div>
