@if ($showNoShowModal)
    <div
        class="rm-modal-backdrop"
        wire:click="$set('showNoShowModal', false)"
    ></div>

    <section
        class="rm-modal-panel rm-modal-panel-sm"
        role="dialog"
        aria-modal="true"
    >
        <div class="rm-modal-title">
            <div>
                <span>Asistencia</span>
                <h2>Registrar inasistencia</h2>
            </div>

            <button
                type="button"
                wire:click="$set('showNoShowModal', false)"
            >
                x
            </button>
        </div>

        <form
            wire:submit="confirmNoShow"
            class="rm-form-stack"
        >
            <p class="rm-modal-subtitle">
                Indica por qué el cliente no asistió. Esta información
                quedará registrada en su historial.
            </p>

            <label class="rm-field">
                <span>¿Por qué no asistió?</span>

                <textarea
                    wire:model="noShowReason"
                    rows="3"
                    placeholder="Ej. Cliente avisó que tuvo un inconveniente personal..."
                ></textarea>

                @error('noShowReason')
                    <small>{{ $message }}</small>
                @enderror
            </label>

            <label class="rm-check-option">
                <input
                    wire:model.live="noShowReschedule"
                    type="checkbox"
                >

                <span>Reagendar esta cita</span>

                <small>
                    Se creará automáticamente una nueva cita
                </small>
            </label>

            @if ($noShowReschedule)
                <div class="rm-form-row">

                    <label class="rm-field">
                        <span>Nueva fecha</span>

                        <input
                            wire:model="noShowRescheduleDate"
                            type="date"
                        >

                        @error('noShowRescheduleDate')
                            <small>{{ $message }}</small>
                        @enderror
                    </label>

                    <label class="rm-field">
                        <span>Nueva hora</span>

                        <input
                            wire:model="noShowRescheduleTime"
                            type="time"
                        >

                        @error('noShowRescheduleTime')
                            <small>{{ $message }}</small>
                        @enderror
                    </label>

                </div>
            @endif

            <div class="rm-form-actions">

                <button
                    class="rm-button rm-button-outline"
                    type="button"
                    wire:click="$set('showNoShowModal', false)"
                >
                    Cancelar
                </button>

                <button
                    class="rm-button rm-button-primary"
                    type="submit"
                >
                    {{ $noShowReschedule
                        ? 'Guardar y reagendar'
                        : 'Registrar inasistencia'
                    }}
                </button>

            </div>
        </form>
    </section>
@endif
@if ($showAttendanceModal)
    <div
        class="rm-modal-backdrop"
        wire:click="$set('showAttendanceModal', false)"
    ></div>

    <section
        class="rm-modal-panel rm-modal-panel-sm"
        role="dialog"
        aria-modal="true"
    >
        <div class="rm-modal-title">
            <div>
                <span>Asistencia</span>
                <h2>¿Quién atendió al cliente?</h2>
            </div>

            <button
                type="button"
                wire:click="$set('showAttendanceModal', false)"
            >
                x
            </button>
        </div>

        <form
            wire:submit="confirmAttendance"
            class="rm-form-stack"
        >
            <label class="rm-field">
                <span>Atendido por</span>

                <select wire:model="attendanceUserId">
                    <option value="">Seleccionar profesional</option>

                    @foreach ($staffUsers as $staff)
                        <option value="{{ $staff->id }}">
                            {{ $staff->name }}
                        </option>
                    @endforeach
                </select>

                @error('attendanceUserId')
                    <small>{{ $message }}</small>
                @enderror
            </label>

            <div class="rm-form-actions">
                <button
                    class="rm-button rm-button-outline"
                    type="button"
                    wire:click="$set('showAttendanceModal', false)"
                >
                    Cancelar
                </button>

                <button
                    class="rm-button rm-button-primary"
                    type="submit"
                >
                    Confirmar asistencia
                </button>
            </div>
        </form>
    </section>
@endif

@if ($showDoctorModal)
    <div
        class="rm-modal-backdrop"
        wire:click="$set('showDoctorModal', false)"
    ></div>

    <section
        class="rm-modal-panel rm-modal-panel-sm"
        role="dialog"
        aria-modal="true"
    >
        <div class="rm-modal-title">
            <div>
                <span>Agenda clinica</span>
                <h2>Asignar doctor</h2>
            </div>

            <button
                type="button"
                wire:click="$set('showDoctorModal', false)"
            >
                x
            </button>
        </div>

        <form
            wire:submit="confirmDoctorAssignment"
            class="rm-form-stack"
        >
            <p class="rm-modal-subtitle">
                El profesional elegido podra ver y completar la historia clinica de este cliente.
            </p>

            <label class="rm-field">
                <span>Doctor o profesional</span>

                <select wire:model="doctorUserId">
                    <option value="">Seleccionar profesional</option>

                    @foreach ($staffUsers as $staff)
                        <option value="{{ $staff->id }}">
                            {{ $staff->name }}
                        </option>
                    @endforeach
                </select>

                @error('doctorUserId')
                    <small>{{ $message }}</small>
                @enderror
            </label>

            <div class="rm-form-actions">
                <button
                    class="rm-button rm-button-outline"
                    type="button"
                    wire:click="$set('showDoctorModal', false)"
                >
                    Cancelar
                </button>

                <button
                    class="rm-button rm-button-primary"
                    type="submit"
                >
                    Guardar doctor
                </button>
            </div>
        </form>
    </section>
@endif

