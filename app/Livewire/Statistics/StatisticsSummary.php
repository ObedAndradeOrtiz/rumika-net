<?php

namespace App\Livewire\Statistics;

use App\Models\Company;
use App\Support\RumikaAccess;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class StatisticsSummary extends Component
{
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $branchFilter = '';

    public function mount(): void
    {
        abort_unless($this->canView(), 403);

        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo = now()->toDateString();
    }

    public function render()
    {
        abort_unless($this->canView(), 403);

        $this->validate([
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date', 'after_or_equal:dateFrom'],
            'branchFilter' => ['nullable', 'integer'],
        ]);

        $company = $this->company();
        $from = Carbon::parse($this->dateFrom)->startOfDay();
        $to = Carbon::parse($this->dateTo)->endOfDay();
        $branches = $company->branches()->with('businessType')->orderBy('name')->get();
        $branchIds = $this->branchFilter !== ''
            ? collect([(int) $this->branchFilter])
            : $branches->pluck('id');

        $appointments = $company->appointments()
            ->with(['branch', 'client', 'services'])
            ->whereIn('branch_id', $branchIds)
            ->whereBetween('scheduled_at', [$from, $to])
            ->get();
        $payments = $company->treatmentPayments()
            ->with(['branch', 'items', 'splits'])
            ->whereIn('branch_id', $branchIds)
            ->whereBetween('paid_at', [$from, $to])
            ->get();
        $expenses = $company->expenses()
            ->with(['branch', 'type'])
            ->whereIn('branch_id', $branchIds)
            ->whereBetween('spent_at', [$from->toDateString(), $to->toDateString()])
            ->get();

        $scheduled = $appointments->count();
        $attended = $appointments->where('attended', true)->count();
        $noShow = $appointments->where('status', 'no_show')->count();
        $pending = max(0, $scheduled - $attended - $noShow);
        $attendanceRate = $scheduled > 0 ? round(($attended / $scheduled) * 100) : 0;
        $servicesIncome = (float) $payments->flatMap->items->where('type', 'service')->sum('total');
        $productsIncome = (float) $payments->flatMap->items->where('type', 'product')->sum('total');
        $expensesTotal = (float) $expenses->sum('amount');

        return view('livewire.statistics.statistics-summary', [
            'branches' => $branches,
            'dateLabel' => $from->format('d/m/Y').' - '.$to->format('d/m/Y'),
            'attendance' => [
                'scheduled' => $scheduled,
                'attended' => $attended,
                'no_show' => $noShow,
                'pending' => $pending,
                'rate' => $attendanceRate,
            ],
            'finance' => [
                'services' => $servicesIncome,
                'products' => $productsIncome,
                'income' => $servicesIncome + $productsIncome,
                'expenses' => $expensesTotal,
                'net' => $servicesIncome + $productsIncome - $expensesTotal,
                'cash' => (float) $payments->flatMap->splits->where('method', 'cash')->sum('amount'),
                'qr' => (float) $payments->flatMap->splits->where('method', 'qr')->sum('amount'),
            ],
            'branchRows' => $this->branchRows($branches, $appointments, $payments, $expenses),
            'dailyRows' => $this->dailyRows($appointments, $payments, $expenses, $from, $to),
            'topServices' => $this->topServices($appointments),
        ]);
    }

    private function branchRows($branches, $appointments, $payments, $expenses)
    {
        return $branches
            ->when($this->branchFilter !== '', fn ($items) => $items->where('id', (int) $this->branchFilter))
            ->map(function ($branch) use ($appointments, $payments, $expenses) {
                $branchAppointments = $appointments->where('branch_id', $branch->id);
                $branchPayments = $payments->where('branch_id', $branch->id);
                $services = (float) $branchPayments->flatMap->items->where('type', 'service')->sum('total');
                $products = (float) $branchPayments->flatMap->items->where('type', 'product')->sum('total');
                $expenseTotal = (float) $expenses->where('branch_id', $branch->id)->sum('amount');
                $scheduled = $branchAppointments->count();
                $attended = $branchAppointments->where('attended', true)->count();

                return [
                    'name' => $branch->name,
                    'type' => $branch->businessType?->name ?? 'Sin tipo',
                    'scheduled' => $scheduled,
                    'attended' => $attended,
                    'rate' => $scheduled > 0 ? round(($attended / $scheduled) * 100) : 0,
                    'income' => $services + $products,
                    'services' => $services,
                    'products' => $products,
                    'expenses' => $expenseTotal,
                    'net' => $services + $products - $expenseTotal,
                ];
            })
            ->values();
    }

    private function dailyRows($appointments, $payments, $expenses, Carbon $from, Carbon $to)
    {
        $days = collect();
        $cursor = $from->copy()->startOfDay();

        while ($cursor <= $to) {
            $dayStart = $cursor->copy()->startOfDay();
            $dayEnd = $cursor->copy()->endOfDay();
            $dayAppointments = $appointments->filter(fn ($appointment) => $appointment->scheduled_at->between($dayStart, $dayEnd));
            $dayPayments = $payments->filter(fn ($payment) => $payment->paid_at->between($dayStart, $dayEnd));
            $dayExpenses = $expenses->filter(fn ($expense) => $expense->spent_at->between($dayStart, $dayEnd));

            $days->push([
                'date' => $cursor->format('d/m'),
                'scheduled' => $dayAppointments->count(),
                'attended' => $dayAppointments->where('attended', true)->count(),
                'income' => (float) $dayPayments->flatMap->items->sum('total'),
                'expenses' => (float) $dayExpenses->sum('amount'),
            ]);

            $cursor->addDay();
        }

        return $days;
    }

    private function topServices($appointments)
    {
        return $appointments
            ->flatMap->services
            ->groupBy('name')
            ->map(fn ($services, $name) => [
                'name' => $name,
                'count' => $services->count(),
            ])
            ->sortByDesc('count')
            ->take(8)
            ->values();
    }

    private function canView(): bool
    {
        return RumikaAccess::can(Auth::user(), 'estadisticas');
    }

    private function company(): Company
    {
        return Auth::user()->companies()->firstOrFail();
    }
}
