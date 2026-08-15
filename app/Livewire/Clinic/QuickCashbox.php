<?php

namespace App\Livewire\Clinic;

use App\Models\Branch;
use App\Models\CashboxSession;
use App\Models\CashboxTicket;
use App\Models\Company;
use App\Models\TreatmentPaymentSplit;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;

class QuickCashbox extends Component
{
    public bool $showModal = false;
    public bool $showPrintPreview = false;

    public string $selectedDate = '';
    public string $cashboxMessage = '';
    public string $historyTab = 'services';
    public string $paymentMethodFilter = '';
    public string $expenseSourceFilter = '';
    public string $openingAmount = '0';
    public string $countedCashAmount = '';
    public string $openingNotes = '';
    public string $closingNotes = '';
    public array $ticketPreview = [];
    public ?int $confirmingCashboxSessionDeleteId = null;

    public function mount(): void
    {
        $this->selectedDate = now()->format('Y-m-d');
    }

    public function open(): void
    {
        $this->showModal = true;
    }

    public function close(): void
    {
        $this->showModal = false;
        $this->showPrintPreview = false;
        $this->cashboxMessage = '';
        $this->ticketPreview = [];
    }

    public function openCashbox(): void
    {
        $this->validate([
            'openingAmount' => ['required', 'numeric', 'min:0'],
            'openingNotes' => ['nullable', 'string', 'max:500'],
        ]);

        $company = $this->company();
        $branch = $this->activeBranch();
        $date = Carbon::parse($this->selectedDate)->toDateString();

        $openSession = $company->cashboxSessions()
            ->where('branch_id', $branch->id)
            ->where('status', 'open')
            ->with('openedBy')
            ->first();

        if ($openSession) {
            $this->cashboxMessage = $openSession->opened_by_user_id === Auth::id()
                ? 'Ya tienes una caja abierta en esta sucursal.'
                : 'Caja abierta por ' . $openSession->openedBy?->name . '. Primero debe cerrarse esa caja.';

            return;
        }

        $shiftNumber = (int) $company->cashboxSessions()
            ->where('branch_id', $branch->id)
            ->whereDate('business_date', $date)
            ->max('shift_number') + 1;

        $session = $company->cashboxSessions()->create([
            'branch_id' => $branch->id,
            'business_date' => $date,
            'shift_number' => $shiftNumber,
            'status' => 'open',
            'opening_amount' => $this->decimal($this->openingAmount),
            'opened_by_user_id' => Auth::id(),
            'opened_at' => now(),
            'opening_notes' => $this->openingNotes ?: null,
        ]);

        $this->createSessionTicket($session, 'session_open', 'Apertura de caja');
        $this->cashboxMessage = 'Caja abierta para el turno ' . $shiftNumber . '.';
    }

    public function closeCashbox(): void
    {
        $this->validate([
            'countedCashAmount' => ['nullable', 'numeric', 'min:0'],
            'closingNotes' => ['nullable', 'string', 'max:500'],
        ]);

        $company = $this->company();
        $branch = $this->activeBranch();
        $session = $company->cashboxSessions()
            ->where('branch_id', $branch->id)
            ->where('status', 'open')
            ->first();

        if (! $session) {
            $this->cashboxMessage = 'Primero debes abrir la caja.';

            return;
        }

        if ($session->status === 'closed') {
            $this->cashboxMessage = 'Esta caja ya fue cerrada.';

            return;
        }

        $totals = $this->sessionTotals($session);
        $expectedCash = (float) $session->opening_amount + $totals['cash'] - $totals['expenses'];
        $countedCash = $this->countedCashAmount !== ''
            ? $this->decimal($this->countedCashAmount)
            : $expectedCash;

        $session->update([
            'status' => 'closed',
            'closed_by_user_id' => Auth::id(),
            'closed_at' => now(),
            'closing_notes' => $this->closingNotes ?: null,
            'cash_total' => $totals['cash'],
            'qr_total' => $totals['qr'],
            'expense_total' => $totals['expenses'],
            'net_total' => $totals['cash'] + $totals['qr'] - $totals['expenses'],
            'expected_cash_amount' => $expectedCash,
            'counted_cash_amount' => $countedCash,
            'cash_difference' => $countedCash - $expectedCash,
        ]);

        $ticket = $this->createSessionTicket($session->refresh(), 'session_close', 'Cierre de caja');
        $this->ticketPreview = $ticket->payload;
        $this->showPrintPreview = true;
        $this->cashboxMessage = 'Caja cerrada correctamente.';
    }