@if ($showAppointmentModal)
    <div class="rm-modal-backdrop" wire:click="$set('showAppointmentModal', false)"></div>
    <section class="rm-modal-panel rm-modal-panel-xl" role="dialog" aria-modal="true">
        <div class="rm-modal-title">
            <div><span>Agenda clinica</span><h2>Nueva cita</h2></div>
            <button type="button" wire:click="$set('showAppointmentModal', false)">x</button>
        </div>

        <form wire:submit="saveAppointment" class="rm-form-stack">
            <div class="rm-tab-switcher">
                <button class="{{ $clientMode === 'existing' ? 'is-active' : '' }}" type="button" wire:click="$set('clientMode', 'existing')">Cliente existente</button>
                <button class="{{ $clientMode === 'new' ? 'is-active' : '' }}" type="button" wire:click="$set('clientMode', 'new')">Nuevo cliente</button>
            </div>

            @if ($clientMode === 'existing')
                <label class="rm-field"><span>Buscar cliente</span><input wire:model.live.debounce.300ms="clientSearch" type="search" placeholder="Nombre, CI o telefono"></label>
                <label class="rm-field"><span>Cliente</span><select wire:model="clientId"><option value="">Seleccionar cliente</option>@foreach ($clients as $client)<option value="{{ $client->id }}">{{ $client->full_name }} {{ $client->identity_number ? '- '.$client->identity_number : '' }} {{ $client->displayPhone() ? '- '.$client->displayPhone() : '' }}</option>@endforeach</select>@error('clientId')<small>{{ $message }}</small>@enderror</label>
            @else
                <div class="rm-form-row">
                    <label class="rm-field"><span>Nombre completo</span><input wire:model="clientName" type="text">@error('clientName')<small>{{ $message }}</small>@enderror</label>
                    <label class="rm-field"><span>CI</span><input wire:model="clientCi" type="text"></label>
                </div>
                <div class="rm-field rm-phone-editor">
                    <span>Teléfonos</span>
                    <label class="rm-field rm-field-compact">
                        <span>País</span>
                        <select wire:model="clientPhoneCountry">
                            @foreach ($phoneCountries as $countryCode => $countryRule)
                                <option value="{{ $countryCode }}">{{ $countryRule['name'] }} (+{{ $countryRule['code'] }})</option>
                            @endforeach
                        </select>
                        @error('clientPhoneCountry')<small>{{ $message }}</small>@enderror
                    </label>
                    <div class="rm-phone-list">
                        @foreach ($clientPhones as $index => $phoneRow)
                            <div class="rm-phone-row is-simple" wire:key="agenda-client-phone-{{ $index }}">
                                <label>
                                    <span>Número</span>
                                    <input wire:model="clientPhones.{{ $index }}.phone" type="text" placeholder="70000000">
                                </label>
                                <label>
                                    <span>Etiqueta</span>
                                    <input wire:model="clientPhones.{{ $index }}.label" type="text" placeholder="{{ $index === 0 ? 'Principal' : 'Casa, trabajo, familiar' }}">
                                </label>
                                <button class="rm-phone-remove" type="button" wire:click="removeClientPhone({{ $index }})">Quitar</button>
                            </div>
                        @endforeach
                    </div>
                    <button class="rm-button rm-button-outline" type="button" wire:click="addClientPhone">Agregar teléfono</button>
                    @error('clientPhones.*.phone')<small>{{ $message }}</small>@enderror
                </div>
                <label class="rm-field"><span>Email</span><input wire:model="clientEmail" type="email">@error('clientEmail')<small>{{ $message }}</small>@enderror</label>
                <label class="rm-field"><span>Notas clinicas iniciales</span><input wire:model="clientNotes" type="text"></label>
            @endif

            <div class="rm-form-row">
                <label class="rm-field"><span>Fecha</span><input wire:model="scheduledDate" type="date">@error('scheduledDate')<small>{{ $message }}</small>@enderror</label>
                <label class="rm-field"><span>Hora</span><input wire:model="scheduledTime" type="time">@error('scheduledTime')<small>{{ $message }}</small>@enderror</label>
            </div>

            <div class="rm-form-row">
                <label class="rm-field"><span>Duracion</span><input wire:model="durationMinutes" type="number" min="10"></label>
                <label class="rm-field"><span>Sesiones pactadas</span><input wire:model="plannedSessions" type="number" min="1"></label>
            </div>

            <label class="rm-field">
                <span>Doctor / profesional opcional</span>
                <select wire:model="appointmentAttendedByUserId">
                    <option value="">Sin asignar por ahora</option>
                    @foreach ($staffUsers as $staff)
                        <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                    @endforeach
                </select>
                @error('appointmentAttendedByUserId')<small>{{ $message }}</small>@enderror
            </label>

            <div class="rm-field">
                <span>Servicios / tratamientos</span>
                <label class="rm-search-field">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <input wire:model.live.debounce.300ms="serviceSearch" type="search" placeholder="Buscar tratamiento o servicio">
                </label>
                <div class="rm-check-grid">
                    @forelse ($services as $service)
                        <label class="rm-check-option">
                            <input wire:model="serviceIds" type="checkbox" value="{{ $service->id }}">
                            <span>{{ $service->name }}</span>
                            <small>{{ \App\Support\Money::symbol() }} {{ $service->price }} - {{ $service->duration_minutes ?? 0 }} min</small>
                        </label>
                    @empty
                        <div class="rm-empty-state"><strong>Sin servicios</strong><span>Prueba con otro nombre de tratamiento.</span></div>
                    @endforelse
                </div>
                @error('serviceIds')<small>{{ $message }}</small>@enderror
            </div>

            <div class="rm-field">
                <span>Paquetes disponibles</span>
                <label class="rm-search-field">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <input wire:model.live.debounce.300ms="packageSearch" type="search" placeholder="Buscar paquete o promocion">
                </label>
                <div class="rm-check-grid">
                    @forelse ($packages as $package)
                        <button class="rm-check-option rm-package-pick" type="button" wire:click="addPackageServices('appointment', {{ $package->id }})">
                            <span>{{ $package->name }}</span>
                            <small>{{ \App\Support\Money::symbol() }} {{ $package->price }} - {{ $package->services->count() }} servicio(s)</small>
                        </button>
                    @empty
                        <div class="rm-empty-state"><strong>Sin paquetes</strong><span>Prueba con otro nombre o crea paquetes en Servicios.</span></div>
                    @endforelse
                </div>
            </div>

            <label class="rm-field"><span>Nombre del tratamiento/plan</span><input wire:model="treatmentName" type="text" placeholder="Automatico segun servicios si queda vacio"></label>
            <label class="rm-field"><span>Notas de la cita</span><input wire:model="appointmentNotes" type="text"></label>

            <div class="rm-form-row">
                @if ($paymentMethod !== 'mixed')
                    <label class="rm-field"><span>Pago inicial opcional</span><input wire:model="paymentAmount" type="number" min="0" step="0.01"></label>
                @endif
                <label class="rm-field"><span>Metodo</span><select wire:model.live="paymentMethod"><option value="cash">Efectivo</option><option value="qr">QR</option><option value="mixed">Mixto</option></select></label>
            </div>
            @if ($paymentMethod === 'mixed')
                <div class="rm-form-row">
                    <label class="rm-field"><span>Efectivo</span><input wire:model="paymentCashAmount" type="number" min="0" step="0.01"></label>
                    <label class="rm-field"><span>QR</span><input wire:model="paymentQrAmount" type="number" min="0" step="0.01"></label>
                </div>
            @endif
            <label class="rm-check-option"><input wire:model.live="invoiceRequested" type="checkbox"><span>Marcar para facturar luego</span><small>Se vera en caja/facturacion pendiente</small></label>
            @if ($invoiceRequested)
                <div class="rm-form-row">
                    <label class="rm-field">
                        <span>NIT / documento</span>
                        <input wire:model="invoiceNit" type="text" placeholder="NIT para factura">
                        @error('invoiceNit')<small>{{ $message }}</small>@enderror
                    </label>
                    <label class="rm-field">
                        <span>Nombre o razon social</span>
                        <input wire:model="invoiceName" type="text" placeholder="Nombre para factura">
                        @error('invoiceName')<small>{{ $message }}</small>@enderror
                    </label>
                </div>
            @endif

            <div class="rm-form-actions"><button class="rm-button rm-button-primary" type="submit">Guardar cita</button></div>
        </form>
    </section>
