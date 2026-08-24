<?php

namespace App\Livewire\Dashboard;

use App\Models\Branch;
use App\Models\Company;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class HomeSummary extends Component
{
    public function render()
    {
        $company = $this->company();
        $branch = $this->activeBranch($company);
        $today = now();
        $dayRange = [$today->copy()->startOfDay(), $today->copy()->endOfDay()];
        $weekRange = [$today->copy()->startOfWeek(), $today->copy()->endOfWeek()];
        $monthRange = [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()];

        $appointmentsToday = $company->appointments()
            ->with(['client', 'services'])
            ->where('branch_id', $branch->id)
            ->whereBetween('scheduled_at', $dayRange)
            ->orderBy('scheduled_at')
            ->get();
        $attendedToday = $appointmentsToday->where('attended', true)->count();
        $attendanceRateToday = $appointmentsToday->count() > 0
            ? round(($attendedToday / $appointmentsToday->count()) * 100)
            : 0;

        $cashbox = $this->cashboxTotals($company, $branch, $dayRange);
        $monthExpenses = (float) $company->expenses()
            ->where('branch_id', $branch->id)
            ->whereBetween('spent_at', $monthRange)
            ->sum('amount');

        $newClientsToday = $company->clients()
            ->where(function ($query) use ($branch) {
                $query->whereNull('branch_id')->orWhere('branch_id', $branch->id);
            })
            ->whereBetween('created_at', $dayRange)
            ->count();

        $lowStockProducts = $this->lowStockProducts($company, $branch);
        $upcomingExpirations = $this->upcomingExpirations($company, $branch, $today);
        $recentExpenses = $company->expenses()
            ->with('type')
            ->where('branch_id', $branch->id)
            ->whereBetween('spent_at', $monthRange)
            ->latest('spent_at')
            ->limit(4)
            ->get();

        return view('livewire.dashboard.home-summary', [
            'branch' => $branch,
            'appointmentsToday' => $appointmentsToday,
            'attendedToday' => $attendedToday,
            'attendanceRateToday' => $attendanceRateToday,
            'weeklyBranchAttendance' => $this->weeklyBranchAttendance($company, $weekRange),
            'cashbox' => $cashbox,
            'newClientsToday' => $newClientsToday,
            'lowStockProducts' => $lowStockProducts,
            'upcomingExpirations' => $upcomingExpirations,
            'monthExpenses' => $monthExpenses,
            'recentExpenses' => $recentExpenses,
            'topProfessional' => $this->topProfessional($company, $branch, $monthRange),
        ]);
    }

    private function cashboxTotals(Company $company, Branch $branch, array $dayRange): array
    {
        $payments = $company->treatmentPayments()
            ->with('splits')
            ->where('branch_id', $branch->id)
            ->whereBetween('paid_at', $dayRange)
            ->get();
        $cash = (float) $payments->flatMap->splits->where('method', 'cash')->sum('amount');
        $qr = (float) $payments->flatMap->splits->where('method', 'qr')->sum('amount');
        $cashboxExpenses = (float) $company->expenses()
            ->where('branch_id', $branch->id)
            ->where('source', 'cashbox')
            ->whereBetween('spent_at', $dayRange)
            ->sum('amount');

        return [
            'cash' => $cash,
            'qr' => $qr,
            'expenses' => $cashboxExpenses,
            'net' => $cash + $qr - $cashboxExpenses,
        ];
    }

    private function lowStockProducts(Company $company, Branch $branch)
    {
        return $company->inventoryProducts()
            ->leftJoin('inventory_product_batches', function ($join) use ($branch) {
                $join->on('inventory_product_batches.inventory_product_id', '=', 'inventory_products.id')
                    ->where('inventory_product_batches.branch_id', '=', $branch->id)
                    ->where('inventory_product_batches.status', 'active');
            })
            ->select([
                'inventory_products.id',
                'inventory_products.name',
                'inventory_products.code',
                'inventory_products.minimum_stock',
                DB::raw('COALESCE(SUM(inventory_product_batches.current_quantity), 0) as current_stock'),
            ])
            ->where('inventory_products.status', 'active')
            ->whereRaw('COALESCE(inventory_products.minimum_stock, 0) > 0')
            ->groupBy('inventory_products.id', 'inventory_products.name', 'inventory_products.code', 'inventory_products.minimum_stock')
            ->havingRaw('COALESCE(SUM(inventory_product_batches.current_quantity), 0) <= COALESCE(inventory_products.minimum_stock, 0)')
            ->orderBy('current_stock')
            ->orderBy('inventory_products.name')
            ->limit(5)
            ->get();
    }

    private function upcomingExpirations(Company $company, Branch $branch, Carbon $today)
    {
        return $company->inventoryBatches()
            ->with('product')
            ->where('branch_id', $branch->id)
            ->where('status', 'active')
            ->where('current_quantity', '>', 0)
            ->whereBetween('expires_at', [$today->copy()->startOfDay(), $today->copy()->addDays(30)->endOfDay()])
            ->orderBy('expires_at')
            ->limit(5)
            ->get();
    }

    private function weeklyBranchAttendance(Company $company, array $weekRange)
    {
        return $company->branches()
            ->with('businessType')
            ->orderBy('name')
            ->get()
            ->map(function (Branch $branch) use ($company, $weekRange) {
                $appointments = $company->appointments()
                    ->where('branch_id', $branch->id)
                    ->whereBetween('scheduled_at', $weekRange)
                    ->get();
                $scheduled = $appointments->count();
                $attended = $appointments->where('attended', true)->count();

                return [
                    'name' => $branch->name,
                    'type' => $branch->businessType?->name ?? 'Negocio',
                    'scheduled' => $scheduled,
                    'attended' => $attended,
                    'rate' => $scheduled > 0 ? round(($attended / $scheduled) * 100) : 0,
                ];
            })
            ->filter(fn ($row) => $row['scheduled'] > 0)
            ->values();
    }

    private function topProfessional(Company $company, Branch $branch, array $monthRange): ?array
    {
        $attendedAppointments = $company->appointments()
            ->with('attendedBy')
            ->where('branch_id', $branch->id)
            ->where('attended', true)
            ->whereBetween('scheduled_at', $monthRange)
            ->get();

        $totalAttended = $attendedAppointments->count();

        if ($totalAttended === 0) {
            return null;
        }

        return $attendedAppointments
            ->groupBy(fn ($appointment) => $appointment->attended_by_user_id ?: 'none')
            ->map(function ($items) use ($totalAttended) {
                $count = $items->count();

                return [
                    'name' => $items->first()->attendedBy?->name ?? 'Sin profesional asignado',
                    'count' => $count,
                    'percentage' => round(($count / $totalAttended) * 100),
                ];
            })
            ->sortByDesc('count')
            ->first();
    }

    private function company(): Company
    {
        return Auth::user()->companies()->firstOrFail();
    }

    private function activeBranch(Company $company): Branch
    {
        $branches = Auth::user()->branches()->where('company_id', $company->id)->orderBy('name')->get();
        $branches = $branches->isNotEmpty() ? $branches : $company->branches()->orderBy('name')->get();

        return $branches->firstWhere('id', session('active_branch_id'))
            ?? $branches->first()
            ?? $company->branches()->firstOrFail();
    }
}
