<?php

namespace App\Livewire\Clinic;

use App\Models\Branch;
use App\Models\CashboxTicket;
use App\Models\ClientCharge;
use App\Models\Company;
use App\Models\Expense;
use App\Models\InventoryMovement;
use App\Models\InventoryProductBatch;
use App\Models\ProductSale;
use App\Models\TreatmentPayment;
use App\Support\PaymentTicketBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class CashboxSummary extends Component
{
    public string $selectedDate = '';
    public string $context = 'cashbox';
    public string $historyTab = 'services';
    public string $paymentMethodFilter = '';
    public string $expenseSourceFilter = '';
    public string $clientSearch = '';
    public ?int $confirmingPaymentDeleteId = null;
    public ?int $confirmingProductSaleDeleteId = null;
    public ?int $confirmingExpenseDeleteId = null;
    public bool $showExpenseModal = false;
    public ?int $editingExpenseId = null;
    public ?int $expenseTypeId = null;
    public ?int $staffUserId = null;
    public string $expenseSource = 'cashbox';
    public string $expenseAmount = '';
    public string $expenseSpentAt = '';
    public string $expenseReference = '';
    public string $expenseDescription = '';
    public bool $showTicketPreview = false;
    public array $ticketPreview = [];

    public function mount(string $context = 'cashbox'): void
    {
        $this->context = $context;
        $this->selectedDate = now()->format('Y-m-d');
    }

    public function setHistoryTab(string $tab): void
    {
        if (! in_array($tab, ['services', 'products', 'expenses'], true)) {
            return;
        }

        $this->historyTab = $tab;
    }

    public function editExpense(int $expenseId): void
    {
        $this->authorizeSuperAdmin();
        $expense = $this->company()->expenses()->whereKey($expenseId)->firstOrFail();

        $this->editingExpenseId = $expense->id;
        $this->expenseTypeId = $expense->expense_type_id;
        $this->staffUserId = $expense->staff_user_id;
        $this->expenseSource = $expense->source;
        $this->expenseAmount = (string) $expense->amount;
        $this->expenseSpentAt = $expense->spent_at->format('Y-m-d');
        $this->expenseReference = $expense->reference ?? '';
        $this->expenseDescription = $expense->description ?? '';
        $this->showExpenseModal = true;
    }

    public function updatedExpenseTypeId(): void
    {
        $type = $this->company()->expenseTypes()->whereKey($this->expenseTypeId)->first();

        if (! $type) {
            return;
        }

        $this->expenseSource = $type->default_source;
        if (! $type->requires_staff) {
            $this->staffUserId = null;
        }
    }

    public function saveExpense(): void
    {
        $this->authorizeSuperAdmin();
        $company = $this->company();
        $typeIds = $company->expenseTypes()->pluck('id')->all();
        $staffIds = $company->users()->pluck('users.id')->all();

        $validated = $this->validate([
            'expenseTypeId' => ['required', Rule::in($typeIds)],
            'staffUserId' => ['nullable', Rule::in($staffIds)],
            'expenseSource' => ['required', 'in:cashbox,external'],
            'expenseAmount' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'expenseSpentAt' => ['required', 'date'],
            'expenseReference' => ['nullable', 'string', 'max:120'],
            'expenseDescription' => ['nullable', 'string', 'max:500'],
        ]);
        $type = $company->expenseTypes()->whereKey($validated['expenseTypeId'])->firstOrFail();

        if ($type->requires_staff && ! $validated['staffUserId']) {
            $this->addError('staffUserId', 'Este tipo de gasto debe asignarse a un personal.');

            return;
        }

        $expense = $company->expenses()->whereKey($this->editingExpenseId)->firstOrFail();
        $expense->fill([
            'expense_type_id' => $validated['expenseTypeId'],
            'staff_user_id' => $type->requires_staff ? $validated['staffUserId'] : null,
            'source' => $validated['expenseSource'],
            'amount' => $validated['expenseAmount'],
            'spent_at' => $validated['expenseSpentAt'],
            'reference' => $validated['expenseReference'] ?: null,
            'description' => $validated['expenseDescription'] ?: null,
        ]);
        $expense->save();

        $this->showExpenseModal = false;
        $this->resetExpenseForm();
    }

    public function closeExpenseModal(): void
    {
        $this->showExpenseModal = false;
        $this->resetExpenseForm();
    }

    public function confirmDeletePayment(int $paymentId): void
    {
        $this->authorizeSuperAdmin();
        $this->confirmingPaymentDeleteId = $paymentId;
    }

    public function cancelDeletePayment(): void
    {
        $this->confirmingPaymentDeleteId = null;
    }

    public function deletePayment(?int $paymentId = null): void
    {
        $paymentId ??= $this->confirmingPaymentDeleteId;

        if (! $paymentId) {
            return;
        }

        $this->authorizeSuperAdmin();
        $company = $this->company();
        $payment = $company->treatmentPayments()->with(['items', 'chargePayments.charge'])->whereKey($paymentId)->firstOrFail();

        DB::transaction(function () use ($company, $payment) {
            $this->reversePaymentProductStock($payment);
            $this->reversePaymentClientCharges($payment);
            InventoryMovement::query()
                ->where('company_id', $company->id)
                ->where('reference', 'PAY-'.$payment->id)
                ->delete();
            $payment->splits()->delete();
            $payment->items()->delete();
            $payment->delete();
        });

        $this->confirmingPaymentDeleteId = null;
    }

    public function confirmDeleteProductSale(int $saleId): void
    {
        $this->authorizeSuperAdmin();
        $this->confirmingProductSaleDeleteId = $saleId;
    }

    public function cancelDeleteProductSale(): void
    {
        $this->confirmingProductSaleDeleteId = null;
    }

    public function deleteProductSale(?int $saleId = null): void
    {
        $saleId ??= $this->confirmingProductSaleDeleteId;

        if (! $saleId) {
            return;
        }

        $this->authorizeSuperAdmin();
        $company = $this->company();
        $sale = $company->productSales()
            ->with('items')
            ->where('branch_id', $this->activeBranch()->id)
            ->whereKey($saleId)
            ->firstOrFail();

        DB::transaction(function () use ($company, $sale) {
            foreach ($sale->items as $item) {
                if ($item->inventory_product_batch_id && (float) $item->stock_quantity > 0) {
                    InventoryProductBatch::query()
                        ->whereKey($item->inventory_product_batch_id)
                        ->increment('current_quantity', (float) $item->stock_quantity);
                }
            }

            InventoryMovement::query()
                ->where('company_id', $company->id)
                ->where('reference', 'SALE-'.$sale->id)
                ->delete();

            $sale->items()->delete();
            $sale->delete();
        });

        $this->confirmingProductSaleDeleteId = null;
    }

    public function confirmDeleteExpense(int $expenseId): void
    {
        $this->authorizeSuperAdmin();
        $this->confirmingExpenseDeleteId = $expenseId;
    }

    public function cancelDeleteExpense(): void
    {
        $this->confirmingExpenseDeleteId = null;
    }

    public function deleteExpense(?int $expenseId = null): void
    {
        $expenseId ??= $this->confirmingExpenseDeleteId;

        if (! $expenseId) {
            return;
        }

        $this->authorizeSuperAdmin();
        $this->company()->expenses()->whereKey($expenseId)->firstOrFail()->delete();
        $this->confirmingExpenseDeleteId = null;
    }

    public function previewPaymentTicket(int $paymentId): void
    {
        $company = $this->company();
        $branch = $this->activeBranch();
        $payment = $company->treatmentPayments()
            ->with(['client', 'splits', 'items', 'chargePayments.charge', 'performedBy', 'receivedBy'])
            ->where('branch_id', $branch->id)
            ->whereKey($paymentId)
            ->firstOrFail();
        $payload = PaymentTicketBuilder::payload($payment, $branch);
        $ticket = CashboxTicket::query()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'treatment_payment_id' => $payment->id,
            'type' => 'payment',
            'ticket_number' => 'PAY-'.$company->id.'-'.$branch->id.'-'.now()->format('YmdHis').'-'.random_int(100, 999),
            'title' => 'Ticket de cobro',
            'payload' => $payload,
            'status' => 'generated',
        ]);
        $payload['ticket_id'] = $ticket->id;
        $payload['ticket_number'] = $ticket->ticket_number;
        $ticket->update(['payload' => $payload]);

        $this->ticketPreview = $payload;
        $this->showTicketPreview = true;
    }

    public function closeTicketPreview(): void
    {
        $this->showTicketPreview = false;
        $this->ticketPreview = [];
    }

    public function markTicketPrinted(): void
    {
        $ticketId = $this->ticketPreview['ticket_id'] ?? null;

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

    public function render()
    {
        $company = $this->company();
        $branch = $this->activeBranch();
        $day = Carbon::parse($this->selectedDate);
        $dayRange = [$day->copy()->startOfDay(), $day->copy()->endOfDay()];
        $clientSearch = trim($this->clientSearch);
        $payments = $company->treatmentPayments()
            ->with(['client', 'appointment.services', 'treatmentPlan', 'splits', 'items', 'performedBy'])
            ->where('branch_id', $branch->id)
            ->whereBetween('paid_at', $dayRange)
            ->when($clientSearch !== '', fn ($query) => $query->whereHas('client', fn ($clientQuery) => $clientQuery
                ->where('full_name', 'like', "%{$clientSearch}%")
                ->orWhere('identity_number', 'like', "%{$clientSearch}%")
                ->orWhere('phone', 'like', "%{$clientSearch}%")))
            ->latest('paid_at')
            ->get();
        $productSales = $company->productSales()
            ->with(['buyer', 'soldBy', 'items'])
            ->where('branch_id', $branch->id)
            ->whereBetween('sold_at', $dayRange)
            ->when($clientSearch !== '', fn ($query) => $query
                ->where('buyer_name', 'like', "%{$clientSearch}%")
                ->orWhere('buyer_nit', 'like', "%{$clientSearch}%")
                ->orWhere('buyer_phone', 'like', "%{$clientSearch}%"))
            ->latest('sold_at')
            ->get();
        $expenses = $company->expenses()
            ->with(['type', 'staffUser', 'createdBy'])
            ->where('branch_id', $branch->id)
            ->whereBetween('spent_at', $dayRange)
            ->latest('spent_at')
            ->get();
        $cashboxExpenseTotal = (float) $company->expenses()
            ->where('branch_id', $branch->id)
            ->where('source', 'cashbox')
            ->whereBetween('spent_at', $dayRange)
            ->sum('amount');
        $cashTotal = (float) $payments->flatMap->splits->where('method', 'cash')->sum('amount')
            + (float) $productSales->sum('cash_amount');
        $qrTotal = (float) $payments->flatMap->splits->where('method', 'qr')->sum('amount')
            + (float) $productSales->sum('qr_amount');

        return view('livewire.clinic.cashbox-summary', [
            'payments' => $payments,
            'cashTotal' => $cashTotal,
            'qrTotal' => $qrTotal,
            'cashboxExpenseTotal' => $cashboxExpenseTotal,
            'netCashTotal' => $cashTotal - $cashboxExpenseTotal,
            'netTotal' => $cashTotal + $qrTotal - $cashboxExpenseTotal,
            'invoiceTotal' => $payments->where('invoice_requested', true)->sum('amount') + $productSales->where('invoice_requested', true)->sum('subtotal'),
            'historyRows' => $this->historyRows($payments, $expenses, $productSales),
            'canManageRecords' => $this->canManageRecords(),
            'expenseTypes' => $company->expenseTypes()->where('status', 'active')->orderBy('name')->get(),
            'staffUsers' => $company->users()->orderBy('name')->get(),
        ]);
    }

    private function historyRows($payments, $expenses, $productSales): array
    {
        $rows = $this->paymentRows($payments);
        $method = $this->paymentMethodFilter;
        $source = $this->expenseSourceFilter;

        return [
            'services' => $rows['services']
                ->filter(fn (array $row) => $method === '' || $row['method'] === $method)
                ->values(),
            'products' => $rows['products']
                ->merge($this->productSaleRows($productSales))
                ->filter(fn (array $row) => $method === '' || $row['method'] === $method)
                ->sortByDesc('sort_at')
                ->values(),
            'expenses' => $expenses
                ->filter(fn ($expense) => $source === '' || $expense->source === $source)
                ->values(),
        ];
    }

    private function productSaleRows($productSales)
    {
        return $productSales->flatMap(function (ProductSale $sale) {
            $cashLeft = (float) $sale->cash_amount;
            $qrLeft = (float) $sale->qr_amount;

            return $sale->items->map(function ($item) use ($sale, &$cashLeft, &$qrLeft) {
                $total = (float) $item->total;
                $cash = min($cashLeft, $total);
                $cashLeft -= $cash;
                $qr = min($qrLeft, $total - $cash);
                $qrLeft -= $qr;

                return [
                    'payment_id' => null,
                    'product_sale_id' => $sale->id,
                    'client' => $sale->buyer_name ?: 'Consumidor final',
                    'date' => $sale->sold_at->format('d/m/Y'),
                    'time' => $sale->sold_at->format('H:i'),
                    'sort_at' => $sale->sold_at,
                    'name' => $item->name,
                    'quantity' => (float) ($item->display_quantity ?? $item->quantity),
                    'total' => $total,
                    'cash' => $cash,
                    'qr' => $qr,
                    'method' => $this->linePaymentMethod($cash, $qr),
                    'staff' => $sale->soldBy?->name ?? 'Sin vendedor',
                    'invoice' => $sale->invoice_requested ? 'Para facturar' : 'Sin factura solicitada',
                    'reference' => $sale->reference ?: 'Venta directa',
                ];
            });
        });
    }

    private function paymentRows($payments): array
    {
        $rows = [
            'services' => collect(),
            'products' => collect(),
        ];

        foreach ($payments as $payment) {
            $cashLeft = (float) $payment->splits->where('method', 'cash')->sum('amount');
            $qrLeft = (float) $payment->splits->where('method', 'qr')->sum('amount');
            $items = $payment->items->sortBy(fn ($item) => $item->type === 'service' ? 0 : 1);

            foreach ($items as $item) {
                $total = (float) $item->total;
                $cash = min($cashLeft, $total);
                $cashLeft -= $cash;
                $qr = min($qrLeft, $total - $cash);
                $qrLeft -= $qr;
                $group = $item->type === 'product' ? 'products' : 'services';

                $rows[$group]->push([
                    'payment_id' => $payment->id,
                    'product_sale_id' => null,
                    'client' => $payment->client?->full_name ?? 'Cliente',
                    'date' => $payment->paid_at->format('d/m/Y'),
                    'time' => $payment->paid_at->format('H:i'),
                    'sort_at' => $payment->paid_at,
                    'name' => $item->name,
                    'quantity' => (float) $item->quantity,
                    'total' => $total,
                    'cash' => $cash,
                    'qr' => $qr,
                    'method' => $this->linePaymentMethod($cash, $qr),
                    'staff' => $payment->performedBy?->name ?? 'Sin profesional',
                    'invoice' => $payment->invoice_requested ? 'Para facturar' : 'Sin factura solicitada',
                    'reference' => $payment->reference ?: 'Sin referencia',
                ]);
            }
        }

        return $rows;
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

    private function authorizeSuperAdmin(): void
    {
        abort_unless($this->canManageRecords(), 403);
    }

    private function canManageRecords(): bool
    {
        $company = $this->company();
        $companyRole = Auth::user()
            ->companies()
            ->where('companies.id', $company->id)
            ->value('company_user.role');
        $branchRole = Auth::user()
            ->branches()
            ->where('branches.id', $this->activeBranch()->id)
            ->leftJoin('roles', 'roles.id', '=', 'branch_user.role_id')
            ->value('roles.slug');

        return in_array($companyRole, ['owner', 'super_admin', 'super-administrador', 'admin', 'administrador'], true)
            || in_array($branchRole, ['owner', 'super_admin', 'super-administrador', 'admin', 'administrador'], true);
    }

    private function resetExpenseForm(): void
    {
        $this->reset(['editingExpenseId', 'expenseTypeId', 'staffUserId', 'expenseAmount', 'expenseReference', 'expenseDescription']);
        $this->expenseSource = 'cashbox';
        $this->expenseSpentAt = $this->selectedDate;
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
}