@endif

@if ($showPaymentModal)
    <div class="rm-modal-backdrop" wire:click="$set('showPaymentModal', false)"></div>
    <section class="rm-modal-panel rm-modal-panel-xl rm-payment-modal" role="dialog" aria-modal="true">
        @php
            $enteredPaymentTotal = round(
                (float) ($paymentCashAmount ?: 0)
                + (float) ($paymentQrAmount ?: 0)
                + collect($extraPaymentSplits)->sum(fn ($split) => (float) ($split['amount'] ?? 0)),
                2
            );
            $paymentDifference = round($enteredPaymentTotal - (float) $paymentChargeSummary['pay_now'], 2);
            $hasProductLines = collect($paymentProductLines)->contains(fn ($line) => ! empty($line['batch_id']) || ! empty($line['quantity']) || ! empty($line['unit_price']) || ! empty($line['paid_amount']));
        @endphp
        <div class="rm-modal-title"><div><span>Caja clinica</span><h2>{{ $editingPaymentId ? 'Editar cobro' : 'Registrar cobro' }}</h2><p class="rm-modal-subtitle">Completa solo lo necesario: tratamiento, productos opcionales y forma de pago.</p></div><button type="button" wire:click="$set('showPaymentModal', false)">x</button></div>
        <form wire:submit="savePayment" class="rm-payment-form">
            <div class="rm-payment-workspace">
                <div class="rm-payment-main">
                    <div class="rm-payment-card rm-payment-step">
                        <div class="rm-payment-card-header">
                            <span class="rm-step-number">1</span>
                            <div>
                                <strong>Responsables</strong>
                                <small>Quien atendio y, si hay productos, quien vendio.</small>
                            </div>
                        </div>
                        <div class="rm-payment-topbar">
                            <label class="rm-field">
                                <span>Atendido por</span>
                                <select wire:model="paymentAttendedByUserId">
                                    <option value="">Seleccionar profesional</option>
                                    @foreach ($staffUsers as $staff)
                                        <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                                    @endforeach
                                </select>
                                @error('paymentAttendedByUserId')<small>{{ $message }}</small>@enderror
                            </label>
                            <label class="rm-field">
                                <span>Vendido por</span>
                                <select wire:model="paymentProductSoldByUserId">
                                    <option value="">Seleccionar vendedor</option>
                                    @foreach ($staffUsers as $staff)
                                        <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                                    @endforeach
                                </select>
                                @error('paymentProductSoldByUserId')<small>{{ $message }}</small>@enderror
                            </label>
                        </div>
                    </div>

                    @if ($paymentAppointment)
                        <div class="rm-payment-card rm-payment-step">
                            <div class="rm-payment-card-header">
                                <span class="rm-step-number">2</span>
                                <div>
                                    <strong>Tratamientos</strong>
                                    <small>Marca lo realizado. Puedes cambiar el precio y cobrar solo un abono.</small>
                                </div>
                            </div>
                            <div class="rm-payment-service-list">
                                @foreach ($paymentAppointment->services as $serviceLine)
                                    <div class="rm-payment-service-row">
                                        <label class="rm-payment-service-check">
                                            <input wire:model="paymentServiceLineIds" type="checkbox" value="{{ $serviceLine->id }}">
                                            <span>{{ $serviceLine->name }}</span>
                                            <small>Base {{ \App\Support\Money::symbol() }} {{ $serviceLine->price }}</small>
                                        </label>
                                        <label class="rm-field">
                                            <span>Precio</span>
                                            <input wire:model.live="paymentServiceLinePrices.{{ $serviceLine->id }}" type="number" min="0" step="0.01" placeholder="Precio cobrado">
                                        </label>
                                        <label class="rm-field">
                                            <span>Paga ahora</span>
                                            <input wire:model.live="paymentServiceLinePayments.{{ $serviceLine->id }}" type="number" min="0" step="0.01" placeholder="Abono">
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <details class="rm-payment-card rm-payment-optional" @if ($hasProductLines || $productSearch) open @endif>
                        <summary>
                            <span>Productos vendidos</span>
                            <small>Opcional. Abre esto solo si el cliente se lleva productos.</small>
                        </summary>
                        <label class="rm-search-field">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                            <input wire:model.live.debounce.300ms="productSearch" type="search" placeholder="Buscar producto por nombre o codigo">
                        </label>
                        <div class="rm-payment-product-list">
                            @foreach ($paymentProductLines as $index => $line)
                                @php
                                    $productLineKey = ! empty($line['client_charge_id'] ?? '')
                                        ? 'charge-'.$line['client_charge_id']
                                        : (! empty($line['batch_id'] ?? '') ? 'batch-'.$line['batch_id'].'-'.$index : 'new-'.$index);
                                    $selectedProductBatch = ! empty($line['batch_id'] ?? '')
                                        ? $productBatches->firstWhere('id', (int) $line['batch_id'])
                                        : null;
                                @endphp
                                <div class="rm-payment-product-row" wire:key="payment-product-line-{{ $productLineKey }}">
                                    @if ($selectedProductBatch?->product)
                                        <button class="rm-product-photo-button" type="button" wire:click="previewProductImage({{ $selectedProductBatch->product->id }})" title="Ver imagen">
                                            @if ($selectedProductBatch->product->image_path)
                                                <img src="{{ asset('storage/'.$selectedProductBatch->product->image_path) }}" alt="{{ $selectedProductBatch->product->name }}">
                                            @else
                                                <span>{{ strtoupper(substr($selectedProductBatch->product->name, 0, 1)) }}</span>
                                            @endif
                                        </button>
                                    @else
                                        <div class="rm-product-photo-button is-empty"><span>P</span></div>
                                    @endif
                                    @if (! empty($line['locked_name'] ?? ''))
                                        <div class="rm-field rm-payment-readonly-product">
                                            <span>Producto vendido</span>
                                            <strong>{{ $line['locked_name'] }}</strong>
                                            <small>Producto registrado en este cobro</small>
                                        </div>
                                    @else
                                        <label class="rm-field">
                                            <span>Producto/lote</span>
                                            <select wire:model="paymentProductLines.{{ $index }}.batch_id">
                                                <option value="">Seleccionar producto</option>
                                                @foreach ($productBatches as $batch)
                                                    @php
                                                        $suggestedProductPrice = (float) ($batch->unit_cost ?: $batch->product?->purchase_cost);
                                                    @endphp
                                                    <option value="{{ $batch->id }}">
                                                        {{ $batch->product->name }} - {{ $batch->lot_code }} - Stock {{ number_format((float) $batch->current_quantity, 2) }}
                                                        @if ($suggestedProductPrice > 0)
                                                            - {{ \App\Support\Money::symbol() }} {{ number_format($suggestedProductPrice, 2) }}
                                                        @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                        </label>
                                    @endif
                                    <label class="rm-field">
                                        <span>Cantidad</span>
                                        <input wire:model="paymentProductLines.{{ $index }}.quantity" type="number" min="0.01" step="0.01">
                                    </label>
                                    <label class="rm-field">
                                        <span>Precio</span>
                                        <input wire:model="paymentProductLines.{{ $index }}.unit_price" type="number" min="0" step="0.01" placeholder="Costo si queda vacio">
                                    </label>
                                    <label class="rm-field">
                                        <span>Paga ahora</span>
                                        <input wire:model.live="paymentProductLines.{{ $index }}.paid_amount" type="number" min="0" step="0.01" placeholder="Si queda saldo">
                                    </label>
                                    <label class="rm-field">
                                        <span>Motivo faltante</span>
                                        <input wire:model="paymentProductLines.{{ $index }}.stock_shortage_reason" type="text" placeholder="Si no alcanza stock">
                                    </label>
                                    <button class="rm-button rm-button-outline" type="button" wire:click="removePaymentProductLine({{ $index }})">Quitar</button>
                                </div>
                            @endforeach
                        </div>
                        @error('paymentProductLines')<small>{{ $message }}</small>@enderror
                        <button class="rm-button rm-button-outline" type="button" wire:click="addPaymentProductLine">Agregar producto</button>
                    </details>

            @if ($pendingCharges->isNotEmpty())
                <details class="rm-payment-card rm-payment-optional" open>
                    <summary>
                        <span>Saldos pendientes</span>
                        <small>Si el cliente quiere abonar una deuda anterior, colocalo aqui.</small>
                    </summary>
                    <div class="rm-payment-product-list">
                        @foreach ($pendingCharges as $charge)
                            <div class="rm-pending-charge-row">
                                <div>
                                    <strong>{{ $charge->name }}</strong>
                                    <span>{{ $charge->type === 'product' ? 'Producto' : 'Tratamiento' }} - Saldo {{ \App\Support\Money::symbol() }} {{ number_format((float) $charge->balance_amount, 2) }}</span>
                                </div>
                                <label class="rm-field">
                                    <span>Paga ahora</span>
                                    <input wire:model.live="pendingChargePayments.{{ $charge->id }}" type="number" min="0" max="{{ $charge->balance_amount }}" step="0.01">
                                    @error('pendingChargePayments.'.$charge->id)<small>{{ $message }}</small>@enderror
                                </label>
                            </div>
                        @endforeach
                    </div>
                </details>
            @endif
                </div>

                <aside class="rm-payment-summary-panel rm-payment-step">
                    <div class="rm-payment-card-header">
                        <span class="rm-step-number">3</span>
                        <div>
                            <strong>Pago</strong>
                            <small>Divide el monto entre efectivo y QR si corresponde.</small>
                        </div>
                    </div>
                    <div class="rm-payment-total-box">
                        <div class="rm-payment-total-line"><span>Tratamientos</span><strong>{{ \App\Support\Money::symbol() }} {{ number_format((float) $paymentChargeSummary['services'], 2) }}</strong></div>
                        <div class="rm-payment-total-line"><span>Productos</span><strong>{{ \App\Support\Money::symbol() }} {{ number_format((float) $paymentChargeSummary['products'], 2) }}</strong></div>
                        <div class="rm-payment-total-line"><span>Abonos pendientes</span><strong>{{ \App\Support\Money::symbol() }} {{ number_format((float) $paymentChargeSummary['pending'], 2) }}</strong></div>
                        <div class="rm-payment-total-line is-main"><span>Debe pagar ahora</span><strong>{{ \App\Support\Money::symbol() }} {{ number_format((float) $paymentChargeSummary['pay_now'], 2) }}</strong></div>
                        <div class="rm-payment-total-line {{ $paymentDifference === 0.0 ? 'is-ok' : ($paymentDifference > 0 ? 'is-warning' : 'is-danger') }}">
                            <span>{{ $paymentDifference === 0.0 ? 'Pago exacto' : ($paymentDifference > 0 ? 'Excedente' : 'Falta cobrar') }}</span>
                            <strong>{{ \App\Support\Money::symbol() }} {{ number_format(abs($paymentDifference), 2) }}</strong>
                        </div>
                    </div>

                    <div class="rm-form-row rm-payment-method-row">
                        <label class="rm-field"><span>Efectivo</span><input wire:model.live="paymentCashAmount" type="number" min="0" step="0.01">@error('paymentCashAmount')<small>{{ $message }}</small>@enderror</label>
                        <label class="rm-field"><span>QR</span><input wire:model.live="paymentQrAmount" type="number" min="0" step="0.01">@error('paymentQrAmount')<small>{{ $message }}</small>@enderror</label>
                    </div>
                    <details class="rm-payment-extra-splits" @if (count($extraPaymentSplits) > 0) open @endif>
                        <summary>Pagos adicionales</summary>
                        <div class="rm-payment-product-list">
                            @foreach ($extraPaymentSplits as $index => $split)
                                <div class="rm-payment-split-row">
                                    <label class="rm-field">
                                        <span>Metodo</span>
                                        <select wire:model="extraPaymentSplits.{{ $index }}.method">
                                            <option value="cash">Efectivo</option>
                                            <option value="qr">QR</option>
                                        </select>
                                    </label>
                                    <label class="rm-field">
                                        <span>Monto</span>
                                        <input wire:model.live="extraPaymentSplits.{{ $index }}.amount" type="number" min="0" step="0.01">
                                    </label>
                                    <label class="rm-field">
                                        <span>Referencia</span>
                                        <input wire:model="extraPaymentSplits.{{ $index }}.reference" type="text">
                                    </label>
                                    <button class="rm-button rm-button-outline" type="button" wire:click="removePaymentSplit({{ $index }})">Quitar</button>
                                </div>
                            @endforeach
                        </div>
                        <button class="rm-button rm-button-outline" type="button" wire:click="addPaymentSplit">Agregar otro pago</button>
                    </details>
                    <label class="rm-field"><span>Referencia</span><input wire:model="paymentReference" type="text" placeholder="Numero QR, recibo, anticipo"></label>
                    <label class="rm-field"><span>Notas</span><input wire:model="paymentNotes" type="text"></label>
                    <label class="rm-check-option"><input wire:model.live="invoiceRequested" type="checkbox"><span>Para facturar</span><small>Queda marcado para el modulo de facturacion</small></label>
                    @if ($invoiceRequested)
                        <div class="rm-form-grid one">
                            <label class="rm-field">
                                <span>NIT / documento</span>
                                <input wire:model="invoiceNit" type="text" placeholder="NIT para factura">
                                @error('invoiceNit')<small>{{ $message }}</small>@enderror
                            </label>
                            <label class="rm-field">
                                <span>Nombre o razon social</span>
                                <input wire:model="invoiceName" type="text" placeholder="Nombre para factura">
                                @error('invoiceName')<small>{{ $message }}</small>@enderror
                            </label>
                        </div>
                    @endif
                    @if ($editingPaymentId && $paymentTickets->isNotEmpty())
                        <div class="rm-payment-ticket-list">
                            <strong>Tickets de este cobro</strong>
                            @foreach ($paymentTickets as $ticket)
                                <button class="rm-payment-ticket-row" type="button" wire:click="previewPaymentTicket({{ $ticket->id }})">
                                    <span>{{ $ticket->ticket_number }}</span>
                                    <small>{{ $ticket->created_at->format('d/m/Y H:i') }} - {{ $ticket->printed_at ? 'Impreso' : 'Sin imprimir' }}</small>
                                </button>
                            @endforeach
                        </div>
                    @endif
                    <div class="rm-form-actions"><button class="rm-button rm-button-primary" type="submit">{{ $editingPaymentId ? 'Actualizar cobro' : 'Guardar cobro' }}</button></div>
                </aside>
            </div>
        </form>
    </section>
