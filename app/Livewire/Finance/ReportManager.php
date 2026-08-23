<?php

namespace App\Livewire\Finance;

use App\Models\Company;
use App\Support\ActiveBranch;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ReportManager extends Component
{
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $branchFilter = '';

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function render()
    {
        $company = $this->company();
        $branches = $this->branches($company);
        $activeBranch = ActiveBranch::resolve(Auth::user(), $branches);
        $data = $this->reportData($company);

        return view('livewire.finance.report-manager', [
            ...$data,
            'branches' => $branches,
            'activeBranch' => $activeBranch,
            'currency' => Money::symbol($activeBranch),
            'pdfUrl' => route('finance.reports.pdf', [
                'from' => $this->dateFrom,
                'to' => $this->dateTo,
                'branch' => $this->branchFilter,
            ]),
        ]);
    }

    public function reportData(Company $company): array
    {
        $range = $this->range();
        $branchIds = $this->branchFilter !== ''
            ? [(int) $this->branchFilter]
            : $this->branches($company)->pluck('id')->all();

        $payments = $company->treatmentPayments()
            ->with(['branch', 'client', 'performedBy', 'items.soldBy', 'items.appointmentService.performedBy', 'items.appointmentService.referredBy'])
            ->whereIn('branch_id', $branchIds)
            ->whereBetween('paid_at', $range)
            ->get();

        $productSales = $company->productSales()
            ->with(['branch', 'soldBy', 'items'])
            ->whereIn('branch_id', $branchIds)
            ->whereBetween('sold_at', $range)
            ->get();

        $expenses = $company->expenses()
            ->with(['branch', 'type', 'createdBy', 'staffUser'])
            ->whereIn('branch_id', $branchIds)
            ->whereBetween('spent_at', $range)
            ->get();

        $appointments = $company->appointments()
            ->with(['branch', 'client', 'services.referredBy'])
            ->whereIn('branch_id', $branchIds)
            ->whereBetween('scheduled_at', $range)
            ->get();

        $debts = $company->clientCharges()
            ->whereIn('branch_id', $branchIds)
            ->whereIn('status', ['pending', 'partial'])
            ->where('balance_amount', '>', 0)
            ->get();

        $serviceItems = $payments->flatMap(fn ($payment) => $payment->items
            ->where('type', 'service')
            ->map(fn ($item) => ['payment' => $payment, 'item' => $item]));
        $productItems = $payments->flatMap(fn ($payment) => $payment->items
            ->where('type', 'product')
            ->map(fn ($item) => ['payment' => $payment, 'item' => $item]))
            ->merge($productSales->flatMap(fn ($sale) => $sale->items
                ->map(fn ($item) => ['sale' => $sale, 'item' => $item])));

        $serviceIncome = (float) $serviceItems->sum(fn ($row) => (float) $row['item']->total);
        $productIncome = (float) $productItems->sum(fn ($row) => (float) $row['item']->total);
        $expenseTotal = (float) $expenses->sum('amount');

        return [
            'rangeLabel' => Carbon::parse($this->dateFrom)->format('d/m/Y').' - '.Carbon::parse($this->dateTo)->format('d/m/Y'),
            'kpis' => [
                'income' => $serviceIncome + $productIncome,
                'services' => $serviceIncome,
                'products' => $productIncome,
                'expenses' => $expenseTotal,
                'net' => $serviceIncome + $productIncome - $expenseTotal,
                'debts' => (float) $debts->sum('balance_amount'),
                'commissions' => (float) $serviceItems->sum(fn ($row) => (float) $row['item']->commission_amount)
                    + (float) $productItems->sum(fn ($row) => (float) $row['item']->commission_amount),
                'appointments' => $appointments->count(),
                'attended' => $appointments->where('attended', true)->count(),
            ],
            'branchRows' => $this->branchRows($this->branches($company), $payments, $productSales, $expenses, $appointments, $debts),
            'serviceRows' => $serviceItems
                ->groupBy(fn ($row) => $row['item']->name)
                ->map(fn ($rows, $name) => ['name' => $name, 'count' => $rows->count(), 'total' => (float) $rows->sum(fn ($row) => (float) $row['item']->total)])
                ->sortByDesc('total')
                ->take(10)
                ->values(),
            'productRows' => $productItems
                ->groupBy(fn ($row) => $row['item']->name)
                ->map(fn ($rows, $name) => ['name' => $name, 'count' => $rows->sum(fn ($row) => (float) $row['item']->quantity), 'total' => (float) $rows->sum(fn ($row) => (float) $row['item']->total)])
                ->sortByDesc('total')
                ->take(10)
                ->values(),
            'staffRows' => $this->staffRows($serviceItems, $productItems),
            'referredServiceRows' => $this->referredServiceRows($appointments),
        ];
    }

    private function branchRows($branches, $payments, $productSales, $expenses, $appointments, $debts)
    {
        return $branches
            ->when($this->branchFilter !== '', fn ($items) => $items->where('id', (int) $this->branchFilter))
            ->map(function ($branch) use ($payments, $productSales, $expenses, $appointments, $debts) {
                $branchPayments = $payments->where('branch_id', $branch->id);
                $serviceIncome = $branchPayments->flatMap->items->where('type', 'service')->sum('total');
                $productIncome = $branchPayments->flatMap->items->where('type', 'product')->sum('total')
                    + $productSales->where('branch_id', $branch->id)->sum('subtotal');
                $branchExpenses = $expenses->where('branch_id', $branch->id)->sum('amount');
                $branchAppointments = $appointments->where('branch_id', $branch->id);

                return [
                    'name' => $branch->name,
                    'services' => (float) $serviceIncome,
                    'products' => (float) $productIncome,
                    'expenses' => (float) $branchExpenses,
                    'net' => (float) ($serviceIncome + $productIncome - $branchExpenses),
                    'appointments' => $branchAppointments->count(),
                    'attended' => $branchAppointments->where('attended', true)->count(),
                    'debts' => (float) $debts->where('branch_id', $branch->id)->sum('balance_amount'),
                ];
            })
            ->values();
    }

    private function staffRows($serviceItems, $productItems)
    {
        $rows = collect();

        $serviceItems->each(function ($row) use ($rows) {
            $staff = $row['item']->appointmentService?->referredBy
                ?? $row['item']->appointmentService?->performedBy
                ?? $row['payment']->performedBy;
            $key = $staff?->id ? 'u'.$staff->id : 'none';
            $current = $rows->get($key, ['name' => $staff?->name ?? 'Sin responsable', 'services' => 0, 'products' => 0, 'commission' => 0]);
            $current['services'] += (float) $row['item']->total;
            $current['commission'] += (float) $row['item']->commission_amount;
            $rows->put($key, $current);
        });

        $productItems->each(function ($row) use ($rows) {
            $staff = isset($row['sale'])
                ? $row['sale']->soldBy
                : ($row['item']->soldBy ?? null);
            $key = $staff?->id ? 'u'.$staff->id : 'none';
            $current = $rows->get($key, ['name' => $staff?->name ?? 'Sin responsable', 'services' => 0, 'products' => 0, 'commission' => 0]);
            $current['products'] += (float) $row['item']->total;
            $current['commission'] += (float) $row['item']->commission_amount;
            $rows->put($key, $current);
        });

        return $rows->values()->sortByDesc(fn ($row) => $row['services'] + $row['products'])->values();
    }

    private function referredServiceRows($appointments)
    {
        return $appointments
            ->flatMap(fn ($appointment) => $appointment->services
                ->filter(fn ($service) => $service->referredBy)
                ->map(fn ($service) => ['appointment' => $appointment, 'service' => $service]))
            ->groupBy(fn ($row) => $row['service']->referredBy->id)
            ->map(function ($rows) {
                $staff = $rows->first()['service']->referredBy;

                return [
                    'name' => $staff->name,
                    'count' => $rows->count(),
                    'completed' => $rows->where('service.status', 'completed')->count(),
                    'total' => (float) $rows->sum(fn ($row) => (float) $row['service']->price),
                ];
            })
            ->sortByDesc('total')
            ->values();
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