    public function previewPrint(): void
    {
        $company = $this->company();

        $branch = $this->activeBranch();

        $session =
            $this->visibleCashboxSession(
                $company,
                $branch,
                Carbon::parse(
                    $this->selectedDate
                )
            );

        $ticket = $session
            ? $this->ticketForSessionPreview(
                $session
            )
            : $this->createDailyTicket(
                $company,
                $branch
            );

        $this->ticketPreview =
            $ticket->payload;

        $this->showPrintPreview = true;
    }

    public function closePrintPreview(): void
    {
        $this->showPrintPreview = false;
        $this->ticketPreview = [];
    }

    public function previewTicket(int $ticketId): void
    {
        $ticket = $this->company()
            ->cashboxTickets()
            ->where('branch_id', $this->activeBranch()->id)
            ->whereKey($ticketId)
            ->firstOrFail();

        $ticket->increment('reprint_count');

        $payload = $ticket->payload ?? [];

        $branch = $this->activeBranch();

        $businessDate = ! empty($payload['business_date'])
            ? Carbon::createFromFormat(
                'd/m/Y',
                $payload['business_date']
            )
            : Carbon::parse($ticket->created_at);

        $payload['raw_ticket'] = $this->buildRawTicket(
            title: $payload['title']
                ?? $ticket->title
                ?? 'Detalle de caja',
            branch: $branch,
            businessDate: $businessDate,
            services: $payload['services'] ?? [],
            products: $payload['products'] ?? [],
            totals: $payload['totals'] ?? [],
            expenses: $payload['expenses'] ?? [],
            session: $ticket->session
        );

        $ticket->update([
            'payload' => $payload,
        ]);

        $this->ticketPreview = $payload;
        $this->showPrintPreview = true;
    }

    public function confirmDeleteClosedCashbox(int $sessionId): void
    {
        if (! $this->canManageCashboxClosures()) {
            $this->cashboxMessage = 'Solo administrador o super administrador puede eliminar cajas cerradas.';

            return;
        }

        $session = $this->company()
            ->cashboxSessions()
            ->where('branch_id', $this->activeBranch()->id)
            ->where('status', 'closed')
            ->whereKey($sessionId)
            ->first();

        if (! $session) {
            $this->cashboxMessage = 'Solo se pueden eliminar cajas cerradas.';

            return;
        }

        $this->confirmingCashboxSessionDeleteId = $session->id;
    }

    public function cancelDeleteClosedCashbox(): void
    {
        $this->confirmingCashboxSessionDeleteId = null;
    }

    public function deleteClosedCashbox(): void
    {
        if (! $this->confirmingCashboxSessionDeleteId) {
            return;
        }

        if (! $this->canManageCashboxClosures()) {
            $this->cashboxMessage = 'Solo administrador o super administrador puede eliminar cajas cerradas.';
            $this->confirmingCashboxSessionDeleteId = null;

            return;
        }

        $session = $this->company()
            ->cashboxSessions()
            ->where('branch_id', $this->activeBranch()->id)
            ->where('status', 'closed')
            ->whereKey($this->confirmingCashboxSessionDeleteId)
            ->first();

        if (! $session) {
            $this->cashboxMessage = 'No se encontro una caja cerrada para eliminar.';
            $this->confirmingCashboxSessionDeleteId = null;

            return;
        }

        $businessDate = $session->business_date->toDateString();

        $session->tickets()->delete();
        $session->delete();

        $remainingSessions = $this->company()
            ->cashboxSessions()
            ->where('branch_id', $this->activeBranch()->id)
            ->whereDate('business_date', $businessDate)
            ->exists();

        $this->openingAmount = '0';
        $this->countedCashAmount = '';
        $this->closingNotes = '';
        $this->cashboxMessage = $remainingSessions
            ? 'Caja eliminada. Los cobros y productos vendidos se mantienen.'
            : 'Caja eliminada. El dia quedo sin cajas; puedes abrir nuevamente desde 0.';
        $this->confirmingCashboxSessionDeleteId = null;
    }