@endif

@if ($showPaymentTicketPreview)
    <div class="rm-modal-backdrop" wire:click="closePaymentTicketPreview"></div>
    <section class="rm-modal-panel rm-modal-panel-wide rm-print-preview-modal" role="dialog" aria-modal="true">
        <div class="rm-modal-title">
            <div>
                <span>Ticket del cliente</span>
                <h2>{{ $paymentTicketPreview['title'] ?? 'Ticket de cobro' }}</h2>
                <p class="rm-modal-subtitle">{{ $paymentTicketPreview['branch'] ?? '' }} - {{ $paymentTicketPreview['business_date'] ?? '' }}</p>
            </div>
            <button type="button" wire:click="closePaymentTicketPreview" aria-label="Cerrar">x</button>
        </div>

        <div class="rm-print-preview-paper">
            <div class="rm-print-header">
                <strong>Rumika - Ticket de cobro</strong>
                <span>{{ $paymentTicketPreview['ticket_number'] ?? 'Ticket sin numero' }}</span>
                <span>Cliente: {{ $paymentTicketPreview['client'] ?? 'Cliente' }}</span>
                <span>Atendido por: {{ $paymentTicketPreview['performed_by'] ?? 'Sin profesional' }}</span>
                <span>Cajero: {{ $paymentTicketPreview['received_by'] ?? 'Sin cajero' }}</span>
            </div>

            <div class="rm-print-section">
                <h3>Detalle</h3>
                <div class="rm-print-table">
                    <div class="rm-print-row rm-print-row-head"><span>Item</span><span>Total</span><span>Efectivo</span><span>QR</span></div>
                    @foreach (($paymentTicketPreview['rows'] ?? []) as $row)
                        <div class="rm-print-row">
                            <span>{{ \Illuminate\Support\Str::limit($row['name'], 32, '') }} @if($row['quantity'] > 1) x {{ number_format($row['quantity'], 2) }} @endif</span>
                            <span>{{ \App\Support\Money::symbol() }} {{ number_format($row['total'], 2) }}</span>
                            <span>{{ \App\Support\Money::symbol() }} {{ number_format($row['cash'], 2) }}</span>
                            <span>{{ \App\Support\Money::symbol() }} {{ number_format($row['qr'], 2) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            @if (! empty($paymentTicketPreview['outstanding_charges']))
                <div class="rm-print-section">
                    <h3>Saldos pendientes</h3>
                    <div class="rm-print-table">
                        <div class="rm-print-row rm-print-row-head"><span>Detalle</span><span>Total</span><span>Pagado</span><span>Saldo</span></div>
                        @foreach ($paymentTicketPreview['outstanding_charges'] as $charge)
                            <div class="rm-print-row">
                                <span>{{ \Illuminate\Support\Str::limit($charge['name'], 32, '') }}</span>
                                <span>{{ \App\Support\Money::symbol() }} {{ number_format($charge['total'], 2) }}</span>
                                <span>{{ \App\Support\Money::symbol() }} {{ number_format($charge['paid'], 2) }}</span>
                                <span>{{ \App\Support\Money::symbol() }} {{ number_format($charge['balance'], 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="rm-print-totals">
                <span>Efectivo {{ \App\Support\Money::symbol() }} {{ number_format($paymentTicketPreview['totals']['cash'] ?? 0, 2) }}</span>
                <span>QR {{ \App\Support\Money::symbol() }} {{ number_format($paymentTicketPreview['totals']['qr'] ?? 0, 2) }}</span>
                <strong>Total {{ \App\Support\Money::symbol() }} {{ number_format($paymentTicketPreview['totals']['total'] ?? 0, 2) }}</strong>
                @if (! empty($paymentTicketPreview['printer_enabled']))<span>Impresora {{ $paymentTicketPreview['printer_name'] ?: 'sin seleccionar' }}</span>@endif
            </div>
        </div>

        <div class="rm-form-actions">
            <button
                class="rm-button rm-button-primary rm-auto-print-ticket"
                type="button"
                wire:click="markPaymentTicketPrinted"
                data-use-qz="{{ ! empty($paymentTicketPreview['printer_enabled']) && ! empty($paymentTicketPreview['printer_name']) ? '1' : '0' }}"
                data-printer-name="{{ $paymentTicketPreview['printer_name'] ?? '' }}"
                onclick="event.preventDefault(); window.RumikaQz.printFromButton(this)"
            >Imprimir ticket</button>
            <button class="rm-button rm-button-outline" type="button" wire:click="closePaymentTicketPreview">Volver</button>
        </div>
    </section>
@endif

@if ($showProductImageModal)
    <div class="rm-modal-backdrop" wire:click="closeProductImagePreview"></div>
    <section class="rm-modal-panel rm-modal-panel-small rm-product-image-modal" role="dialog" aria-modal="true">
        <div class="rm-modal-title">
            <div><span>Producto</span><h2>{{ $previewProductName }}</h2></div>
            <button type="button" wire:click="closeProductImagePreview" aria-label="Cerrar">x</button>
        </div>
        <div class="rm-product-image-large">
            @if ($previewProductImagePath)
                <img src="{{ asset('storage/'.$previewProductImagePath) }}" alt="{{ $previewProductName }}">
            @else
                <span>{{ strtoupper(substr($previewProductName ?: 'P', 0, 1)) }}</span>
            @endif
        </div>
    </section>
@endif

@if ($showRescheduleModal)
    <div class="rm-modal-backdrop" wire:click="$set('showRescheduleModal', false)"></div>
    <section class="rm-modal-panel rm-modal-panel-wide" role="dialog" aria-modal="true">
        <div class="rm-modal-title"><div><span>Agenda</span><h2>Reagendar cliente</h2></div><button type="button" wire:click="$set('showRescheduleModal', false)">x</button></div>
        <form wire:submit="saveReschedule" class="rm-form-stack">
            <p>La cita original quedara en su fecha como reagendada y se creara una nueva cita vinculada.</p>
            <div class="rm-form-row">
                <label class="rm-field"><span>Nueva fecha</span><input wire:model="rescheduleDate" type="date">@error('rescheduleDate')<small>{{ $message }}</small>@enderror</label>
                <label class="rm-field"><span>Nueva hora</span><input wire:model="rescheduleTime" type="time"></label>
            </div>
            <div class="rm-field">
                <span>Servicios para la nueva cita</span>
                <label class="rm-search-field">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <input wire:model.live.debounce.300ms="rescheduleServiceSearch" type="search" placeholder="Buscar tratamiento o servicio">
                </label>
                <div class="rm-check-grid">
                    @forelse ($rescheduleServices as $service)
                        <label class="rm-check-option">
                            <input wire:model="rescheduleServiceIds" type="checkbox" value="{{ $service->id }}">
                            <span>{{ $service->name }}</span>
                            <small>{{ \App\Support\Money::symbol() }} {{ $service->price }} - {{ $service->duration_minutes ?? 0 }} min</small>
                        </label>
                    @empty
                        <div class="rm-empty-state"><strong>Sin servicios</strong><span>Prueba con otro nombre.</span></div>
                    @endforelse
                </div>
                @error('rescheduleServiceIds')<small>{{ $message }}</small>@enderror
            </div>
            <div class="rm-field">
                <span>Paquetes para la nueva cita</span>
                <label class="rm-search-field">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <input wire:model.live.debounce.300ms="reschedulePackageSearch" type="search" placeholder="Buscar paquete o promocion">
                </label>
                <div class="rm-check-grid">
                    @forelse ($reschedulePackages as $package)
                        <button class="rm-check-option rm-package-pick" type="button" wire:click="addPackageServices('reschedule', {{ $package->id }})">
                            <span>{{ $package->name }}</span>
                            <small>{{ \App\Support\Money::symbol() }} {{ $package->price }} - {{ $package->services->count() }} servicio(s)</small>
                        </button>
                    @empty
                        <div class="rm-empty-state"><strong>Sin paquetes</strong><span>Prueba con otro nombre.</span></div>
                    @endforelse
                </div>
            </div>
            <label class="rm-field"><span>Motivo</span><input wire:model="rescheduleReason" type="text"></label>
            <div class="rm-form-actions"><button class="rm-button rm-button-primary" type="submit">Crear nueva cita</button></div>
        </form>
    </section>
@endif

@if ($showAddServicesModal)
    <div class="rm-modal-backdrop" wire:click="$set('showAddServicesModal', false)"></div>
    <section class="rm-modal-panel rm-modal-panel-wide" role="dialog" aria-modal="true">
        <div class="rm-modal-title"><div><span>Historial clinico</span><h2>Agregar tratamientos</h2></div><button type="button" wire:click="$set('showAddServicesModal', false)">x</button></div>
        <form wire:submit="saveAddedServices" class="rm-form-stack">
            <p>Agrega tratamientos o servicios a la fecha seleccionada sin mover la cita.</p>
            <label class="rm-field">
                <span>Referido por</span>
                <select wire:model="addServicesReferredByUserId">
                    <option value="">Sin referido</option>
                    @foreach ($staffUsers as $staff)
                        <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                    @endforeach
                </select>
                <small>Usa este campo cuando recepcion registra el servicio, pero el servicio fue sugerido por una doctora o profesional.</small>
                @error('addServicesReferredByUserId')<small>{{ $message }}</small>@enderror
            </label>
            <div class="rm-field">
                <span>Servicios / tratamientos</span>
                <label class="rm-search-field">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <input wire:model.live.debounce.300ms="addServicesSearch" type="search" placeholder="Buscar tratamiento o servicio">
                </label>
                <div class="rm-check-grid">
                    @forelse ($addServices as $service)
                        <label class="rm-check-option">
                            <input wire:model="addServiceIds" type="checkbox" value="{{ $service->id }}">
                            <span>{{ $service->name }}</span>
                            <small>{{ \App\Support\Money::symbol() }} {{ $service->price }} - {{ $service->duration_minutes ?? 0 }} min</small>
                        </label>
                    @empty
                        <div class="rm-empty-state"><strong>Sin servicios</strong><span>Prueba con otro nombre.</span></div>
                    @endforelse
                </div>
                @error('addServiceIds')<small>{{ $message }}</small>@enderror
            </div>
            <div class="rm-field">
                <span>Paquetes</span>
                <label class="rm-search-field">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <input wire:model.live.debounce.300ms="addPackagesSearch" type="search" placeholder="Buscar paquete o promocion">
                </label>
                <div class="rm-check-grid">
                    @forelse ($addPackages as $package)
                        <button class="rm-check-option rm-package-pick" type="button" wire:click="addPackageServices('add', {{ $package->id }})">
                            <span>{{ $package->name }}</span>
                            <small>{{ \App\Support\Money::symbol() }} {{ $package->price }} - {{ $package->services->count() }} servicio(s)</small>
                        </button>
                    @empty
                        <div class="rm-empty-state"><strong>Sin paquetes</strong><span>Prueba con otro nombre.</span></div>
                    @endforelse
                </div>
            </div>
            <div class="rm-form-actions"><button class="rm-button rm-button-primary" type="submit">Agregar a la cita</button></div>
        </form>
    </section>
@endif

@if ($showHistoryModal && $historyClient)
    <div class="rm-modal-backdrop" wire:click="$set('showHistoryModal', false)"></div>
    <section class="rm-modal-panel rm-modal-panel-xl" role="dialog" aria-modal="true">
        <div class="rm-modal-title"><div><span>Historial clinico</span><h2>{{ $historyClient->full_name }}</h2></div><button type="button" wire:click="$set('showHistoryModal', false)">x</button></div>
        <div class="rm-form-stack">
            <div class="rm-commerce-meta">
                <span>CI {{ $historyClient->identity_number ?? 'N/A' }}</span>
                @forelse ($historyClient->phones as $phone)
                    <span>{{ $phone->label ? $phone->label.': ' : '' }}{{ $phone->phone }}</span>
                @empty
                    <span>{{ $historyClient->displayContact() ?? 'Sin telefono ni CI' }}</span>
                @endforelse
                <span>{{ $historyClient->email ?? 'Sin email' }}</span>
            </div>

            <div class="rm-tab-switcher rm-tab-switcher-four rm-history-tabs">
                <button class="{{ $historyTab === 'appointments' ? 'is-active' : '' }}" type="button" wire:click="$set('historyTab', 'appointments')">Citas <span>{{ $historyClient->appointments->count() }}</span></button>
                <button class="{{ $historyTab === 'products' ? 'is-active' : '' }}" type="button" wire:click="$set('historyTab', 'products')">Productos <span>{{ $historyProductItems->count() }}</span></button>
                <button class="{{ $historyTab === 'service_debts' ? 'is-active' : '' }}" type="button" wire:click="$set('historyTab', 'service_debts')">Tratamientos <span>{{ $historyPendingServiceCharges->count() }}</span></button>
                <button class="{{ $historyTab === 'product_debts' ? 'is-active' : '' }}" type="button" wire:click="$set('historyTab', 'product_debts')">A cuenta <span>{{ $historyPendingProductCharges->count() }}</span></button>
            </div>

            @if ($historyTab === 'appointments')
                <div class="rm-history-section">
                <div class="rm-panel-title">
                    <div>
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M9 12h6M9 16h6M9 8h2"/><path d="M5 3h10l4 4v14H5z"/><path d="M15 3v5h5"/></svg>
                        <h3>Tratamientos y citas</h3>
                    </div>
                </div>
                <div class="rm-commerce-list">
                    @forelse ($historyClient->appointments->sortByDesc('scheduled_at') as $item)
                        <article class="rm-commerce-row">
                            <div class="rm-commerce-icon">{{ $item->scheduled_at->format('d/m') }}</div>
                            <div class="rm-row-main">
                                <strong>{{ $item->services->pluck('name')->join(' + ') }}</strong>
                                <span>{{ $item->scheduled_at->format('d/m/Y H:i') }} - {{ $this->appointmentStatusLabel($item->status) }}</span>
                                <div class="rm-service-scroll">
                                    @foreach ($item->services as $service)
                                        <span class="rm-service-pill {{ $service->status === 'completed' ? 'is-completed' : '' }}">
                                            {{ $service->name }}
                                            <small>{{ $service->status === 'completed' ? 'Finalizado' : 'Pendiente' }}</small>
                                        </span>
                                    @endforeach
                                </div>
                                <div class="rm-commerce-meta">
                                        <span>{{ $item->attended ? 'Asistió' : 'Sin asistencia' }}</span>
                                    <span>Pagos {{ \App\Support\Money::symbol() }} {{ number_format((float) $item->payments->sum('amount'), 2) }}</span>
                                    @if ($item->reschedule_reason)
                                        <span>Motivo: {{ $item->reschedule_reason }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="rm-commerce-actions">
                                <button class="rm-button rm-button-outline" type="button" wire:click="openAddServices({{ $item->id }})">Agregar servicios</button>
                            </div>
                        </article>
                    @empty
                        <div class="rm-empty-state"><strong>Sin historial</strong><span>Las citas y tratamientos apareceran aqui.</span></div>
                    @endforelse
                </div>
            </div>
            @endif

            @if ($historyTab === 'products')
                <div class="rm-history-section">
                <div class="rm-panel-title">
                    <div>
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="m7.5 4.3 9 5.2v10.4l-9-5.2Z"/><path d="m16.5 9.5 3-1.7-9-5.2-3 1.7M7.5 14.7l-3-1.7V6.1l3-1.8"/></svg>
                        <h3>Productos comprados</h3>
                    </div>
                </div>
                <div class="rm-commerce-list">
                    @forelse ($historyProductItems as $item)
                        <article class="rm-commerce-row">
                            <div class="rm-commerce-icon">{{ $item->payment?->paid_at?->format('d/m') ?? $item->created_at->format('d/m') }}</div>
                            <div class="rm-row-main">
                                <strong>{{ $item->name }}</strong>
                                <span>{{ $item->quantity }} x {{ \App\Support\Money::symbol() }} {{ number_format((float) $item->unit_price, 2) }} - Pagado {{ \App\Support\Money::symbol() }} {{ number_format((float) $item->total, 2) }}</span>
                                <div class="rm-commerce-meta">
                                    <span>Total {{ \App\Support\Money::symbol() }} {{ number_format((float) ($item->charged_total ?: $item->total), 2) }}</span>
                                    @if ($item->batch)
                                        <span>Lote {{ $item->batch->lot_code }}</span>
                                    @endif
                                    @if (str_starts_with($item->name, 'Abono '))
                                        <span>Abono a cuenta</span>
                                    @endif
                                    @if ($item->soldBy)
                                        <span>Vendido por {{ $item->soldBy->name }}</span>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="rm-empty-state"><strong>Sin productos</strong><span>Las compras de productos apareceran aqui.</span></div>
                    @endforelse
                </div>
            </div>
            @endif

            @if ($historyTab === 'service_debts')
                <div class="rm-history-section">
                <div class="rm-panel-title">
                    <div>
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M4 7h16a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2Z"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2M8 13h8"/></svg>
                        <h3>Tratamientos pendientes</h3>
                    </div>
                </div>
                <div class="rm-history-debt-card">
                        @forelse ($historyPendingServiceCharges as $charge)
                            <div class="rm-pending-charge-row">
                                <div>
                                    <strong>{{ $charge->name }}</strong>
                                    <span>Total {{ \App\Support\Money::symbol() }} {{ number_format((float) $charge->total_amount, 2) }} - Pagado {{ \App\Support\Money::symbol() }} {{ number_format((float) $charge->paid_amount, 2) }}</span>
                                </div>
                                <span class="rm-debt-balance">Saldo {{ \App\Support\Money::symbol() }} {{ number_format((float) $charge->balance_amount, 2) }}</span>
                            </div>
                        @empty
                            <div class="rm-empty-state"><strong>Sin pendientes</strong><span>No tiene tratamientos pendientes de pago.</span></div>
                        @endforelse
                </div>
            </div>
            @endif

            @if ($historyTab === 'product_debts')
                <div class="rm-history-section">
                <div class="rm-panel-title">
                    <div>
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="m7.5 4.3 9 5.2v10.4l-9-5.2Z"/><path d="m16.5 9.5 3-1.7-9-5.2-3 1.7M7.5 14.7l-3-1.7V6.1l3-1.8"/></svg>
                        <h3>Productos a cuenta</h3>
                    </div>
                </div>
                <div class="rm-history-debt-card">
                        @forelse ($historyPendingProductCharges as $charge)
                            <div class="rm-pending-charge-row">
                                <div>
                                    <strong>{{ $charge->name }}</strong>
                                    <span>{{ $charge->quantity }} x {{ \App\Support\Money::symbol() }} {{ number_format((float) $charge->unit_price, 2) }} - Pagado {{ \App\Support\Money::symbol() }} {{ number_format((float) $charge->paid_amount, 2) }}</span>
                                    @if ($charge->soldBy)
                                        <span>Vendido por {{ $charge->soldBy->name }}</span>
                                    @endif
                                </div>
                                <span class="rm-debt-balance">Saldo {{ \App\Support\Money::symbol() }} {{ number_format((float) $charge->balance_amount, 2) }}</span>
                            </div>
                        @empty
                            <div class="rm-empty-state"><strong>Sin productos a cuenta</strong><span>No tiene productos pendientes de pago.</span></div>
                        @endforelse
                </div>
            </div>
            @endif
        </div>
    </section>
@endif
