<?php

namespace App\Livewire\Clinic;

use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\Branch;
use App\Models\CashboxTicket;
use App\Models\Client;
use App\Models\ClientCharge;
use App\Models\Company;
use App\Models\InventoryMovement;
use App\Models\InventoryProductBatch;
use App\Models\Service;
use App\Models\TreatmentPayment;
use App\Models\TreatmentPaymentItem;
use App\Models\TreatmentPlan;
use App\Support\PaymentTicketBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class AgendaManager extends Component
{
    public string $selectedDate = '';
    public bool $showAppointmentModal = false;
    public bool $showHistoryModal = false;
    public bool $showPaymentModal = false;
    public bool $showRescheduleModal = false;
    public bool $showAddServicesModal = false;
    public bool $showPaymentTicketPreview = false;
    public array $paymentTicketPreview = [];
    public ?int $selectedAppointmentId = null;
    public ?int $historyClientId = null;
    public ?int $paymentAppointmentId = null;
    public ?int $editingPaymentId = null;
    public ?int $rescheduleAppointmentId = null;
    public ?int $addServicesAppointmentId = null;
    public ?int $confirmingAppointmentDeleteId = null;
    public ?int $editingTimeAppointmentId = null;
    public string $appointmentSearch = '';

    public string $clientMode = 'existing';
    public string $clientSearch = '';
    public ?int $clientId = null;
    public string $clientName = '';
    public string $clientCi = '';
    public string $clientPhone = '';
    public string $clientEmail = '';
    public string $clientNotes = '';

    public string $scheduledDate = '';
    public string $scheduledTime = '09:00';
    public string $durationMinutes = '60';
    public string $serviceSearch = '';
    public array $serviceIds = [];
    public bool $createTreatmentPlan = true;
    public string $treatmentName = '';
    public string $plannedSessions = '1';
    public string $appointmentNotes = '';

    public string $paymentAmount = '';
    public string $paymentMethod = 'cash';
    public string $paymentCashAmount = '';
    public string $paymentQrAmount = '';
    public array $extraPaymentSplits = [];
    public bool $invoiceRequested = false;
    public string $paymentReference = '';
    public string $paymentNotes = '';
    public ?int $paymentAttendedByUserId = null;
    public array $paymentServiceLineIds = [];
    public array $paymentServiceLinePrices = [];
    public array $paymentServiceLinePayments = [];
    public array $paymentProductLines = [];
    public ?int $paymentProductSoldByUserId = null;
    public array $pendingChargePayments = [];
    public string $productSearch = '';

    public string $rescheduleDate = '';
    public string $rescheduleTime = '09:00';
    public string $rescheduleReason = '';
    public string $rescheduleServiceSearch = '';
    public array $rescheduleServiceIds = [];
    public string $addServicesSearch = '';
    public array $addServiceIds = [];
    public string $editingAppointmentTime = '';
    public string $historyTab = 'appointments';

    public bool $showAttendanceModal = false;
    public ?int $attendanceAppointmentId = null;
    public ?int $attendanceUserId = null;

    public bool $showNoShowModal = false;
    public ?int $noShowAppointmentId = null;
    public string $noShowReason = '';
    public bool $noShowReschedule = false;
    public string $noShowRescheduleDate = '';
    public string $noShowRescheduleTime = '09:00';

    public function mount(): void
    {
        $this->selectedDate = now()->format('Y-m-d');
        $this->scheduledDate = $this->selectedDate;

        if ($paymentId = request()->integer('editar_cobro')) {
            $payment = $this->company()->treatmentPayments()->whereKey($paymentId)->first();

            if ($payment) {
                $this->selectedDate = $payment->paid_at->format('Y-m-d');
                $this->scheduledDate = $this->selectedDate;
                $this->editPayment($payment->id);
            }
        }
    }

    public function previousDay(): void
    {
        $this->selectedDate = Carbon::parse($this->selectedDate)->subDay()->format('Y-m-d');
        $this->scheduledDate = $this->selectedDate;
    }

    public function nextDay(): void
    {
        $this->selectedDate = Carbon::parse($this->selectedDate)->addDay()->format('Y-m-d');
        $this->scheduledDate = $this->selectedDate;
    }

    public function createAppointment(): void
    {
        $this->resetAppointmentForm();
        $this->showAppointmentModal = true;
    }

    public function saveAppointment(): void
    {
        $company = $this->company();
        $branch = $this->activeBranch();
        $clientIds = $company->clients()->pluck('id')->all();
        $serviceIds = $company->services()->pluck('id')->all();

        $rules = [
            'clientMode' => ['required', 'in:existing,new'],
            'scheduledDate' => ['required', 'date'],
            'scheduledTime' => ['required', 'date_format:H:i'],
            'durationMinutes' => ['required', 'integer', 'min:10', 'max:480'],
            'serviceIds' => ['required', 'array', 'min:1'],
            'serviceIds.*' => [Rule::in($serviceIds)],
            'createTreatmentPlan' => ['boolean'],
            'treatmentName' => ['nullable', 'string', 'max:160'],
            'plannedSessions' => ['required', 'integer', 'min:1', 'max:120'],
            'appointmentNotes' => ['nullable', 'string', 'max:800'],
            'paymentAmount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'paymentMethod' => ['required', 'in:cash,qr,mixed'],
            'paymentCashAmount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'paymentQrAmount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'invoiceRequested' => ['boolean'],
            'paymentReference' => ['nullable', 'string', 'max:120'],
            'paymentNotes' => ['nullable', 'string', 'max:500'],
        ];

        if ($this->clientMode === 'existing') {
            $rules['clientId'] = ['required', Rule::in($clientIds)];
        } else {
            $rules += [
                'clientName' => ['required', 'string', 'max:160'],
                'clientCi' => ['nullable', 'string', 'max:40'],
                'clientPhone' => ['nullable', 'string', 'max:40'],
                'clientEmail' => ['nullable', 'email', 'max:140'],
                'clientNotes' => ['nullable', 'string', 'max:800'],
            ];
        }

        $validated = $this->validate($rules);

        $paymentId = DB::transaction(function () use ($company, $branch, $validated) {
            $client = $this->clientMode === 'existing'
                ? $company->clients()->whereKey($validated['clientId'])->firstOrFail()
                : $company->clients()->create([
                    'branch_id' => $branch->id,
                    'full_name' => $validated['clientName'],
                    'identity_number' => $validated['clientCi'] ?: null,
                    'phone' => $validated['clientPhone'] ?: null,
                    'email' => $validated['clientEmail'] ?: null,
                    'clinical_notes' => $validated['clientNotes'] ?: null,
                ]);

            $services = $company->services()->whereIn('id', $validated['serviceIds'])->get();
            $totalAmount = $services->sum(fn(Service $service) => (float) $service->price);
            $plan = null;

            if ($validated['createTreatmentPlan']) {
                $plan = TreatmentPlan::query()->create([
                    'company_id' => $company->id,
                    'branch_id' => $branch->id,
                    'client_id' => $client->id,
                    'name' => $validated['treatmentName'] ?: $services->pluck('name')->join(' + '),
                    'planned_sessions' => $validated['plannedSessions'],
                    'total_amount' => $totalAmount,
                    'notes' => $validated['appointmentNotes'] ?: null,
                ]);
            }

            $appointment = Appointment::query()->create([
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'client_id' => $client->id,
                'treatment_plan_id' => $plan?->id,
                'scheduled_at' => Carbon::parse($validated['scheduledDate'] . ' ' . $validated['scheduledTime']),
                'duration_minutes' => $validated['durationMinutes'],
                'status' => 'scheduled',
                'clinical_notes' => $validated['appointmentNotes'] ?: null,
            ]);

            foreach ($services as $service) {
                $appointment->services()->create([
                    'service_id' => $service->id,
                    'name' => $service->name,
                    'price' => $service->price,
                    'duration_minutes' => $service->duration_minutes,
                ]);
            }

            $initialPaymentAmount = $this->initialAppointmentPaymentAmount($validated);

            if ($initialPaymentAmount > 0) {
                return $this->storePayment($company, $branch, $client, $appointment, $plan, $initialPaymentAmount, $validated);
            }

            return null;
        });

        $this->showAppointmentModal = false;
        $this->resetAppointmentForm();

        if ($paymentId) {
            $this->openPaymentTicket((int) $paymentId, true);
        }
    }

    public function markAttended(int $appointmentId): void
    {
        $appointment = $this->appointmentQuery()
            ->whereKey($appointmentId)
            ->firstOrFail();

        $this->attendanceAppointmentId = $appointment->id;
        $this->attendanceUserId = $appointment->attended_by_user_id;

        $this->showAttendanceModal = true;
    }

    public function confirmAttendance(): void
    {
        $company = $this->company();

        $userIds = $company->users()
            ->pluck('users.id')
            ->all();

        $this->validate([
            'attendanceAppointmentId' => ['required', 'integer'],
            'attendanceUserId' => ['required', Rule::in($userIds)],
        ]);

        $appointment = $this->appointmentQuery()
            ->whereKey($this->attendanceAppointmentId)
            ->firstOrFail();

        $wasAlreadyAttended = (bool) $appointment->attended;

        $appointment->update([
            'attended' => true,
            'status' => 'attended',
            'attended_by_user_id' => $this->attendanceUserId,
        ]);

        /*
     * Solo incrementamos la sesión la primera vez.
     * Así evitamos sumar dos veces si cambias quién atendió.
     */
        if (! $wasAlreadyAttended) {
            $appointment->treatmentPlan?->increment('completed_sessions');
        }
        $this->showAttendanceModal = false;
        $this->attendanceAppointmentId = null;
        $this->attendanceUserId = null;

        $this->resetErrorBag('attendanceUserId');
    }

    public function markNoShow(int $appointmentId): void
    {
        $appointment = $this->appointmentQuery()
            ->whereKey($appointmentId)
            ->firstOrFail();

        if ($appointment->locked_by_payment) {
            return;
        }

        $this->noShowAppointmentId = $appointment->id;
        $this->noShowReason = '';
        $this->noShowReschedule = false;

        $this->noShowRescheduleDate = $appointment->scheduled_at
            ->copy()
            ->addDay()
            ->format('Y-m-d');

        $this->noShowRescheduleTime = $appointment->scheduled_at->format('H:i');

        $this->resetErrorBag();

        $this->showNoShowModal = true;
    }

    public function confirmNoShow(): void
    {
        $validated = $this->validate([
            'noShowAppointmentId' => ['required', 'integer'],
            'noShowReason' => ['required', 'string', 'min:3', 'max:500'],
            'noShowReschedule' => ['boolean'],
            'noShowRescheduleDate' => [
                Rule::requiredIf($this->noShowReschedule),
                'nullable',
                'date',
            ],
            'noShowRescheduleTime' => [
                Rule::requiredIf($this->noShowReschedule),
                'nullable',
                'date_format:H:i',
            ],
        ]);

        $appointment = $this->appointmentQuery()
            ->whereKey($this->noShowAppointmentId)
            ->with('services')
            ->firstOrFail();

        if ($appointment->locked_by_payment) {
            $this->showNoShowModal = false;

            return;
        }

        DB::transaction(function () use ($appointment, $validated) {

            /*
         * Guardamos la observación en la ficha de la cita.
         */
            $existingNotes = trim((string) $appointment->clinical_notes);

            $noShowNote = 'No asistió: ' . trim($validated['noShowReason']);

            $clinicalNotes = $existingNotes !== ''
                ? $existingNotes . "\n" . $noShowNote
                : $noShowNote;

            /*
         * La cita original queda registrada como NO ASISTIÓ.
         */
            $appointment->update([
                'attended' => false,
                'status' => 'no_show',
                'attended_by_user_id' => null,
                'reschedule_reason' => trim($validated['noShowReason']),
                'clinical_notes' => $clinicalNotes,
            ]);

            /*
         * Si además quiere reagendar,
         * creamos una nueva cita vinculada a la anterior.
         */
            if ($validated['noShowReschedule']) {

                $newAppointment = Appointment::query()->create([
                    'company_id' => $appointment->company_id,
                    'branch_id' => $appointment->branch_id,
                    'client_id' => $appointment->client_id,
                    'treatment_plan_id' => $appointment->treatment_plan_id,
                    'rescheduled_from_id' => $appointment->id,

                    'scheduled_at' => Carbon::parse(
                        $validated['noShowRescheduleDate']
                            . ' '
                            . $validated['noShowRescheduleTime']
                    ),

                    'duration_minutes' => $appointment->duration_minutes,
                    'status' => 'scheduled',

                    'clinical_notes' => 'Reagendada después de inasistencia. Motivo: '
                        . trim($validated['noShowReason']),

                    'reschedule_reason' => trim($validated['noShowReason']),
                ]);

                /*
             * Copiamos los mismos tratamientos/servicios.
             */
                foreach ($appointment->services as $serviceLine) {
                    $newAppointment->services()->create([
                        'service_id' => $serviceLine->service_id,
                        'name' => $serviceLine->name,
                        'price' => $serviceLine->price,
                        'duration_minutes' => $serviceLine->duration_minutes,
                        'status' => 'pending',
                    ]);
                }
            }
        });

        $this->showNoShowModal = false;

        $this->reset([
            'noShowAppointmentId',
            'noShowReason',
            'noShowReschedule',
            'noShowRescheduleDate',
            'noShowRescheduleTime',
        ]);

        $this->noShowRescheduleTime = '09:00';

        $this->resetErrorBag();
    }

    public function confirmDeleteAppointment(int $appointmentId): void
    {
        $this->authorizeAppointmentDeletion();
        $this->appointmentQuery()->whereKey($appointmentId)->firstOrFail();
        $this->confirmingAppointmentDeleteId = $appointmentId;
    }

    public function cancelDeleteAppointment(): void
    {
        $this->confirmingAppointmentDeleteId = null;
    }

    public function deleteAppointment(int $appointmentId): void
    {
        $this->authorizeAppointmentDeletion();

        $appointment = $this->appointmentQuery()
            ->whereKey($appointmentId)
            ->with(['payments.items', 'payments.chargePayments.charge', 'treatmentPlan'])
            ->firstOrFail();

        $paymentIds = $appointment->payments()->pluck('id')->all();
        $hasExternalChargePayments = ClientCharge::query()
            ->where('appointment_id', $appointment->id)
            ->whereHas('payments', fn($query) => $paymentIds
                ? $query->whereNotIn('treatment_payment_id', $paymentIds)
                : $query)
            ->exists();

        if ($hasExternalChargePayments) {
            $this->addError('appointmentDelete', 'Esta cita tiene abonos posteriores. Primero elimina o edita esos cobros para no afectar otra caja.');

            return;
        }

        DB::transaction(function () use ($appointment) {
            foreach ($appointment->payments as $payment) {
                $this->deletePaymentWithReversal($payment, $appointment->company_id);
            }

            ClientCharge::query()
                ->where('appointment_id', $appointment->id)
                ->whereDoesntHave('payments')
                ->delete();

            if ($appointment->attended && $appointment->treatmentPlan) {
                $appointment->treatmentPlan->update([
                    'completed_sessions' => max(0, (int) $appointment->treatmentPlan->completed_sessions - 1),
                ]);
            }

            Appointment::query()
                ->where('rescheduled_from_id', $appointment->id)
                ->update(['rescheduled_from_id' => null]);

            $appointment->delete();
        });

        $this->confirmingAppointmentDeleteId = null;
    }

    public function editAppointmentTime(int $appointmentId): void
    {
        $appointment = $this->appointmentQuery()
            ->whereKey($appointmentId)
            ->firstOrFail();

        $this->editingTimeAppointmentId = $appointment->id;
        $this->editingAppointmentTime = $appointment->scheduled_at->format('H:i');
        $this->resetErrorBag('editingAppointmentTime');
    }

    public function cancelAppointmentTimeEdit(): void
    {
        $this->editingTimeAppointmentId = null;
        $this->editingAppointmentTime = '';
        $this->resetErrorBag('editingAppointmentTime');
    }

    public function saveAppointmentTime(): void
    {
        $validated = $this->validate([
            'editingTimeAppointmentId' => ['required', 'integer'],
            'editingAppointmentTime' => ['required', 'date_format:H:i'],
        ]);

        $appointment = $this->appointmentQuery()
            ->whereKey($validated['editingTimeAppointmentId'])
            ->firstOrFail();

        $appointment->update([
            'scheduled_at' => $appointment->scheduled_at
                ->copy()
                ->setTimeFromTimeString($validated['editingAppointmentTime']),
        ]);

        $this->cancelAppointmentTimeEdit();
    }

    public function openPayment(int $appointmentId): void
    {
        $appointment = $this->appointmentQuery()->whereKey($appointmentId)->with(['services', 'payments'])->firstOrFail();
        $this->paymentAppointmentId = $appointment->id;
        $this->editingPaymentId = null;
        $this->paymentServiceLineIds = $appointment->services->pluck('id')->map(fn($id) => (string) $id)->all();
        $this->paymentServiceLinePrices = $appointment->services
            ->mapWithKeys(fn($service) => [(string) $service->id => (string) $service->price])
            ->all();
        $this->paymentServiceLinePayments = $appointment->services
            ->reject(fn($service) => $this->existingChargeForAppointmentService($service->id)?->balance_amount > 0)
            ->mapWithKeys(fn($service) => [(string) $service->id => (string) $service->price])
            ->all();
        $serviceDue = array_sum(array_map('floatval', $this->paymentServiceLinePayments));
        $this->paymentAmount = (string) $serviceDue;
        $this->paymentCashAmount = (string) $serviceDue;
        $this->paymentQrAmount = '';
        $this->extraPaymentSplits = [];
        $this->paymentMethod = 'cash';
        $this->invoiceRequested = false;
        $this->paymentReference = '';
        $this->paymentNotes = '';
        $this->paymentAttendedByUserId = $appointment->attended_by_user_id;
        $this->paymentProductSoldByUserId = null;
        $this->paymentProductLines = [];
        $this->pendingChargePayments = $this->pendingChargeInputs($appointment->client_id);
        $this->showPaymentModal = true;
    }

    public function editPayment(int $paymentId): void
    {
        $payment = $this->company()->treatmentPayments()
            ->with(['appointment.services', 'splits', 'items'])
            ->whereKey($paymentId)
            ->firstOrFail();

        $this->paymentAppointmentId = $payment->appointment_id;
        $this->editingPaymentId = $payment->id;
        $this->paymentServiceLineIds = $payment->items
            ->where('type', 'service')
            ->where('appointment_service_id', '!=', null)
            ->pluck('appointment_service_id')
            ->filter()
            ->map(fn($id) => (string) $id)
            ->values()
            ->all();
        $this->paymentServiceLinePrices = $payment->items
            ->where('type', 'service')
            ->where('appointment_service_id', '!=', null)
            ->mapWithKeys(fn($item) => [(string) $item->appointment_service_id => (string) ($item->charged_total ?: $item->unit_price)])
            ->all();
        $this->paymentServiceLinePayments = $payment->items
            ->where('type', 'service')
            ->where('appointment_service_id', '!=', null)
            ->mapWithKeys(fn($item) => [(string) $item->appointment_service_id => (string) $item->total])
            ->all();
        $this->paymentCashAmount = (string) ($payment->splits->firstWhere('method', 'cash')?->amount ?? '');
        $this->paymentQrAmount = (string) ($payment->splits->firstWhere('method', 'qr')?->amount ?? '');
        $usedSplitIds = collect([
            $payment->splits->firstWhere('method', 'cash')?->id,
            $payment->splits->firstWhere('method', 'qr')?->id,
        ])->filter()->all();
        $this->extraPaymentSplits = $payment->splits
            ->whereNotIn('id', $usedSplitIds)
            ->values()
            ->map(fn($split) => [
                'method' => $split->method,
                'amount' => (string) $split->amount,
                'reference' => $split->reference ?? '',
            ])
            ->all();
        $this->paymentAmount = (string) $payment->amount;
        $this->paymentMethod = $payment->method;
        $this->invoiceRequested = $payment->invoice_requested;
        $this->paymentReference = $payment->reference ?? '';
        $this->paymentNotes = $payment->notes ?? '';
        $this->paymentAttendedByUserId = $payment->performed_by_user_id;
        $this->paymentProductSoldByUserId = $payment->items
            ->where('type', 'product')
            ->first()?->sold_by_user_id;
        $this->productSearch = '';
        $this->paymentProductLines = $payment->items
            ->where('type', 'product')
            ->values()
            ->map(fn($item) => [
                'client_charge_id' => (string) ($item->client_charge_id ?? ''),
                'batch_id' => (string) ($item->inventory_product_batch_id ?? ''),
                'locked_name' => $item->inventory_product_batch_id ? '' : $item->name,
                'quantity' => (string) $item->quantity,
                'unit_price' => (string) $item->unit_price,
                'paid_amount' => (string) $item->total,
                'stock_shortage_reason' => '',
            ])
            ->all();
        $existingProductChargeIds = collect($this->paymentProductLines)
            ->pluck('client_charge_id')
            ->filter()
            ->map(fn($id) => (int) $id)
            ->all();
        $appointmentProductDebtLines = $this->company()->clientCharges()
            ->where('appointment_id', $payment->appointment_id)
            ->where('client_id', $payment->client_id)
            ->where('type', 'product')
            ->whereIn('status', ['pending', 'partial'])
            ->when($existingProductChargeIds, fn($query) => $query->whereNotIn('id', $existingProductChargeIds))
            ->get()
            ->map(fn(ClientCharge $charge) => [
                'client_charge_id' => (string) $charge->id,
                'batch_id' => (string) ($charge->inventory_product_batch_id ?? ''),
                'locked_name' => $charge->inventory_product_batch_id ? '' : $charge->name,
                'quantity' => (string) $charge->quantity,
                'unit_price' => (string) $charge->unit_price,
                'paid_amount' => (string) $charge->paid_amount,
                'stock_shortage_reason' => '',
            ])
            ->all();
        $this->paymentProductLines = array_values([...$this->paymentProductLines, ...$appointmentProductDebtLines]);
        $this->pendingChargePayments = $this->pendingChargeInputs($payment->client_id, $payment->id);
        $this->showPaymentModal = true;
    }

    public function addPaymentProductLine(): void
    {
        $this->paymentProductLines[] = [
            'client_charge_id' => '',
            'batch_id' => '',
            'locked_name' => '',
            'quantity' => '1',
            'unit_price' => '',
            'paid_amount' => '',
            'stock_shortage_reason' => '',
        ];
    }

    public function updatedPaymentProductLines(mixed $value, ?string $key = null): void
    {
        if (! $key || ! str_ends_with($key, '.batch_id')) {
            return;
        }

        $index = (int) str($key)->before('.')->toString();
        $batchId = (int) $value;

        if ($batchId <= 0 || ! isset($this->paymentProductLines[$index])) {
            return;
        }

        $batch = $this->company()->inventoryBatches()
            ->with('product')
            ->where('branch_id', $this->activeBranch()->id)
            ->whereKey($batchId)
            ->first();

        if (! $batch) {
            return;
        }

        $suggestedPrice = round((float) ($batch->unit_cost ?: $batch->product?->purchase_cost), 2);

        if ($suggestedPrice <= 0) {
            return;
        }

        if (($this->paymentProductLines[$index]['unit_price'] ?? '') === '') {
            $this->paymentProductLines[$index]['unit_price'] = (string) $suggestedPrice;
        }

        if (($this->paymentProductLines[$index]['paid_amount'] ?? '') === '') {
            $quantity = max(1, (float) ($this->paymentProductLines[$index]['quantity'] ?? 1));
            $this->paymentProductLines[$index]['paid_amount'] = (string) round($quantity * $suggestedPrice, 2);
        }
    }

    public function addPaymentSplit(): void
    {
        $this->extraPaymentSplits[] = [
            'method' => 'qr',
            'amount' => '',
            'reference' => '',
        ];
    }

    public function removePaymentSplit(int $index): void
    {
        unset($this->extraPaymentSplits[$index]);
        $this->extraPaymentSplits = array_values($this->extraPaymentSplits);
    }

    public function removePaymentProductLine(int $index): void
    {
        unset($this->paymentProductLines[$index]);
        $this->paymentProductLines = array_values($this->paymentProductLines);
    }

    public function savePayment(): void
    {
        $this->normalizePaymentAmountInputs();

        $appointment = $this->appointmentQuery()->whereKey($this->paymentAppointmentId)->with(['services', 'company', 'branch', 'client', 'treatmentPlan'])->firstOrFail();
        $company = $appointment->company;
        $branch = $appointment->branch;
        $userIds = $company->users()->pluck('users.id')->all();
        $serviceLineIds = $appointment->services()->pluck('id')->all();
        $batchIds = $company->inventoryBatches()->where('branch_id', $branch->id)->pluck('id')->all();

        $validated = $this->validate([
            'paymentCashAmount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'paymentQrAmount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'extraPaymentSplits' => ['array'],
            'extraPaymentSplits.*.method' => ['required', 'in:cash,qr'],
            'extraPaymentSplits.*.amount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'extraPaymentSplits.*.reference' => ['nullable', 'string', 'max:120'],
            'invoiceRequested' => ['boolean'],
            'paymentReference' => ['nullable', 'string', 'max:120'],
            'paymentNotes' => ['nullable', 'string', 'max:500'],
            'paymentAttendedByUserId' => ['nullable', Rule::in($userIds)],
            'paymentProductSoldByUserId' => ['nullable', Rule::in($userIds)],
            'paymentServiceLineIds' => ['array'],
            'paymentServiceLineIds.*' => [Rule::in($serviceLineIds)],
            'paymentServiceLinePrices' => ['array'],
            'paymentServiceLinePrices.*' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'paymentServiceLinePayments' => ['array'],
            'paymentServiceLinePayments.*' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'paymentProductLines' => ['array'],
            'paymentProductLines.*.client_charge_id' => ['nullable', 'integer'],
            'paymentProductLines.*.batch_id' => ['nullable', Rule::in($batchIds)],
            'paymentProductLines.*.quantity' => ['nullable', 'numeric', 'min:0.01', 'max:99999999'],
            'paymentProductLines.*.unit_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'paymentProductLines.*.paid_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'paymentProductLines.*.stock_shortage_reason' => ['nullable', 'string', 'max:500'],
            'pendingChargePayments' => ['array'],
            'pendingChargePayments.*' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
        ]);

        $cash = (float) ($validated['paymentCashAmount'] ?: 0);
        $qr = (float) ($validated['paymentQrAmount'] ?: 0);
        $extraSplits = collect($validated['extraPaymentSplits'] ?? [])
            ->filter(fn($split) => (float) ($split['amount'] ?? 0) > 0)
            ->values();
        $amount = $cash + $qr + $extraSplits->sum(fn($split) => (float) $split['amount']);

        if ($amount <= 0) {
            $this->addError('paymentCashAmount', 'Ingresa al menos un monto en efectivo o QR.');

            return;
        }

        $selectedServiceIds = collect($validated['paymentServiceLineIds'] ?? [])->map(fn($id) => (int) $id)->all();
        $serviceLines = $appointment->services()->whereIn('id', $selectedServiceIds)->get();
        $serviceLineCharges = $serviceLines->mapWithKeys(function ($serviceLine) use ($validated) {
            $chargedTotal = round((float) (($validated['paymentServiceLinePrices'][(string) $serviceLine->id] ?? '') !== ''
                ? $validated['paymentServiceLinePrices'][(string) $serviceLine->id]
                : $serviceLine->price), 2);

            return [(string) $serviceLine->id => $chargedTotal];
        });
        $serviceTotal = (float) $serviceLineCharges->sum();
        $servicePaidTotal = $serviceLines->sum(function ($serviceLine) use ($validated, $serviceLineCharges) {
            $chargedTotal = (float) $serviceLineCharges[(string) $serviceLine->id];
            $paid = (float) ($validated['paymentServiceLinePayments'][(string) $serviceLine->id] ?? $chargedTotal);

            return min($paid, $chargedTotal);
        });
        foreach ($serviceLines as $serviceLine) {
            $chargedTotal = (float) $serviceLineCharges[(string) $serviceLine->id];
            $paid = (float) ($validated['paymentServiceLinePayments'][(string) $serviceLine->id] ?? $chargedTotal);

            if ($paid > $chargedTotal) {
                $this->addError('paymentServiceLinePayments.' . $serviceLine->id, 'El pago de ' . $serviceLine->name . ' no puede superar Bs ' . number_format($chargedTotal, 2) . '.');

                return;
            }
        }
        $productSoldByUserId = ($validated['paymentProductSoldByUserId'] ?? null) ?: null;
        $productLines = $this->normalizedPaymentProductLines($validated['paymentProductLines'] ?? [], $company, $branch, $productSoldByUserId ? (int) $productSoldByUserId : null);
        $productTotal = $productLines->sum(fn(array $line) => $line['quantity'] * $line['unit_price']);
        $productPaidTotal = $productLines->sum(fn(array $line) => $line['paid_amount']);
        $pendingPayments = $this->normalizedPendingChargePayments($validated['pendingChargePayments'] ?? [], $company, $branch, $appointment->client_id);
        $pendingPaidTotal = $pendingPayments->sum('amount');
        $chargeTotal = round($serviceTotal + $productTotal, 2);
        $payNowTotal = round($servicePaidTotal + $productPaidTotal + $pendingPaidTotal, 2);
        $submittedProductChargeIds = collect($validated['paymentProductLines'] ?? [])
            ->pluck('client_charge_id')
            ->filter()
            ->map(fn($id) => (int) $id)
            ->all();

        if (($chargeTotal + $pendingPaidTotal) <= 0) {
            $this->addError('paymentServiceLineIds', 'Selecciona al menos un tratamiento, producto o saldo pendiente.');

            return;
        }

        if ($amount <= 0 && $payNowTotal > 0) {
            $this->addError('paymentCashAmount', 'Ingresa el monto que esta pagando el cliente.');

            return;
        }

        if (abs(round($amount, 2) - $payNowTotal) > 0.009) {
            $this->addError('paymentCashAmount', 'El cobro debe sumar Bs ' . number_format($payNowTotal, 2) . ' segun lo que paga ahora.');

            return;
        }

        foreach ($productLines->groupBy('batch_id') as $batchId => $lines) {
            $batch = $lines->first()['batch'];
            $needed = $lines->sum('quantity');
            $available = (float) $batch->current_quantity + $this->editingPaymentBatchQuantity((int) $batchId);

            $hasShortageReason = $lines->contains(fn(array $line) => trim((string) $line['stock_shortage_reason']) !== '');

            if ($available < $needed && ! $hasShortageReason) {
                $this->addError('paymentProductLines', 'Indica el motivo del stock pendiente para ' . $batch->product->name . '. Disponible: ' . number_format($available, 2) . '.');

                return;
            }
        }
        $stockShortageReasons = $productLines
            ->groupBy('batch_id')
            ->map(fn($lines) => (string) ($lines->first(fn(array $line) => trim((string) $line['stock_shortage_reason']) !== '')['stock_shortage_reason'] ?? ''))
            ->all();

        $paymentId = DB::transaction(function () use ($appointment, $validated, $cash, $qr, $extraSplits, $amount, $selectedServiceIds, $serviceLineCharges, $productLines, $pendingPayments, $stockShortageReasons, $submittedProductChargeIds) {
            $payment = $this->editingPaymentId
                ? $appointment->company->treatmentPayments()->with('items')->whereKey($this->editingPaymentId)->firstOrFail()
                : new TreatmentPayment([
                    'company_id' => $appointment->company_id,
                    'branch_id' => $appointment->branch_id,
                    'client_id' => $appointment->client_id,
                    'appointment_id' => $appointment->id,
                    'treatment_plan_id' => $appointment->treatment_plan_id,
                    'paid_at' => $appointment->scheduled_at,
                ]);

            if ($payment->exists) {
                $this->reversePaymentProductStock($payment);
                $this->reversePaymentClientCharges($payment);
                if ($payment->treatment_plan_id) {
                    $payment->treatmentPlan?->decrement('paid_amount', (float) $payment->amount);
                }
                $payment->splits()->delete();
                $payment->items()->delete();
                InventoryMovement::query()->where('company_id', $appointment->company_id)->where('reference', 'PAY-' . $payment->id)->delete();
                ClientCharge::query()
                    ->whereIn('id', $submittedProductChargeIds)
                    ->where('appointment_id', $appointment->id)
                    ->where('type', 'product')
                    ->whereDoesntHave('payments')
                    ->delete();
            }

            $payment->fill([
                'received_by_user_id' => Auth::id(),
                'performed_by_user_id' => $validated['paymentAttendedByUserId'],
                'amount' => $amount,
                'method' => $this->paymentMethodFromAmounts($cash, $qr, $extraSplits),
                'invoice_requested' => (bool) ($validated['invoiceRequested'] ?? false),
                'reference' => $validated['paymentReference'] ?: null,
                'notes' => $validated['paymentNotes'] ?: null,
            ]);
            $payment->save();

            if ($cash > 0) {
                $payment->splits()->create(['method' => 'cash', 'amount' => $cash, 'reference' => $validated['paymentReference'] ?: null]);
            }
            if ($qr > 0) {
                $payment->splits()->create(['method' => 'qr', 'amount' => $qr, 'reference' => $validated['paymentReference'] ?: null]);
            }
            foreach ($extraSplits as $split) {
                $payment->splits()->create([
                    'method' => $split['method'],
                    'amount' => $split['amount'],
                    'reference' => $split['reference'] ?: ($validated['paymentReference'] ?: null),
                ]);
            }

            $appointment->services()->update(['performed_by_user_id' => null]);
            foreach ($appointment->services()->whereIn('id', $selectedServiceIds)->get() as $serviceLine) {
                $serviceLine->update(['performed_by_user_id' => $validated['paymentAttendedByUserId']]);
                $chargedTotal = (float) $serviceLineCharges[(string) $serviceLine->id];
                $paidAmount = min((float) ($validated['paymentServiceLinePayments'][(string) $serviceLine->id] ?? $chargedTotal), $chargedTotal);
                $charge = $this->createClientCharge([
                    'company_id' => $appointment->company_id,
                    'branch_id' => $appointment->branch_id,
                    'client_id' => $appointment->client_id,
                    'appointment_id' => $appointment->id,
                    'appointment_service_id' => $serviceLine->id,
                    'type' => 'service',
                    'name' => $serviceLine->name,
                    'quantity' => 1,
                    'unit_price' => $chargedTotal,
                    'total_amount' => $chargedTotal,
                    'paid_amount' => 0,
                    'balance_amount' => $chargedTotal,
                    'charged_at' => $appointment->scheduled_at,
                ]);
                $this->applyChargePayment($charge, $payment, $paidAmount);

                if ($paidAmount <= 0) {
                    continue;
                }

                $payment->items()->create([
                    'client_charge_id' => $charge->id,
                    'appointment_service_id' => $serviceLine->id,
                    'type' => 'service',
                    'name' => $serviceLine->name,
                    'quantity' => 1,
                    'unit_price' => $chargedTotal,
                    'charged_total' => $chargedTotal,
                    'total' => $paidAmount,
                ]);
            }

            foreach ($productLines as $line) {
                $batch = $appointment->company->inventoryBatches()
                    ->with('product')
                    ->where('branch_id', $appointment->branch_id)
                    ->whereKey($line['batch_id'])
                    ->lockForUpdate()
                    ->firstOrFail();
                $quantity = $line['quantity'];

                $unitPrice = $line['unit_price'];
                $chargedTotal = $quantity * $unitPrice;
                $paidAmount = $line['paid_amount'];
                $availableBefore = (float) $batch->current_quantity;
                $missingQuantity = max(0, round($quantity - $availableBefore, 2));
                $batch->decrement('current_quantity', $quantity);
                $charge = $this->createClientCharge([
                    'company_id' => $appointment->company_id,
                    'branch_id' => $appointment->branch_id,
                    'client_id' => $appointment->client_id,
                    'appointment_id' => $appointment->id,
                    'inventory_product_id' => $batch->inventory_product_id,
                    'inventory_product_batch_id' => $batch->id,
                    'sold_by_user_id' => $line['sold_by_user_id'],
                    'type' => 'product',
                    'name' => $batch->product->name,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_amount' => $chargedTotal,
                    'paid_amount' => 0,
                    'balance_amount' => $chargedTotal,
                    'charged_at' => $appointment->scheduled_at,
                ]);
                $this->applyChargePayment($charge, $payment, $paidAmount);

                $payment->items()->create([
                    'client_charge_id' => $charge->id,
                    'inventory_product_id' => $batch->inventory_product_id,
                    'inventory_product_batch_id' => $batch->id,
                    'sold_by_user_id' => $line['sold_by_user_id'],
                    'type' => 'product',
                    'name' => $batch->product->name,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'charged_total' => $chargedTotal,
                    'total' => $paidAmount,
                ]);

                InventoryMovement::query()->create([
                    'company_id' => $appointment->company_id,
                    'branch_id' => $appointment->branch_id,
                    'inventory_count_id' => $this->currentInventoryCountForProduct($appointment->branch, $batch->inventory_product_id)->id,
                    'inventory_product_id' => $batch->inventory_product_id,
                    'inventory_product_batch_id' => $batch->id,
                    'type' => 'sale',
                    'quantity' => $quantity,
                    'unit_cost' => $batch->unit_cost,
                    'total_cost' => $quantity * (float) $batch->unit_cost,
                    'moved_at' => $appointment->scheduled_at,
                    'reference' => 'PAY-' . $payment->id,
                    'reason' => 'Venta de producto en caja clinica',
                ]);

                if ($missingQuantity > 0) {
                    InventoryMovement::query()->create([
                        'company_id' => $appointment->company_id,
                        'branch_id' => $appointment->branch_id,
                        'inventory_count_id' => $this->currentInventoryCountForProduct($appointment->branch, $batch->inventory_product_id)->id,
                        'inventory_product_id' => $batch->inventory_product_id,
                        'inventory_product_batch_id' => $batch->id,
                        'type' => 'stock_shortage',
                        'quantity' => $missingQuantity,
                        'unit_cost' => $batch->unit_cost,
                        'total_cost' => $missingQuantity * (float) $batch->unit_cost,
                        'moved_at' => now(),
                        'reference' => 'PAY-' . $payment->id,
                        'reason' => ($stockShortageReasons[$line['batch_id']] ?? '') ?: 'Venta con stock pendiente',
                    ]);
                }
            }

            foreach ($pendingPayments as $pendingPayment) {
                $charge = $appointment->company->clientCharges()
                    ->where('branch_id', $appointment->branch_id)
                    ->where('client_id', $appointment->client_id)
                    ->whereKey($pendingPayment['charge_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->applyChargePayment($charge, $payment, $pendingPayment['amount']);

                $payment->items()->create([
                    'client_charge_id' => $charge->id,
                    'appointment_service_id' => $charge->appointment_service_id,
                    'inventory_product_id' => $charge->inventory_product_id,
                    'inventory_product_batch_id' => $charge->inventory_product_batch_id,
                    'sold_by_user_id' => $charge->sold_by_user_id,
                    'type' => $charge->type,
                    'name' => 'Abono ' . $charge->name,
                    'quantity' => $charge->quantity,
                    'unit_price' => $charge->unit_price,
                    'charged_total' => $charge->total_amount,
                    'total' => $pendingPayment['amount'],
                ]);
            }

            $appointment->update([
                'locked_by_payment' => true,
                'attended_by_user_id' => $validated['paymentAttendedByUserId'],
            ]);
            $appointment->treatmentPlan?->increment('paid_amount', $amount);

            return $payment->id;
        });

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $this->showPaymentModal = false;
        $this->openPaymentTicket((int) $paymentId, true);
    }

    public function previewPaymentTicket(int $ticketId): void
    {
        $ticket = $this->company()
            ->cashboxTickets()
            ->where('branch_id', $this->activeBranch()->id)
            ->where('type', 'payment')
            ->whereKey($ticketId)
            ->firstOrFail();

        $ticket->increment('reprint_count');
        $this->paymentTicketPreview = $ticket->refresh()->payload;
        $this->showPaymentTicketPreview = true;
    }

    public function closePaymentTicketPreview(): void
    {
        $this->showPaymentTicketPreview = false;
        $this->paymentTicketPreview = [];
    }

    public function markPaymentTicketPrinted(): void
    {
        $ticketId = $this->paymentTicketPreview['ticket_id'] ?? null;

        if (! $ticketId) {
            return;
        }

        $this->company()
            ->cashboxTickets()
            ->where('branch_id', $this->activeBranch()->id)
            ->whereKey($ticketId)
            ->update([
                'printed_by_user_id' => Auth::id(),
                'printed_at' => now(),
                'status' => 'printed',
            ]);
    }

    public function openReschedule(int $appointmentId): void
    {
        $appointment = $this->appointmentQuery()->whereKey($appointmentId)->with('services')->firstOrFail();
        $this->rescheduleAppointmentId = $appointment->id;
        $this->rescheduleDate = $appointment->scheduled_at->addWeek()->format('Y-m-d');
        $this->rescheduleTime = $appointment->scheduled_at->format('H:i');
        $this->rescheduleReason = '';
        $this->rescheduleServiceSearch = '';
        $this->rescheduleServiceIds = $appointment->services
            ->pluck('service_id')
            ->filter()
            ->map(fn($id) => (string) $id)
            ->values()
            ->all();
        $this->showRescheduleModal = true;
    }

    public function saveReschedule(): void
    {
        $appointment = $this->appointmentQuery()->whereKey($this->rescheduleAppointmentId)->with('services')->firstOrFail();
        $company = $this->company();
        $branch = $this->activeBranch();
        $serviceIds = $this->availableServiceIds($company, $branch);

        $validated = $this->validate([
            'rescheduleDate' => ['required', 'date'],
            'rescheduleTime' => ['required', 'date_format:H:i'],
            'rescheduleReason' => ['nullable', 'string', 'max:500'],
            'rescheduleServiceIds' => ['required', 'array', 'min:1'],
            'rescheduleServiceIds.*' => [Rule::in($serviceIds)],
        ]);

        DB::transaction(function () use ($appointment, $validated, $company, $branch) {
            $new = Appointment::query()->create([
                'company_id' => $appointment->company_id,
                'branch_id' => $appointment->branch_id,
                'client_id' => $appointment->client_id,
                'treatment_plan_id' => $appointment->treatment_plan_id,
                'rescheduled_from_id' => $appointment->id,
                'scheduled_at' => Carbon::parse($validated['rescheduleDate'] . ' ' . $validated['rescheduleTime']),
                'duration_minutes' => $appointment->duration_minutes,
                'status' => 'scheduled',
                'clinical_notes' => $appointment->clinical_notes,
                'reschedule_reason' => $validated['rescheduleReason'] ?: null,
            ]);

            $services = $company->services()
                ->where(function ($query) use ($branch) {
                    $query->whereNull('branch_id')->orWhere('branch_id', $branch->id);
                })
                ->whereIn('id', $validated['rescheduleServiceIds'])
                ->get();

            foreach ($services as $service) {
                $new->services()->create($this->appointmentServicePayload($service));
            }

            $appointment->update([
                'status' => 'rescheduled',
                'reschedule_reason' => $validated['rescheduleReason'] ?: null,
            ]);
        });

        $this->showRescheduleModal = false;
    }

    public function openAddServices(int $appointmentId): void
    {
        $appointment = $this->appointmentQuery()->whereKey($appointmentId)->firstOrFail();

        $this->addServicesAppointmentId = $appointment->id;
        $this->addServicesSearch = '';
        $this->addServiceIds = [];
        $this->showAddServicesModal = true;
    }

    public function saveAddedServices(): void
    {
        $appointment = $this->appointmentQuery()->whereKey($this->addServicesAppointmentId)->firstOrFail();
        $company = $this->company();
        $branch = $this->activeBranch();
        $serviceIds = $this->availableServiceIds($company, $branch);

        $validated = $this->validate([
            'addServiceIds' => ['required', 'array', 'min:1'],
            'addServiceIds.*' => [Rule::in($serviceIds)],
        ]);

        $existingIds = $appointment->services()->pluck('service_id')->filter()->all();
        $services = $company->services()
            ->where(function ($query) use ($branch) {
                $query->whereNull('branch_id')->orWhere('branch_id', $branch->id);
            })
            ->whereIn('id', $validated['addServiceIds'])
            ->whereNotIn('id', $existingIds)
            ->get();

        foreach ($services as $service) {
            $appointment->services()->create($this->appointmentServicePayload($service));
        }

        if ($services->isEmpty()) {
            $this->addError('addServiceIds', 'Los servicios seleccionados ya estan en esta cita.');

            return;
        }

        $this->showAddServicesModal = false;
        $this->addServiceIds = [];
    }

    public function completeAppointmentService(int $appointmentServiceId): void
    {
        $serviceLine = AppointmentService::query()
            ->whereHas('appointment', function ($query) {
                $query->where('company_id', $this->company()->id)
                    ->where('branch_id', $this->activeBranch()->id);
            })
            ->whereKey($appointmentServiceId)
            ->firstOrFail();

        $serviceLine->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $appointment = $serviceLine->appointment;

        if ($appointment->services()->where('status', '!=', 'completed')->doesntExist()) {
            $appointment->update(['status' => 'completed']);
        }
    }

    public function openHistory(int $clientId): void
    {
        $this->historyClientId = $clientId;
        $this->historyTab = 'appointments';
        $this->showHistoryModal = true;
    }

    public function appointmentStatusLabel(?string $status): string
    {
        return match ($status) {
            'scheduled' => 'Programada',
            'rescheduled' => 'Reagendada',
            'completed' => 'Finalizada',
            'no_show' => 'No asistio',
            default => 'Pendiente',
        };
    }

    public function render()
    {
        $company = $this->company();
        $branch = $this->activeBranch();
        $day = Carbon::parse($this->selectedDate);

        $appointments = $company->appointments()
            ->with([
                'client',
                'services',
                'payments.splits',
                'payments.items',
                'treatmentPlan',
                'attendedBy',
                'rescheduledAppointments',
            ])
            ->where('branch_id', $branch->id)
            ->whereBetween('scheduled_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()])
            ->when(trim($this->appointmentSearch) !== '', function ($query) {
                $search = '%' . trim($this->appointmentSearch) . '%';

                $query->where(function ($query) use ($search) {
                    $query->whereHas('client', function ($query) use ($search) {
                        $query->where('full_name', 'like', $search)
                            ->orWhere('identity_number', 'like', $search)
                            ->orWhere('phone', 'like', $search);
                    })->orWhereHas('services', function ($query) use ($search) {
                        $query->where('name', 'like', $search);
                    });
                });
            })
            ->orderBy('scheduled_at')
            ->get();

        $historyClient = $this->historyClientId
            ? $company->clients()->with(['appointments.services', 'appointments.payments', 'treatmentPlans.payments'])->whereKey($this->historyClientId)->first()
            : null;

        $historyProductItems = $historyClient
            ? TreatmentPaymentItem::query()
            ->with(['payment', 'product', 'batch', 'soldBy'])
            ->where('type', 'product')
            ->whereHas('payment', fn($query) => $query
                ->where('company_id', $company->id)
                ->where('client_id', $historyClient->id))
            ->latest()
            ->get()
            : collect();

        $historyPendingCharges = $historyClient
            ? $company->clientCharges()
            ->with('soldBy')
            ->where('client_id', $historyClient->id)
            ->whereIn('status', ['pending', 'partial'])
            ->orderBy('charged_at')
            ->get()
            : collect();

        return view('livewire.clinic.agenda-manager', [
            'branch' => $branch,
            'appointments' => $appointments,
            'clients' => $this->filteredClients($company),
            'staffUsers' => $company->users()->orderBy('name')->get(),
            'services' => $this->filteredServices($company, $branch),
            'rescheduleServices' => $this->filteredModalServices($company, $branch, $this->rescheduleServiceSearch),
            'addServices' => $this->filteredModalServices($company, $branch, $this->addServicesSearch),
            'productBatches' => $this->paymentProductBatches($company, $branch),
            'paymentChargeSummary' => $this->paymentChargeSummary($company, $branch),
            'paymentAppointment' => $this->paymentAppointmentId
                ? $company->appointments()->with(['services', 'payments.splits', 'payments.items'])->whereKey($this->paymentAppointmentId)->first()
                : null,
            'pendingCharges' => $this->paymentAppointmentId
                ? $this->pendingCharges($company->appointments()->whereKey($this->paymentAppointmentId)->value('client_id') ?? 0, $this->editingPaymentId)
                : collect(),
            'paymentTickets' => $this->editingPaymentId
                ? $company->cashboxTickets()
                ->where('branch_id', $branch->id)
                ->where('treatment_payment_id', $this->editingPaymentId)
                ->where('type', 'payment')
                ->latest()
                ->get()
                : collect(),
            'historyClient' => $historyClient,
            'historyProductItems' => $historyProductItems,
            'historyPendingProductCharges' => $historyPendingCharges->where('type', 'product')->values(),
            'historyPendingServiceCharges' => $historyPendingCharges->where('type', 'service')->values(),
            'canDeleteAppointments' => $this->canDeleteAppointments(),
        ]);
    }

    private function storePayment(Company $company, Branch $branch, Client $client, Appointment $appointment, ?TreatmentPlan $plan, float $amount, array $data): int
    {
        $method = $data['paymentMethod'];
        $cashAmount = (float) ($data['paymentCashAmount'] ?? 0);
        $qrAmount = (float) ($data['paymentQrAmount'] ?? 0);

        $payment = TreatmentPayment::query()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'client_id' => $client->id,
            'appointment_id' => $appointment->id,
            'treatment_plan_id' => $plan?->id,
            'received_by_user_id' => Auth::id(),
            'amount' => $amount,
            'method' => $method,
            'invoice_requested' => (bool) ($data['invoiceRequested'] ?? false),
            'reference' => $data['paymentReference'] ?: null,
            'notes' => $data['paymentNotes'] ?: null,
            'paid_at' => $appointment->scheduled_at,
        ]);

        if ($method === 'mixed') {
            if ($cashAmount > 0) {
                $payment->splits()->create([
                    'method' => 'cash',
                    'amount' => $cashAmount,
                    'reference' => $data['paymentReference'] ?: null,
                ]);
            }
            if ($qrAmount > 0) {
                $payment->splits()->create([
                    'method' => 'qr',
                    'amount' => $qrAmount,
                    'reference' => $data['paymentReference'] ?: null,
                ]);
            }
        } else {
            $payment->splits()->create([
                'method' => $method,
                'amount' => $amount,
                'reference' => $data['paymentReference'] ?: null,
            ]);
        }

        $paymentLeft = $amount;

        foreach ($appointment->services as $serviceLine) {
            $chargedTotal = (float) $serviceLine->price;
            $paidAmount = min($paymentLeft, $chargedTotal);
            $paymentLeft -= $paidAmount;
            $charge = $this->createClientCharge([
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'client_id' => $client->id,
                'appointment_id' => $appointment->id,
                'appointment_service_id' => $serviceLine->id,
                'type' => 'service',
                'name' => $serviceLine->name,
                'quantity' => 1,
                'unit_price' => $serviceLine->price,
                'total_amount' => $chargedTotal,
                'paid_amount' => 0,
                'balance_amount' => $chargedTotal,
                'charged_at' => $appointment->scheduled_at,
            ]);
            $this->applyChargePayment($charge, $payment, $paidAmount);

            if ($paidAmount > 0) {
                $payment->items()->create([
                    'client_charge_id' => $charge->id,
                    'appointment_service_id' => $serviceLine->id,
                    'type' => 'service',
                    'name' => $serviceLine->name,
                    'quantity' => 1,
                    'unit_price' => $serviceLine->price,
                    'charged_total' => $chargedTotal,
                    'total' => $paidAmount,
                ]);
            }
        }

        $appointment->update(['locked_by_payment' => true]);
        $plan?->increment('paid_amount', $amount);

        return $payment->id;
    }

    private function openPaymentTicket(int $paymentId, bool $autoPrint = false): void
    {
        $company = $this->company();
        $branch = $this->activeBranch();
        $payment = $company->treatmentPayments()
            ->with(['company', 'client', 'splits', 'items', 'chargePayments.charge', 'performedBy', 'receivedBy'])
            ->where('branch_id', $branch->id)
            ->whereKey($paymentId)
            ->firstOrFail();
        $payload = PaymentTicketBuilder::payload($payment, $branch);
        $ticket = CashboxTicket::query()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'treatment_payment_id' => $payment->id,
            'type' => 'payment',
            'ticket_number' => 'PAY-' . $company->id . '-' . $branch->id . '-' . now()->format('YmdHis') . '-' . random_int(100, 999),
            'title' => 'Ticket de cobro',
            'payload' => $payload,
            'status' => 'generated',
        ]);
        $payload['ticket_id'] = $ticket->id;
        $payload['ticket_number'] = $ticket->ticket_number;
        $ticket->update(['payload' => $payload]);

        $this->paymentTicketPreview = $payload;
        $this->showPaymentTicketPreview = true;

        if ($autoPrint && $payload['printer_enabled'] && $payload['printer_name']) {
            $this->dispatch('rumika-auto-print-ticket');
        }
    }

    private function reversePaymentProductStock(TreatmentPayment $payment): void
    {
        foreach ($payment->items()->where('type', 'product')->get() as $item) {
            if ($item->inventory_product_batch_id) {
                InventoryProductBatch::query()
                    ->whereKey($item->inventory_product_batch_id)
                    ->increment('current_quantity', (float) $item->quantity);
            }
        }
    }

    private function reversePaymentClientCharges(TreatmentPayment $payment): void
    {
        $chargeIds = $payment->items()->pluck('client_charge_id')->filter()->unique();

        foreach ($payment->chargePayments()->with('charge')->get() as $chargePayment) {
            $charge = $chargePayment->charge;

            if (! $charge) {
                continue;
            }

            $paidAmount = max(0, (float) $charge->paid_amount - (float) $chargePayment->amount);
            $balanceAmount = max(0, (float) $charge->total_amount - $paidAmount);
            $charge->update([
                'paid_amount' => $paidAmount,
                'balance_amount' => $balanceAmount,
                'status' => $balanceAmount <= 0 ? 'paid' : ($paidAmount > 0 ? 'partial' : 'pending'),
            ]);
        }

        $payment->chargePayments()->delete();

        ClientCharge::query()
            ->whereIn('id', $chargeIds)
            ->whereDoesntHave('payments')
            ->delete();
    }

    private function deletePaymentWithReversal(TreatmentPayment $payment, int $companyId): void
    {
        $this->reversePaymentProductStock($payment);
        $this->reversePaymentClientCharges($payment);

        if ($payment->treatment_plan_id) {
            $payment->treatmentPlan?->decrement('paid_amount', (float) $payment->amount);
        }

        InventoryMovement::query()
            ->where('company_id', $companyId)
            ->where('reference', 'PAY-' . $payment->id)
            ->delete();

        $payment->splits()->delete();
        $payment->items()->delete();
        $payment->delete();
    }

    private function normalizedPaymentProductLines(array $lines, Company $company, Branch $branch, ?int $soldByUserId)
    {
        return collect($lines)
            ->filter(fn(array $line) => ! empty($line['batch_id']) && (float) ($line['quantity'] ?? 0) > 0)
            ->map(function (array $line) use ($company, $branch, $soldByUserId) {
                $batch = $company->inventoryBatches()
                    ->with('product')
                    ->where('branch_id', $branch->id)
                    ->whereKey($line['batch_id'])
                    ->firstOrFail();
                $quantity = round((float) $line['quantity'], 2);
                $unitPrice = round((float) ($line['unit_price'] ?: $batch->unit_cost), 2);
                $chargedTotal = round($quantity * $unitPrice, 2);
                $paidAmount = round((float) (($line['paid_amount'] ?? '') !== '' ? $line['paid_amount'] : $chargedTotal), 2);

                if ($paidAmount > $chargedTotal) {
                    throw ValidationException::withMessages([
                        'paymentProductLines' => 'El pago de ' . $batch->product->name . ' no puede superar Bs ' . number_format($chargedTotal, 2) . '.',
                    ]);
                }

                return [
                    'batch_id' => $batch->id,
                    'batch' => $batch,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'paid_amount' => $paidAmount,
                    'sold_by_user_id' => $soldByUserId,
                    'stock_shortage_reason' => trim((string) ($line['stock_shortage_reason'] ?? '')),
                ];
            })
            ->values();
    }

    private function normalizePaymentAmountInputs(): void
    {
        $this->paymentCashAmount = $this->normalizeDecimalInput($this->paymentCashAmount);
        $this->paymentQrAmount = $this->normalizeDecimalInput($this->paymentQrAmount);

        foreach ($this->paymentServiceLinePrices as $key => $amount) {
            $this->paymentServiceLinePrices[$key] = $this->normalizeDecimalInput($amount);
        }

        foreach ($this->paymentServiceLinePayments as $key => $amount) {
            $this->paymentServiceLinePayments[$key] = $this->normalizeDecimalInput($amount);
        }

        foreach ($this->pendingChargePayments as $key => $amount) {
            $this->pendingChargePayments[$key] = $this->normalizeDecimalInput($amount);
        }

        foreach ($this->extraPaymentSplits as $index => $split) {
            $this->extraPaymentSplits[$index]['amount'] = $this->normalizeDecimalInput($split['amount'] ?? '');
        }

        foreach ($this->paymentProductLines as $index => $line) {
            $this->paymentProductLines[$index]['quantity'] = $this->normalizeDecimalInput($line['quantity'] ?? '');
            $this->paymentProductLines[$index]['unit_price'] = $this->normalizeDecimalInput($line['unit_price'] ?? '');
            $this->paymentProductLines[$index]['paid_amount'] = $this->normalizeDecimalInput($line['paid_amount'] ?? '');
        }
    }

    private function normalizeDecimalInput(mixed $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        return str_replace(',', '.', $value);
    }

    private function normalizedPendingChargePayments(array $payments, Company $company, Branch $branch, int $clientId)
    {
        return collect($payments)
            ->filter(fn($amount) => (float) $amount > 0)
            ->map(function ($amount, $chargeId) use ($company, $branch, $clientId) {
                $charge = $company->clientCharges()
                    ->where('branch_id', $branch->id)
                    ->where('client_id', $clientId)
                    ->whereIn('status', ['pending', 'partial'])
                    ->whereKey($chargeId)
                    ->firstOrFail();
                $payAmount = round((float) $amount, 2);

                if ($payAmount > (float) $charge->balance_amount) {
                    throw ValidationException::withMessages([
                        'pendingChargePayments.' . $chargeId => 'El abono de ' . $charge->name . ' no puede superar Bs ' . number_format((float) $charge->balance_amount, 2) . '.',
                    ]);
                }

                return [
                    'charge_id' => (int) $chargeId,
                    'amount' => $payAmount,
                ];
            })
            ->values();
    }

    private function createClientCharge(array $attributes): ClientCharge
    {
        $attributes['status'] = 'pending';

        return ClientCharge::query()->create($attributes);
    }

    private function applyChargePayment(ClientCharge $charge, TreatmentPayment $payment, float $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        $amount = min(round($amount, 2), (float) $charge->balance_amount);
        $paidAmount = round((float) $charge->paid_amount + $amount, 2);
        $balanceAmount = max(0, round((float) $charge->total_amount - $paidAmount, 2));

        $charge->payments()->create([
            'treatment_payment_id' => $payment->id,
            'amount' => $amount,
        ]);
        $charge->update([
            'paid_amount' => $paidAmount,
            'balance_amount' => $balanceAmount,
            'status' => $balanceAmount <= 0 ? 'paid' : 'partial',
        ]);
    }

    private function editingPaymentBatchQuantity(int $batchId): float
    {
        if (! $this->editingPaymentId) {
            return 0.0;
        }

        return (float) TreatmentPayment::query()
            ->whereKey($this->editingPaymentId)
            ->firstOrFail()
            ->items()
            ->where('inventory_product_batch_id', $batchId)
            ->sum('quantity');
    }

    private function paymentMethodFromAmounts(float $cash, float $qr, $extraSplits): string
    {
        $cashTotal = $cash + (float) $extraSplits->where('method', 'cash')->sum('amount');
        $qrTotal = $qr + (float) $extraSplits->where('method', 'qr')->sum('amount');

        return $cashTotal > 0 && $qrTotal > 0 ? 'mixed' : ($qrTotal > 0 ? 'qr' : 'cash');
    }

    private function paymentProductBatches(Company $company, Branch $branch)
    {
        $search = trim($this->productSearch);
        $selectedBatchIds = collect($this->paymentProductLines)
            ->pluck('batch_id')
            ->filter()
            ->map(fn($id) => (int) $id)
            ->all();

        $this->ensureCatalogBatchesForBranch($company, $branch);

        return $company->inventoryBatches()
            ->with(['product.brand', 'product.useArea'])
            ->where('branch_id', $branch->id)
            ->where(function ($query) use ($selectedBatchIds) {
                $query->where('status', 'available')
                    ->when($selectedBatchIds, fn($selectedQuery) => $selectedQuery->orWhereIn('id', $selectedBatchIds));
            })
            ->where(fn ($query) => $this->onlyStockedOrSingleEmergencyBatch($query, $branch, $selectedBatchIds))
            ->when($selectedBatchIds, fn($query) => $query->orderByRaw('CASE WHEN id IN (' . implode(',', array_fill(0, count($selectedBatchIds), '?')) . ') THEN 0 ELSE 1 END', $selectedBatchIds))
            ->when($search !== '', fn($query) => $query->where(function ($nested) use ($search, $selectedBatchIds) {
                $nested->where('lot_code', 'like', "%{$search}%")
                    ->orWhereHas('product', fn($productQuery) => $productQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhereHas('brand', fn($brandQuery) => $brandQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('useArea', fn($areaQuery) => $areaQuery->where('name', 'like', "%{$search}%")))
                    ->when($selectedBatchIds, fn($selectedQuery) => $selectedQuery->orWhereIn('id', $selectedBatchIds));
            }))
            ->orderByDesc('current_quantity')
            ->orderByRaw('expires_at IS NULL, expires_at ASC')
            ->limit($search === '' ? 12 : 25)
            ->get();
    }

    private function onlyStockedOrSingleEmergencyBatch($query, Branch $branch, array $selectedBatchIds = []): void
    {
        $query->where('current_quantity', '>', 0)
            ->when($selectedBatchIds, fn ($selectedQuery) => $selectedQuery->orWhereIn('id', $selectedBatchIds))
            ->orWhereIn('id', function ($subquery) use ($branch) {
                $subquery
                    ->selectRaw('MIN(zero_batches.id)')
                    ->from('inventory_product_batches as zero_batches')
                    ->where('zero_batches.branch_id', $branch->id)
                    ->where('zero_batches.status', 'available')
                    ->where('zero_batches.current_quantity', '<=', 0)
                    ->whereNotExists(function ($exists) {
                        $exists
                            ->selectRaw('1')
                            ->from('inventory_product_batches as positive_batches')
                            ->whereColumn('positive_batches.branch_id', 'zero_batches.branch_id')
                            ->whereColumn('positive_batches.inventory_product_id', 'zero_batches.inventory_product_id')
                            ->where('positive_batches.status', 'available')
                            ->where('positive_batches.current_quantity', '>', 0);
                    })
                    ->groupBy('zero_batches.inventory_product_id');
            });
    }

    private function ensureCatalogBatchesForBranch(Company $company, Branch $branch): void
    {
        $existingProductIds = InventoryProductBatch::query()
            ->where('company_id', $company->id)
            ->where('branch_id', $branch->id)
            ->pluck('inventory_product_id')
            ->all();

        $company->inventoryProducts()
            ->where('status', 'active')
            ->when($existingProductIds, fn($query) => $query->whereNotIn('id', $existingProductIds))
            ->select(['id', 'company_id', 'purchase_cost'])
            ->chunkById(100, function ($products) use ($company, $branch) {
                foreach ($products as $product) {
                    InventoryProductBatch::query()->firstOrCreate(
                        [
                            'branch_id' => $branch->id,
                            'inventory_product_id' => $product->id,
                            'lot_code' => 'CATALOGO-' . $branch->id . '-' . $product->id,
                        ],
                        [
                            'company_id' => $company->id,
                            'received_at' => now()->toDateString(),
                            'initial_quantity' => 0,
                            'current_quantity' => 0,
                            'unit_cost' => (float) $product->purchase_cost,
                            'status' => 'available',
                        ],
                    );
                }
            });
    }

    private function paymentChargeSummary(Company $company, Branch $branch): array
    {
        $serviceTotal = 0.0;

        if ($this->paymentAppointmentId && $this->paymentServiceLineIds) {
            $appointment = $company->appointments()->whereKey($this->paymentAppointmentId)->first();
            $serviceLineIds = collect($this->paymentServiceLineIds)->map(fn($id) => (int) $id)->all();
            $serviceTotal = $appointment
                ? (float) $appointment->services()->whereIn('id', $serviceLineIds)->get()->sum(function ($serviceLine) {
                    return (float) (($this->paymentServiceLinePrices[(string) $serviceLine->id] ?? '') !== ''
                        ? $this->paymentServiceLinePrices[(string) $serviceLine->id]
                        : $serviceLine->price);
                })
                : 0.0;
        }

        $productTotal = collect($this->paymentProductLines)
            ->filter(fn(array $line) => ! empty($line['batch_id']) && (float) ($line['quantity'] ?? 0) > 0)
            ->sum(function (array $line) use ($company, $branch) {
                $batch = $company->inventoryBatches()
                    ->where('branch_id', $branch->id)
                    ->whereKey($line['batch_id'])
                    ->first();

                if (! $batch) {
                    return 0.0;
                }

                $quantity = (float) ($line['quantity'] ?? 0);
                $unitPrice = (float) (($line['unit_price'] ?? '') !== '' ? $line['unit_price'] : $batch->unit_cost);

                return $quantity * $unitPrice;
            });

        return [
            'services' => round($serviceTotal, 2),
            'products' => round($productTotal, 2),
            'pending' => round(collect($this->pendingChargePayments)->sum(fn($amount) => (float) $amount), 2),
            'total' => round($serviceTotal + $productTotal, 2),
            'pay_now' => round(
                collect($this->paymentServiceLinePayments)->sum(fn($amount) => (float) $amount)
                    + collect($this->paymentProductLines)->sum(fn(array $line) => (float) ($line['paid_amount'] ?? 0))
                    + collect($this->pendingChargePayments)->sum(fn($amount) => (float) $amount),
                2
            ),
        ];
    }

    private function pendingCharges(int $clientId, ?int $exceptPaymentId = null)
    {
        $query = $this->company()->clientCharges()
            ->where('branch_id', $this->activeBranch()->id)
            ->where('client_id', $clientId)
            ->whereIn('status', ['pending', 'partial'])
            ->where('balance_amount', '>', 0)
            ->orderBy('charged_at');

        return $query->get();
    }

    private function pendingChargeInputs(int $clientId, ?int $exceptPaymentId = null): array
    {
        return $this->pendingCharges($clientId, $exceptPaymentId)
            ->mapWithKeys(fn(ClientCharge $charge) => [(string) $charge->id => ''])
            ->all();
    }

    private function existingChargeForAppointmentService(int $appointmentServiceId): ?ClientCharge
    {
        return ClientCharge::query()
            ->where('appointment_service_id', $appointmentServiceId)
            ->whereIn('status', ['pending', 'partial'])
            ->where('balance_amount', '>', 0)
            ->first();
    }

    private function currentInventoryCount(Branch $branch)
    {
        return $this->company()->inventoryCounts()->firstOrCreate(
            [
                'branch_id' => $branch->id,
                'inventory_use_area_id' => null,
                'status' => 'in_process',
            ],
            [
                'name' => 'Inventario ' . $branch->name . ' ' . now()->format('Y-m'),
                'opened_at' => now(),
            ],
        );
    }

    private function currentInventoryCountForProduct(Branch $branch, int $productId)
    {
        $company = $this->company();
        $product = $company->inventoryProducts()->whereKey($productId)->firstOrFail();
        $zoneCount = $company->inventoryCounts()
            ->where('branch_id', $branch->id)
            ->where('inventory_use_area_id', $product->inventory_use_area_id)
            ->where('status', 'in_process')
            ->first();

        return $zoneCount ?: $this->currentInventoryCount($branch);
    }

    private function appointmentQuery()
    {
        $company = $this->company();

        return $company->appointments()
            ->with(['company', 'branch', 'client', 'treatmentPlan'])
            ->where('branch_id', $this->activeBranch()->id);
    }

    private function authorizeAppointmentDeletion(): void
    {
        abort_unless($this->canDeleteAppointments(), 403);
    }

    private function canDeleteAppointments(): bool
    {
        $company = $this->company();
        $companyRole = Auth::user()
            ->companies()
            ->where('companies.id', $company->id)
            ->value('company_user.role');

        if ($this->roleNameCanDeleteAppointments($companyRole)) {
            return true;
        }

        $branchRole = Auth::user()
            ->branches()
            ->where('branches.id', $this->activeBranch()->id)
            ->leftJoin('roles', 'roles.id', '=', 'branch_user.role_id')
            ->select(['roles.slug', 'roles.permissions'])
            ->first();

        if (! $branchRole) {
            return false;
        }

        return $this->roleNameCanDeleteAppointments($branchRole->slug)
            || $this->rolePermissionsCanDeleteAppointments($branchRole->permissions);
    }

    private function roleNameCanDeleteAppointments(?string $role): bool
    {
        return in_array($role, [
            'owner',
            'super_admin',
            'super-administrador',
            'admin',
            'administrator',
            'administrador',
        ], true);
    }

    private function rolePermissionsCanDeleteAppointments(mixed $permissions): bool
    {
        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true);
        }

        if (! is_array($permissions)) {
            return false;
        }

        $agendaPermissions = $permissions['agenda'] ?? [];

        if (is_string($agendaPermissions)) {
            $agendaPermissions = [$agendaPermissions];
        }

        return is_array($agendaPermissions)
            && (in_array('delete', $agendaPermissions, true) || in_array('*', $agendaPermissions, true));
    }

    private function company(): Company
    {
        return Auth::user()->companies()->firstOrFail();
    }

    private function activeBranch(): Branch
    {
        $company = $this->company();
        $branches = Auth::user()->branches()->where('company_id', $company->id)->orderBy('name')->get();
        $branches = $branches->isNotEmpty() ? $branches : $company->branches()->orderBy('name')->get();

        return $branches->firstWhere('id', session('active_branch_id'))
            ?? $branches->first()
            ?? $company->branches()->firstOrFail();
    }

    private function resetAppointmentForm(): void
    {
        $this->reset(['clientSearch', 'clientId', 'clientName', 'clientCi', 'clientPhone', 'clientEmail', 'clientNotes', 'serviceSearch', 'serviceIds', 'treatmentName', 'appointmentNotes', 'paymentAmount', 'paymentCashAmount', 'paymentQrAmount', 'paymentReference', 'paymentNotes', 'paymentServiceLinePrices', 'paymentServiceLinePayments', 'paymentProductLines', 'paymentProductSoldByUserId', 'pendingChargePayments', 'productSearch']);
        $this->clientMode = 'existing';
        $this->scheduledDate = $this->selectedDate;
        $this->scheduledTime = '09:00';
        $this->durationMinutes = '60';
        $this->createTreatmentPlan = true;
        $this->plannedSessions = '1';
        $this->paymentMethod = 'cash';
        $this->invoiceRequested = false;
        $this->resetErrorBag();
    }

    private function filteredClients(Company $company)
    {
        $search = trim($this->clientSearch);

        return $company->clients()
            ->when($search !== '', fn($query) => $query->where(fn($nested) => $nested
                ->where('full_name', 'like', "%{$search}%")
                ->orWhere('identity_number', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")))
            ->orderBy('full_name')
            ->limit($search === '' ? 8 : 15)
            ->get();
    }

    private function filteredServices(Company $company, Branch $branch)
    {
        $search = trim($this->serviceSearch);

        return $company->services()
            ->where(function ($query) use ($branch) {
                $query->whereNull('branch_id')->orWhere('branch_id', $branch->id);
            })
            ->when($search !== '', fn($query) => $query->where(fn($nested) => $nested
                ->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")))
            ->orderBy('name')
            ->limit($search === '' ? 3 : 15)
            ->get();
    }

    private function filteredModalServices(Company $company, Branch $branch, string $search)
    {
        $search = trim($search);

        return $company->services()
            ->where(function ($query) use ($branch) {
                $query->whereNull('branch_id')->orWhere('branch_id', $branch->id);
            })
            ->when($search !== '', fn($query) => $query->where(fn($nested) => $nested
                ->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")))
            ->orderBy('name')
            ->limit($search === '' ? 8 : 15)
            ->get();
    }

    private function availableServiceIds(Company $company, Branch $branch): array
    {
        return $company->services()
            ->where(function ($query) use ($branch) {
                $query->whereNull('branch_id')->orWhere('branch_id', $branch->id);
            })
            ->pluck('id')
            ->all();
    }

    private function appointmentServicePayload(Service $service): array
    {
        return [
            'service_id' => $service->id,
            'name' => $service->name,
            'price' => $service->price,
            'duration_minutes' => $service->duration_minutes,
            'status' => 'pending',
        ];
    }

    private function initialAppointmentPaymentAmount(array $data): float
    {
        if (($data['paymentMethod'] ?? 'cash') === 'mixed') {
            return (float) ($data['paymentCashAmount'] ?? 0) + (float) ($data['paymentQrAmount'] ?? 0);
        }

        return (float) ($data['paymentAmount'] ?? 0);
    }
}
