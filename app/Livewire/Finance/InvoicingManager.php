<?php

namespace App\Livewire\Finance;

use App\Models\Branch;
use App\Models\Company;
use App\Models\ProductSale;
use App\Models\TreatmentPayment;
use App\Support\ActiveBranch;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class InvoicingManager extends Component
{
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $branchFilter = '';
    public string $typeFilter = '';
    public string $statusFilter = 'pending';
    public string $search = '';

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->branchFilter = (string) (session('active_branch_id') ?: '');
    }

    public function markAsInvoiced(string $kind, int $id): void
    {
        $record = $this->recordQuery($kind)->whereKey($id)->firstOrFail();

        $record->forceFill([
            'invoice_status' => 'invoiced',
            'invoiced_at' => now(),
            'invoiced_by_user_id' => Auth::id(),
        ])->save();
    }

    public function markAsPending(string $kind, int $id): void
    {
        $record = $this->recordQuery($kind)->whereKey($id)->firstOrFail();

        $record->forceFill([
            'invoice_status' => 'pending',
            'invoiced_at' => null,
            'invoiced_by_user_id' => null,
        ])->save();
    }

    public function render()
    {
        $company = $this->company();
        $branches = $this->branches($company);
        $activeBranch = ActiveBranch::resolve(Auth::user(), $branches);
        $rows = collect();

        if ($this->typeFilter !== 'products') {
            $rows = $rows->merge($this->serviceRows($company));
        }

        if ($this->typeFilter !== 'services') {
            $rows = $rows->merge($this->productRows($company));
        }

        $rows = $rows
            ->sortByDesc('sort_date')
            ->values();

        return view('livewire.finance.invoicing-manager', [
            'rows' => $rows,
            'branches' => $branches,
            'activeBranch' => $activeBranch,
            'pendingTotal' => $rows->where('invoice_status', 'pending')->sum('total'),
            'invoicedTotal' => $rows->where('invoice_status', 'invoiced')->sum('total'),
            'currency' => Money::symbol($activeBranch),
        ]);
    }

    private function serviceRows(Company $company)
    {
        return $company->treatmentPayments()
            ->with(['branch', 'client', 'items', 'splits', 'invoicedBy'])
            ->where('invoice_requested', true)
            ->when($this->statusFilter !== '', fn ($query) => $query->where('invoice_status', $this->statusFilter))
            ->when($this->branchFilter !== '', fn ($query) => $query->where('branch_id', $this->branchFilter))
            ->whereBetween('paid_at', $this->range())
            ->when(trim($this->search) !== '', function ($query) {
                $term = '%'.trim($this->search).'%';

                $query->where(function ($query) use ($term) {
                    $query->where('invoice_nit', 'like', $term)
                        ->orWhere('invoice_name', 'like', $term)
                        ->orWhereHas('client', fn ($clientQuery) => $clientQuery
                            ->where('full_name', 'like', $term)
                            ->orWhere('identity_document', 'like', $term)
                            ->orWhere('phone', 'like', $term));
                });
            })
            ->latest('paid_at')
            ->get()
            ->map(function (TreatmentPayment $payment) {
                $details = $payment->items
                    ->where('type', 'service')
                    ->pluck('name')
                    ->filter()
                    ->unique()
                    ->values()
                    ->implode(', ');

                if ($details === '') {
                    $details = $payment->items->pluck('name')->filter()->unique()->values()->implode(', ');
                }

                return [
                    'kind' => 'services',
                    'label' => 'Servicios',
                    'id' => $payment->id,
                    'sort_date' => $payment->paid_at,
                    'date' => $payment->paid_at?->format('d/m/Y H:i'),
                    'branch' => $payment->branch?->name ?? 'Sin sucursal',
                    'customer' => $payment->invoice_name ?: ($payment->client?->full_name ?: 'Sin cliente'),
                    'nit' => $payment->invoice_nit ?: ($payment->client?->identity_document ?: 'Sin NIT'),
                    'detail' => $details ?: 'Cobro de tratamiento',
                    'total' => (float) $payment->amount,
                    'cash' => (float) $payment->splits->where('method', 'cash')->sum('amount'),
                    'qr' => (float) $payment->splits->where('method', 'qr')->sum('amount'),
                    'invoice_status' => $payment->invoice_status ?: 'pending',
                    'invoiced_by' => $payment->invoicedBy?->name,
                    'invoiced_at' => $payment->invoiced_at?->format('d/m/Y H:i'),
                ];
            });
    }

    private function productRows(Company $company)
    {
        return $company->productSales()
            ->with(['branch', 'items', 'invoicedBy'])
            ->where('invoice_requested', true)
            ->when($this->statusFilter !== '', fn ($query) => $query->where('invoice_status', $this->statusFilter))
            ->when($this->branchFilter !== '', fn ($query) => $query->where('branch_id', $this->branchFilter))
            ->whereBetween('sold_at', $this->range())
            ->when(trim($this->search) !== '', function ($query) {
                $term = '%'.trim($this->search).'%';

                $query->where(function ($query) use ($term) {
                    $query->where('buyer_name', 'like', $term)
                        ->orWhere('buyer_nit', 'like', $term)
                        ->orWhere('buyer_phone', 'like', $term)
                        ->orWhere('buyer_email', 'like', $term)
                        ->orWhereHas('items', fn ($itemQuery) => $itemQuery->where('name', 'like', $term));
                });
            })
            ->latest('sold_at')
            ->get()
            ->map(fn (ProductSale $sale) => [
                'kind' => 'products',
                'label' => 'Productos',
                'id' => $sale->id,
                'sort_date' => $sale->sold_at,
                'date' => $sale->sold_at?->format('d/m/Y H:i'),
                'branch' => $sale->branch?->name ?? 'Sin sucursal',
                'customer' => $sale->buyer_name ?: 'Consumidor final',
                'nit' => $sale->buyer_nit ?: 'Sin NIT',
                'detail' => $sale->items
                    ->map(fn ($item) => $item->name.' x '.number_format((float) $item->quantity, 2))
                    ->implode(', '),
                'total' => (float) $sale->subtotal,
                'cash' => (float) $sale->cash_amount,
                'qr' => (float) $sale->qr_amount,
                'invoice_status' => $sale->invoice_status ?: 'pending',
                'invoiced_by' => $sale->invoicedBy?->name,
                'invoiced_at' => $sale->invoiced_at?->format('d/m/Y H:i'),
            ]);
    }

    private function recordQuery(string $kind)
    {
        $company = $this->company();

        return $kind === 'products'
            ? $company->productSales()->where('invoice_requested', true)
            : $company->treatmentPayments()->where('invoice_requested', true);
    }

    private function range(): array
    {
        return [
            Carbon::parse($this->dateFrom ?: now()->startOfMonth())->startOfDay(),
            Carbon::parse($this->dateTo ?: now())->endOfDay(),
        ];
    }

    private function company(): Company
    {
        return Auth::user()->companies()->firstOrFail();
    }

    private function branches(Company $company)
    {
        $branches = Auth::user()->branches()->where('company_id', $company->id)->orderBy('name')->get();

        return $branches->isNotEmpty() ? $branches : $company->branches()->orderBy('name')->get();
    }
}
