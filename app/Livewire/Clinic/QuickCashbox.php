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

        /*
    |--------------------------------------------------------------------------
    | Compatibilidad con tickets anteriores
    |--------------------------------------------------------------------------
    |
    | Si el ticket fue creado antes de implementar raw_ticket,
    | lo generamos nuevamente desde el payload guardado.
    |
    */

        if (empty($payload['raw_ticket'])) {
            $branch = $this->activeBranch();

            $businessDate = ! empty($payload['business_date'])
                ? Carbon::createFromFormat('d/m/Y', $payload['business_date'])
                : Carbon::parse($ticket->created_at);

            $payload['raw_ticket'] = $this->buildRawTicket(
                title: $payload['title'] ?? $ticket->title ?? 'Detalle de caja',
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
        }

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
                $existingTicket->increment('reprint_count');

                return $existingTicket->refresh();
            }
        }

        return $this->createSessionTicket(
            $session,
            $session->status === 'closed' ? 'session_close' : 'session_detail',
            $session->status === 'closed' ? 'Cierre de caja' : 'Detalle de caja',
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
            ->whereBetween('created_at', [$start, $end])
            ->latest('created_at')
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
        /*
    |--------------------------------------------------------------------------
    | ANCHO
    |--------------------------------------------------------------------------
    |
    | 42 caracteres funciona bien normalmente para Epson 80mm
    |
    */

        $width = 42;


        /*
    |--------------------------------------------------------------------------
    | LIMPIAR TEXTO
    |--------------------------------------------------------------------------
    */

        $clean = function ($value): string {
            $value = Str::ascii((string) $value);

            $value = preg_replace(
                '/\s+/',
                ' ',
                $value
            );

            return trim($value);
        };


        /*
    |--------------------------------------------------------------------------
    | DINERO
    |--------------------------------------------------------------------------
    */

        $money = function ($value): string {
            return number_format(
                (float) $value,
                2,
                '.',
                ''
            );
        };


        /*
    |--------------------------------------------------------------------------
    | CENTRAR
    |--------------------------------------------------------------------------
    */

        $center = function ($value) use (
            $width,
            $clean
        ): string {
            $text = $clean($value);

            if (strlen($text) > $width) {
                $text = substr(
                    $text,
                    0,
                    $width
                );
            }

            $spaces = max(
                0,
                (int) floor(
                    ($width - strlen($text)) / 2
                )
            );

            return str_repeat(
                ' ',
                $spaces
            ) . $text;
        };


        /*
    |--------------------------------------------------------------------------
    | PRIMEROS DOS NOMBRES
    |--------------------------------------------------------------------------
    */

        $firstTwoNames = function ($value) use (
            $clean
        ): string {
            $parts = array_values(
                array_filter(
                    explode(
                        ' ',
                        $clean($value)
                    )
                )
            );

            return implode(
                ' ',
                array_slice(
                    $parts,
                    0,
                    2
                )
            );
        };


        /*
    |--------------------------------------------------------------------------
    | FILA DE PAGO COMPACTA
    |--------------------------------------------------------------------------
    |
    | Ejemplos:
    |
    | MARIA FERNANDA               E 150.00
    | JUAN CARLOS                  Q 200.00
    | ANA PAOLA              E 50.00 Q 100.00
    |
    */

        $paymentRow = function (
            $name,
            $cash,
            $qr
        ) use (
            $width,
            $clean,
            $money
        ): string {

            $name = $clean($name);

            $cash = (float) $cash;
            $qr = (float) $qr;


            /*
         * Solo efectivo
         */

            if ($cash > 0 && $qr <= 0) {
                $payment = 'E ' . $money($cash);
            }

            /*
         * Solo QR
         */ elseif ($qr > 0 && $cash <= 0) {
                $payment = 'Q ' . $money($qr);
            }

            /*
         * Mixto
         */ elseif ($cash > 0 && $qr > 0) {
                $payment =
                    'E' . $money($cash)
                    . ' Q' . $money($qr);
            } else {
                $payment = '0.00';
            }


            /*
         * Espacio disponible para nombre
         */

            $maxName = max(
                1,
                $width
                    - strlen($payment)
                    - 1
            );


            if (strlen($name) > $maxName) {
                $name = substr(
                    $name,
                    0,
                    $maxName
                );
            }


            $spaces = max(
                1,
                $width
                    - strlen($name)
                    - strlen($payment)
            );


            return $name
                . str_repeat(
                    ' ',
                    $spaces
                )
                . $payment;
        };


        /*
    |--------------------------------------------------------------------------
    | FILA TOTAL
    |--------------------------------------------------------------------------
    */

        $totalRow = function (
            $label,
            $amount
        ) use (
            $width,
            $clean,
            $money
        ): string {

            $label = $clean($label);

            $amount = $money($amount);


            $spaces = max(
                1,
                $width
                    - strlen($label)
                    - strlen($amount)
            );


            return $label
                . str_repeat(
                    ' ',
                    $spaces
                )
                . $amount;
        };


        /*
    |--------------------------------------------------------------------------
    | RESUMEN DE SECCION
    |--------------------------------------------------------------------------
    |
    | Ef 100.00 QR 200.00 TOT 300.00
    |
    */

        $sectionSummary = function (
            $cash,
            $qr,
            $total
        ) use ($money): string {

            return 'Ef ' . $money($cash)
                . ' QR ' . $money($qr)
                . ' T ' . $money($total);
        };


        /*
    |--------------------------------------------------------------------------
    | COMENZAR TICKET
    |--------------------------------------------------------------------------
    */

        $lines = [];


        /*
    |--------------------------------------------------------------------------
    | CABECERA
    |--------------------------------------------------------------------------
    */

        $lines[] = $center(
            $branch->name
        );

        $lines[] = $center(
            $title
        );


        /*
     * Fecha y turno en la misma zona para ahorrar papel.
     */

        $dateLine =
            $businessDate->format('d/m/Y');


        if ($session?->shift_number) {
            $dateLine .=
                ' - Turno '
                . $session->shift_number;
        }


        $lines[] = $center(
            $dateLine
        );


        /*
     * Horarios compactos
     */

        if ($session?->opened_at) {

            $timeLine =
                'Desde '
                . $session->opened_at
                ->format('H:i');


            if ($session?->closed_at) {

                $timeLine .=
                    ' Hasta '
                    . $session->closed_at
                    ->format('H:i');
            }


            $lines[] = $center(
                $timeLine
            );
        }


        /*
     * Persona responsable.
     *
     * Solo primeros nombres para evitar líneas largas.
     */

        if ($session?->openedBy?->name) {

            $lines[] =
                'Caja: '
                . $firstTwoNames(
                    $session->openedBy->name
                );
        }


        $lines[] = str_repeat(
            '-',
            $width
        );


        /*
    |--------------------------------------------------------------------------
    | SERVICIOS
    |--------------------------------------------------------------------------
    */

        $lines[] = 'SERVICIOS';


        $serviceCash = 0;
        $serviceQr = 0;
        $serviceTotal = 0;


        foreach ($services as $row) {

            $cash = (float) (
                $row['cash'] ?? 0
            );

            $qr = (float) (
                $row['qr'] ?? 0
            );

            $total = (float) (
                $row['total']
                ?? ($cash + $qr)
            );


            $serviceCash += $cash;
            $serviceQr += $qr;
            $serviceTotal += $total;


            /*
         * Solo primeros dos nombres
         */

            $patient =
                $firstTwoNames(
                    $row['client']
                        ?? 'Cliente'
                );


            $lines[] = $paymentRow(
                $patient,
                $cash,
                $qr
            );
        }


        if (empty($services)) {
            $lines[] = 'Sin servicios';
        }


        $lines[] = str_repeat(
            '-',
            $width
        );


        /*
     * AQUÍ ESTÁ EL TOTAL QUE TE FALTABA.
     */

        $lines[] = $sectionSummary(
            $serviceCash,
            $serviceQr,
            $serviceTotal
        );


        /*
    |--------------------------------------------------------------------------
    | PRODUCTOS
    |--------------------------------------------------------------------------
    */

        if (! empty($products)) {

            $lines[] = '';
            $lines[] = 'PRODUCTOS';


            $productCash = 0;
            $productQr = 0;
            $productTotal = 0;


            foreach ($products as $row) {

                $cash = (float) (
                    $row['cash'] ?? 0
                );

                $qr = (float) (
                    $row['qr'] ?? 0
                );

                $total = (float) (
                    $row['total']
                    ?? ($cash + $qr)
                );


                $productCash += $cash;
                $productQr += $qr;
                $productTotal += $total;


                $productName = $clean(
                    $row['name']
                        ?? 'Producto'
                );


                /*
             * Cantidad solo si hay más de uno.
             */

                if (
                    isset($row['quantity'])
                    && (float) $row['quantity'] > 1
                ) {

                    $quantity =
                        (float) $row['quantity'];


                    $productName .=
                        ' x'
                        . (
                            floor($quantity)
                            == $quantity
                            ? (int) $quantity
                            : number_format(
                                $quantity,
                                2,
                                '.',
                                ''
                            )
                        );
                }


                $lines[] = $paymentRow(
                    $productName,
                    $cash,
                    $qr
                );
            }


            $lines[] = str_repeat(
                '-',
                $width
            );


            /*
         * TOTAL PRODUCTOS
         */

            $lines[] = $sectionSummary(
                $productCash,
                $productQr,
                $productTotal
            );
        }


        /*
    |--------------------------------------------------------------------------
    | GASTOS
    |--------------------------------------------------------------------------
    */

        if (! empty($expenses)) {

            $lines[] = '';
            $lines[] = 'GASTOS';


            $expenseTotal = 0;


            foreach ($expenses as $expense) {

                $amount = (float) (
                    $expense['amount']
                    ?? 0
                );


                $expenseTotal += $amount;


                $lines[] = $totalRow(
                    $expense['name']
                        ?? 'Gasto',
                    $amount
                );
            }


            $lines[] = str_repeat(
                '-',
                $width
            );


            $lines[] = $totalRow(
                'Total gastos',
                $expenseTotal
            );
        }


        /*
    |--------------------------------------------------------------------------
    | TOTALES GENERALES
    |--------------------------------------------------------------------------
    */

        $lines[] = '';
        $lines[] = 'TOTAL CAJA';
        $lines[] = str_repeat(
            '-',
            $width
        );


        if (
            array_key_exists(
                'opening_amount',
                $totals
            )
        ) {

            $lines[] = $totalRow(
                'Inicial',
                $totals['opening_amount']
            );
        }


        $lines[] = $totalRow(
            'Efectivo',
            $totals['cash'] ?? 0
        );


        $lines[] = $totalRow(
            'QR',
            $totals['qr'] ?? 0
        );


        if (
            (float) (
                $totals['expenses']
                ?? 0
            ) > 0
        ) {

            $lines[] = $totalRow(
                'Gastos',
                $totals['expenses']
            );
        }


        /*
     * TOTAL DE VENTAS
     */

        $grossTotal =
            (float) (
                $totals['cash'] ?? 0
            )
            +
            (float) (
                $totals['qr'] ?? 0
            );


        $lines[] = $totalRow(
            'TOTAL',
            $grossTotal
        );


        /*
     * Caja física:
     * efectivo menos gastos.
     */

        $lines[] = $totalRow(
            'Caja neta',
            $totals['net_cash'] ?? 0
        );


        /*
     * Datos de cierre únicamente si corresponde.
     */

        if (
            array_key_exists(
                'counted_cash_amount',
                $totals
            )
        ) {

            $lines[] = $totalRow(
                'Contado',
                $totals['counted_cash_amount']
            );
        }


        if (
            array_key_exists(
                'cash_difference',
                $totals
            )
            &&
            abs(
                (float) $totals['cash_difference']
            ) > 0.001
        ) {

            $lines[] = $totalRow(
                'Diferencia',
                $totals['cash_difference']
            );
        }


        /*
    |--------------------------------------------------------------------------
    | PIE
    |--------------------------------------------------------------------------
    */

        $lines[] = str_repeat(
            '-',
            $width
        );

        $lines[] = $center(
            'Rumika'
        );


        /*
     * Solamente 2 líneas vacías.
     *
     * Antes teníamos 4.
     * Ahorramos papel.
     */

        $lines[] = '';
        $lines[] = '';


        return implode(
            "\n",
            $lines
        );
    }
}