    public function markTicketPrinted(?int $ticketId = null): void
    {
        if (! $ticketId && isset($this->ticketPreview['ticket_id'])) {
            $ticketId = (int) $this->ticketPreview['ticket_id'];
        }

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

    public function setHistoryTab(string $tab): void
    {
        if (! in_array($tab, ['services', 'products', 'expenses'], true)) {
            return;
        }

        $this->historyTab = $tab;
    }

    public function render()
    {
        $company = $this->company();
        $branch = $this->activeBranch();
        $day = Carbon::parse($this->selectedDate);
        $paymentsQuery = $company->treatmentPayments()
            ->where('branch_id', $branch->id)
            ->whereBetween('paid_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()]);
        $paymentIds = (clone $paymentsQuery)->pluck('id');
        $payments = (clone $paymentsQuery)
            ->with(['client', 'splits', 'items'])
            ->latest('paid_at')
            ->get();

        $cashTotal = (float) TreatmentPaymentSplit::query()
            ->whereIn('treatment_payment_id', $paymentIds)
            ->where('method', 'cash')
            ->sum('amount');
        $qrTotal = (float) TreatmentPaymentSplit::query()
            ->whereIn('treatment_payment_id', $paymentIds)
            ->where('method', 'qr')
            ->sum('amount');
        $cashboxExpenseTotal = (float) $company->expenses()
            ->where('branch_id', $branch->id)
            ->where('source', 'cashbox')
            ->whereDate('spent_at', $day->toDateString())
            ->sum('amount');
        $expenses = $company->expenses()
            ->with(['type', 'staffUser', 'createdBy'])
            ->where('branch_id', $branch->id)
            ->whereDate('spent_at', $day->toDateString())
            ->latest('spent_at')
            ->get();

        $cashboxSession = $this->visibleCashboxSession($company, $branch, $day);
        $printSummary = $this->buildPrintSummary($payments);

        return view('livewire.clinic.quick-cashbox', [
            'branch' => $branch,
            'cashboxSession' => $cashboxSession,
            'payments' => $payments,
            'cashTotal' => $cashTotal,
            'qrTotal' => $qrTotal,
            'cashboxExpenseTotal' => $cashboxExpenseTotal,
            'netCashTotal' => $cashTotal - $cashboxExpenseTotal,
            'netTotal' => $cashTotal + $qrTotal - $cashboxExpenseTotal,
            'invoiceTotal' => (float) $payments->where('invoice_requested', true)->sum('amount'),
            'printSummary' => $printSummary,
            'historyRows' => $this->cashboxHistoryRows($printSummary, $expenses),
            'ticketRows' => $company->cashboxTickets()
                ->with(['printedBy', 'session'])
                ->where('branch_id', $branch->id)
                ->whereIn('type', ['session_close', 'session_detail', 'daily_detail'])
                ->latest()
                ->limit(12)
                ->get(),
        ]);
    }

    private function buildPrintSummary($payments): array
    {
        $summary = [
            'services' => ['rows' => collect(), 'cash' => 0.0, 'qr' => 0.0, 'total' => 0.0],
            'products' => ['rows' => collect(), 'cash' => 0.0, 'qr' => 0.0, 'total' => 0.0],
        ];

        foreach ($payments as $payment) {
            $cashLeft = (float) $payment->splits->where('method', 'cash')->sum('amount');
            $qrLeft = (float) $payment->splits->where('method', 'qr')->sum('amount');
            $items = $payment->items->sortBy(fn($item) => $item->type === 'service' ? 0 : 1);

            foreach ($items as $item) {
                $total = (float) $item->total;
                $cash = min($cashLeft, $total);
                $cashLeft -= $cash;
                $qr = min($qrLeft, $total - $cash);
                $qrLeft -= $qr;
                $group = $item->type === 'product' ? 'products' : 'services';

                $summary[$group]['rows']->push([
                    'time' => $payment->paid_at->format('H:i'),
                    'client' => $payment->client?->full_name ?? 'Cliente',
                    'name' => $item->name,
                    'quantity' => (float) $item->quantity,
                    'total' => $total,
                    'cash' => $cash,
                    'qr' => $qr,
                    'method' => $this->linePaymentMethod($cash, $qr),
                ]);
                $summary[$group]['cash'] += $cash;
                $summary[$group]['qr'] += $qr;
                $summary[$group]['total'] += $total;
            }
        }

        return $summary;
    }

    private function cashboxHistoryRows(array $printSummary, $expenses): array
    {
        $method = $this->paymentMethodFilter;
        $source = $this->expenseSourceFilter;

        return [
            'services' => $printSummary['services']['rows']
                ->filter(fn(array $row) => $method === '' || $row['method'] === $method)
                ->values(),
            'products' => $printSummary['products']['rows']
                ->filter(fn(array $row) => $method === '' || $row['method'] === $method)
                ->values(),
            'expenses' => $expenses
                ->filter(fn($expense) => $source === '' || $expense->source === $source)
                ->values(),
        ];
    }

    private function visibleCashboxSession(Company $company, Branch $branch, Carbon $day): ?CashboxSession
    {
        return $company->cashboxSessions()
            ->with(['openedBy', 'closedBy'])
            ->where('branch_id', $branch->id)
            ->where('status', 'open')
            ->first()
            ?? $company->cashboxSessions()
            ->with(['openedBy', 'closedBy'])
            ->where('branch_id', $branch->id)
            ->whereDate('business_date', $day->toDateString())
            ->latest('shift_number')
            ->first();
    }

    private function createDailyTicket(Company $company, Branch $branch): CashboxTicket
    {
        $day = Carbon::parse($this->selectedDate);
        $payments = $company->treatmentPayments()
            ->with(['client', 'splits', 'items'])
            ->where('branch_id', $branch->id)
            ->whereBetween('paid_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()])
            ->latest('paid_at')
            ->get();

        $payload = $this->ticketPayload(
            title: 'Detalle de caja',
            branch: $branch,
            businessDate: $day,
            summary: $this->buildPrintSummary($payments),
            totals: $this->dailyTotals($company, $branch, $day),
        );

        return $this->storeTicket($company, $branch, null, 'daily_detail', 'Detalle de caja', $payload);
    }

    private function createSessionTicket(CashboxSession $session, string $type, string $title): CashboxTicket
    {
        $session->loadMissing(['company', 'branch', 'openedBy', 'closedBy']);
        $payments = $this->paymentsForSession($session);
        $expenses = $this->expensesForSession($session);
        $totals = $this->sessionTotals($session);
        $payload = $this->ticketPayload(
            title: $title,
            branch: $session->branch,
            businessDate: $session->business_date,
            summary: $this->buildPrintSummary($payments),
            expenses: $expenses,
            totals: [
                ...$totals,
                'opening_amount' => (float) $session->opening_amount,
                'expected_cash_amount' => (float) $session->expected_cash_amount,
                'counted_cash_amount' => (float) $session->counted_cash_amount,
                'cash_difference' => (float) $session->cash_difference,
            ],
            session: $session,
        );

        return $this->storeTicket($session->company, $session->branch, $session, $type, $title, $payload);
    }

    private function ticketForSessionPreview(CashboxSession $session): CashboxTicket
    {
        if ($session->status === 'closed') {
            $existingTicket = $session->tickets()
                ->where('type', 'session_close')
                ->latest()
                ->first();

            if ($existingTicket) {
                $payments = $this->paymentsForSession($session);
                $expenses = $this->expensesForSession($session);
                $totals = $this->sessionTotals($session);

                $payload = $this->ticketPayload(
                    title: 'Cierre de caja',
                    branch: $session->branch,
                    businessDate: $session->business_date,
                    summary: $this->buildPrintSummary($payments),
                    expenses: $expenses,
                    totals: [
                        ...$totals,
                        'opening_amount' => (float) $session->opening_amount,
                        'expected_cash_amount' => (float) $session->expected_cash_amount,
                        'counted_cash_amount' => (float) $session->counted_cash_amount,
                        'cash_difference' => (float) $session->cash_difference,
                    ],
                    session: $session,
                );

                $payload['ticket_id'] = $existingTicket->id;
                $payload['ticket_number'] = $existingTicket->ticket_number;

                $existingTicket->update([
                    'payload' => $payload,
                ]);

                $existingTicket->increment('reprint_count');

                return $existingTicket->refresh();
            }
        }

        return $this->createSessionTicket(
            $session,
            $session->status === 'closed'
                ? 'session_close'
                : 'session_detail',
            $session->status === 'closed'
                ? 'Cierre de caja'
                : 'Detalle de caja',
        );
    }

    private function storeTicket(Company $company, Branch $branch, ?CashboxSession $session, string $type, string $title, array $payload): CashboxTicket
    {
        $ticket = CashboxTicket::query()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'cashbox_session_id' => $session?->id,
            'type' => $type,
            'ticket_number' => 'CB-' . $company->id . '-' . $branch->id . '-' . now()->format('YmdHis') . '-' . random_int(100, 999),
            'title' => $title,
            'payload' => $payload,
            'status' => 'generated',
        ]);

        $payload['ticket_id'] = $ticket->id;
        $payload['ticket_number'] = $ticket->ticket_number;
        $ticket->update(['payload' => $payload]);

        return $ticket->refresh();
    }

    private function ticketPayload(
        string $title,
        Branch $branch,
        Carbon $businessDate,
        array $summary,
        array $totals,
        ?CashboxSession $session = null,
        $expenses = null
    ): array {
        /*
    |--------------------------------------------------------------------------
    | Convertir todo a arrays simples
    |--------------------------------------------------------------------------
    */

        $services = $summary['services']['rows']
            ->values()
            ->all();

        $products = $summary['products']['rows']
            ->values()
            ->all();

        $expenseRows = $expenses
            ? $expenses
            ->map(fn($expense) => [
                'name' => $expense->type?->name ?? 'Gasto',
                'amount' => (float) $expense->amount,
                'responsible' => $expense->createdBy?->name ?? 'Sin responsable',
                'reference' => $expense->reference,
            ])
            ->values()
            ->all()
            : [];

        /*
    |--------------------------------------------------------------------------
    | Crear texto listo para impresora
    |--------------------------------------------------------------------------
    */

        $rawTicket = $this->buildRawTicket(
            title: $title,
            branch: $branch,
            businessDate: $businessDate,
            services: $services,
            products: $products,
            totals: $totals,
            expenses: $expenseRows,
            session: $session
        );

        return [
            'title' => $title,

            'branch' => $branch->name,

            'business_type' => $branch->businessType?->name,

            'business_date' => $businessDate->format('d/m/Y'),

            'shift_number' => $session?->shift_number,

            'status' => $session?->status,

            'opened_by' => $session?->openedBy?->name,

            'closed_by' => $session?->closedBy?->name,

            'opened_at' => $session?->opened_at?->format('d/m/Y H:i'),

            'closed_at' => $session?->closed_at?->format('d/m/Y H:i'),

            'printer_enabled' => (bool) $branch->uses_ticket_printer,

            'printer_name' => $branch->printer_name,

            'printer_bridge_url' => $branch->printer_bridge_url,

            'services' => $services,

            'products' => $products,

            'expenses' => $expenseRows,

            'totals' => $totals,

            /*
        |--------------------------------------------------------------------------
        | Texto final para QZ
        |--------------------------------------------------------------------------
        */

            'raw_ticket' => $rawTicket,

            'created_at' => now()->format('d/m/Y H:i'),
        ];
    }

    private function dailyTotals(Company $company, Branch $branch, Carbon $day): array
    {
        $paymentIds = $company->treatmentPayments()
            ->where('branch_id', $branch->id)
            ->whereBetween('paid_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()])
            ->pluck('id');

        $cash = (float) TreatmentPaymentSplit::query()
            ->whereIn('treatment_payment_id', $paymentIds)
            ->where('method', 'cash')
            ->sum('amount');
        $qr = (float) TreatmentPaymentSplit::query()
            ->whereIn('treatment_payment_id', $paymentIds)
            ->where('method', 'qr')
            ->sum('amount');
        $expenses = (float) $company->expenses()
            ->where('branch_id', $branch->id)
            ->where('source', 'cashbox')
            ->whereDate('spent_at', $day->toDateString())
            ->sum('amount');

        return [
            'cash' => $cash,
            'qr' => $qr,
            'expenses' => $expenses,
            'net_cash' => $cash - $expenses,
            'net_total' => $cash + $qr - $expenses,
        ];
    }

    private function sessionTotals(CashboxSession $session): array
    {
        $payments = $this->paymentsForSession($session);
        $paymentIds = $payments->pluck('id');
        $cash = (float) TreatmentPaymentSplit::query()
            ->whereIn('treatment_payment_id', $paymentIds)
            ->where('method', 'cash')
            ->sum('amount');
        $qr = (float) TreatmentPaymentSplit::query()
            ->whereIn('treatment_payment_id', $paymentIds)
            ->where('method', 'qr')
            ->sum('amount');
        $expenses = (float) $this->expensesForSession($session)->sum('amount');

        return [
            'cash' => $cash,
            'qr' => $qr,
            'expenses' => $expenses,
            'net_cash' => $cash - $expenses,
            'net_total' => $cash + $qr - $expenses,
        ];
    }

    private function paymentsForSession(CashboxSession $session)
    {
        $end = $session->closed_at ?? now();
        $start = $this->sessionStartAt($session);

        return $session->company->treatmentPayments()
            ->with(['client', 'splits', 'items'])
            ->where('branch_id', $session->branch_id)
            ->whereBetween('paid_at', [$start, $end])
            ->latest('paid_at')
            ->get();
    }

    private function expensesForSession(CashboxSession $session)
    {
        $end = $session->closed_at ?? now();
        $start = $this->sessionStartAt($session);

        return $session->company->expenses()
            ->with(['type', 'createdBy'])
            ->where('branch_id', $session->branch_id)
            ->where('source', 'cashbox')
            ->whereBetween('spent_at', [$start, $end])
            ->latest('spent_at')
            ->get();
    }

    private function sessionStartAt(CashboxSession $session): Carbon
    {
        if ((int) $session->shift_number <= 1) {
            return $session->business_date->copy()->startOfDay();
        }

        return $session->opened_at ?? $session->business_date->copy()->startOfDay();
    }

    private function decimal(string $value): float
    {
        return (float) str_replace(',', '.', $value);
    }

    private function linePaymentMethod(float $cash, float $qr): string
    {
        return $cash > 0 && $qr > 0 ? 'mixed' : ($qr > 0 ? 'qr' : 'cash');
    }

    public function paymentMethodLabel(string $method): string
    {
        return match ($method) {
            'cash' => 'Efectivo',
            'qr' => 'QR',
            'mixed' => 'Mixto',
            default => 'Otro',
        };
    }

    public function expenseSourceLabel(string $source): string
    {
        return match ($source) {
            'cashbox' => 'Gasto de caja',
            'external' => 'Gasto externo',
            default => 'Gasto',
        };
    }

    public function canManageCashboxClosures(): bool
    {
        $user = Auth::user();
        $company = $this->company();
        $branch = $this->activeBranch();
        $companyRole = $user->companies()
            ->where('companies.id', $company->id)
            ->value('company_user.role');
        $branchRole = $user->branches()
            ->where('branches.id', $branch->id)
            ->leftJoin('roles', 'roles.id', '=', 'branch_user.role_id')
            ->select(['roles.slug', 'roles.name'])
            ->first();
        $roleName = Str::lower(Str::ascii($branchRole?->name ?? ''));

        return in_array($companyRole, ['owner', 'super_admin', 'super-administrador', 'admin', 'administrador'], true)
            || in_array($branchRole?->slug, ['owner', 'super_admin', 'super-administrador', 'admin', 'administrador'], true)
            || str_contains($roleName, 'administrador');
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

    private function buildRawTicket(
        string $title,
        Branch $branch,
        Carbon $businessDate,
        array $services = [],
        array $products = [],
        array $totals = [],
        array $expenses = [],
        ?CashboxSession $session = null
    ): string {
        $width = 42;
        $descriptionWidth = 24;
        $amountWidth = 18;

        $clean = function ($value): string {
            $value = Str::ascii((string) $value);
            $value = preg_replace('/\s+/', ' ', $value);

            return trim($value);
        };

        $money = function ($value): string {
            return number_format(
                (float) $value,
                2,
                '.',
                ''
            );
        };

        $center = function ($value) use ($width, $clean): string {
            $text = $clean($value);

            if (strlen($text) > $width) {
                $text = substr($text, 0, $width);
            }

            return str_pad(
                $text,
                $width,
                ' ',
                STR_PAD_BOTH
            );
        };

        $firstTwoNames = function ($value) use ($clean): string {
            $parts = array_values(
                array_filter(
                    explode(' ', $clean($value))
                )
            );

            return implode(
                ' ',
                array_slice($parts, 0, 2)
            );
        };

        $row = function ($name, $value) use (
            $clean,
            $descriptionWidth,
            $amountWidth
        ): string {
            $name = $clean($name);
            $value = $clean($value);

            if (strlen($name) > $descriptionWidth) {
                $name = substr(
                    $name,
                    0,
                    $descriptionWidth
                );
            }

            if (strlen($value) > $amountWidth) {
                $value = substr(
                    $value,
                    0,
                    $amountWidth
                );
            }

            return str_pad(
                $name,
                $descriptionWidth,
                ' ',
                STR_PAD_RIGHT
            )
                . str_pad(
                    $value,
                    $amountWidth,
                    ' ',
                    STR_PAD_LEFT
                );
        };

        $paymentText = function ($cash, $qr) use ($money): string {
            $cash = (float) $cash;
            $qr = (float) $qr;

            if ($cash > 0 && $qr > 0) {
                return 'EF '
                    . $money($cash)
                    . ' QR '
                    . $money($qr);
            }

            if ($qr > 0) {
                return 'QR ' . $money($qr);
            }

            if ($cash > 0) {
                return 'EF ' . $money($cash);
            }

            return '0.00';
        };

        $lines = [];

        $lines[] = $center($branch->name);
        $lines[] = $center($title);
        $lines[] = str_repeat('-', $width);

        $lines[] = 'FECHA: '
            . $businessDate->format('d/m/Y');

        if ($session?->shift_number) {
            $lines[] = 'CIERRE NRO: '
                . $session->shift_number;
        }

        if ($session?->openedBy?->name) {
            $lines[] = 'APERTURA: '
                . $firstTwoNames(
                    $session->openedBy->name
                );
        }

        if ($session?->opened_at) {
            $lines[] = 'HORA APERTURA: '
                . $session->opened_at->format('H:i');
        }

        if ($session?->closedBy?->name) {
            $lines[] = 'CIERRE: '
                . $firstTwoNames(
                    $session->closedBy->name
                );
        }

        if ($session?->closed_at) {
            $lines[] = 'HORA CIERRE: '
                . $session->closed_at->format('H:i');
        }

        $lines[] = str_repeat('-', $width);

        $lines[] = 'SERVICIOS';
        $lines[] = str_repeat('-', $width);

        foreach ($services as $service) {
            $cash = (float) (
                $service['cash'] ?? 0
            );

            $qr = (float) (
                $service['qr'] ?? 0
            );

            $name = $firstTwoNames(
                $service['client'] ?? 'Cliente'
            );

            $lines[] = $row(
                $name,
                $paymentText(
                    $cash,
                    $qr
                )
            );
        }

        if (empty($services)) {
            $lines[] = 'Sin servicios';
        }

        if (! empty($products)) {
            $lines[] = '';
            $lines[] = 'PRODUCTOS';
            $lines[] = str_repeat('-', $width);

            foreach ($products as $product) {
                $cash = (float) (
                    $product['cash'] ?? 0
                );

                $qr = (float) (
                    $product['qr'] ?? 0
                );

                $name = $clean(
                    $product['name']
                        ?? 'Producto'
                );

                if (
                    isset($product['quantity'])
                    && (float) $product['quantity'] > 1
                ) {
                    $name .= ' x'
                        . number_format(
                            (float) $product['quantity'],
                            0
                        );
                }

                $lines[] = $row(
                    $name,
                    $paymentText(
                        $cash,
                        $qr
                    )
                );
            }
        }

        $lines[] = '';
        $lines[] = 'GASTOS';
        $lines[] = str_repeat('-', $width);

        if (! empty($expenses)) {
            foreach ($expenses as $expense) {
                $lines[] = $row(
                    $expense['name'] ?? 'Gasto',
                    $money(
                        $expense['amount'] ?? 0
                    )
                );
            }
        } else {
            $lines[] = $row(
                'SIN GASTOS',
                '0.00'
            );
        }

        $lines[] = str_repeat('-', $width);

        $lines[] = $row(
            'TOTAL GASTOS',
            $money(
                $totals['expenses'] ?? 0
            )
        );

        $lines[] = '';
        $lines[] = 'RESUMEN DE CAJA';
        $lines[] = str_repeat('-', $width);

        $cash = (float) (
            $totals['cash'] ?? 0
        );

        $qr = (float) (
            $totals['qr'] ?? 0
        );

        $expensesTotal = (float) (
            $totals['expenses'] ?? 0
        );

        $openingAmount = (float) (
            $totals['opening_amount'] ?? 0
        );

        $totalIngresos =
            $cash
            + $qr;

        $totalGeneral =
            $totalIngresos
            - $expensesTotal;

        $totalCaja =
            $openingAmount
            + $cash
            - $expensesTotal;

        if (
            isset($totals['opening_amount'])
            && $openingAmount > 0
        ) {
            $lines[] = $row(
                'MONTO INICIAL',
                $money($openingAmount)
            );
        }

        $lines[] = $row(
            'TOTAL EFECTIVO',
            $money($cash)
        );

        $lines[] = $row(
            'TOTAL QR',
            $money($qr)
        );

        $lines[] = $row(
            'TOTAL INGRESOS',
            $money($totalIngresos)
        );

        $lines[] = $row(
            'TOTAL GASTOS',
            $money($expensesTotal)
        );

        $lines[] = str_repeat('-', $width);

        $lines[] = $row(
            'TOTAL GENERAL',
            $money($totalGeneral)
        );

        $lines[] = $row(
            'TOTAL EN CAJA',
            $money($totalCaja)
        );

        if (
            $session?->status === 'closed'
            && isset($totals['counted_cash_amount'])
        ) {
            $lines[] = str_repeat('-', $width);

            $lines[] = $row(
                'CONTADO',
                $money(
                    $totals['counted_cash_amount']
                )
            );
        }

        if (
            $session?->status === 'closed'
            && isset($totals['cash_difference'])
        ) {
            $lines[] = $row(
                'DIFERENCIA',
                $money(
                    $totals['cash_difference']
                )
            );
        }

        $lines[] = str_repeat('-', $width);
        $lines[] = '';
        $lines[] = $center('Sistema Rumika SaaS');

        return implode("\n", $lines);
    }
    public function imprimirResultado(): void
    {
        $branch = $this->activeBranch();

        $texto = (string) ($this->ticketPreview['raw_ticket'] ?? '');

        if ($texto === '') {
            $this->cashboxMessage = 'No existe un ticket preparado para imprimir.';

            return;
        }

        if (! $branch->printer_name) {
            $this->cashboxMessage = 'No hay una impresora configurada para esta sucursal.';

            return;
        }

        if (! empty($this->ticketPreview['ticket_id'])) {
            $this->company()
                ->cashboxTickets()
                ->where('branch_id', $branch->id)
                ->whereKey((int) $this->ticketPreview['ticket_id'])
                ->update([
                    'printed_by_user_id' => Auth::id(),
                    'printed_at' => now(),
                    'status' => 'printed',
                ]);
        }

        $this->dispatch(
            'imprimir-ticket-caja',
            texto: $texto,
            impresora: $branch->printer_name
        );
    }
}
