<?php

namespace App\Livewire\Finance;

use App\Models\Company;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class IncomeSummary extends Component
{
    public string $dateFrom = '';
    public string $dateTo = '';

    public function mount(): void
    {
        abort_unless($this->canViewSummary(), 403);

        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo = now()->toDateString();
    }

    public function updatedDateFrom(): void
    {
        $this->validateDateRange();
    }

    public function updatedDateTo(): void
    {
        $this->validateDateRange();
    }

    public function render()
    {
        abort_unless($this->canViewSummary(), 403);

        $this->validateDateRange();

        $company = $this->company();
        $from = Carbon::parse($this->dateFrom)->startOfDay();
        $to = Carbon::parse($this->dateTo)->endOfDay();
        $branches = $company->branches()->with('businessType')->orderBy('name')->get();
        $branchIds = $branches->pluck('id');
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
        $branchRows = $this->branchRows($branches, $payments, $expenses);
        $totals = [
            'services' => $branchRows->sum('services'),
            'products' => $branchRows->sum('products'),
            'expenses' => $branchRows->sum('expenses'),
            'cash' => $branchRows->sum('cash'),
            'qr' => $branchRows->sum('qr'),
        ];
        $totals['income'] = $totals['services'] + $totals['products'];
        $totals['net'] = $totals['income'] - $totals['expenses'];

        return view('livewire.finance.income-summary', [
            'branchRows' => $branchRows,
            'totals' => $totals,
            'dateLabel' => $from->format('d/m/Y').' - '.$to->format('d/m/Y'),
        ]);
    }

    private function branchRows(Collection $branches, Collection $payments, Collection $expenses): Collection
    {
        return $branches->map(function ($branch) use ($payments, $expenses) {
            $branchPayments = $payments->where('branch_id', $branch->id);
            $branchExpenses = $expenses->where('branch_id', $branch->id);
            $services = $this->sumItems($branchPayments, 'service');
            $products = $this->sumItems($branchPayments, 'product');
            $cash = (float) $branchPayments->flatMap->splits->where('method', 'cash')->sum('amount');
            $qr = (float) $branchPayments->flatMap->splits->where('method', 'qr')->sum('amount');
            $expenseTotal = (float) $branchExpenses->sum('amount');
            $income = $services + $products;

            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'type' => $branch->businessType?->name ?? 'Sin tipo',
                'services' => $services,
                'products' => $products,
                'expenses' => $expenseTotal,
                'cash' => $cash,
                'qr' => $qr,
                'income' => $income,
                'net' => $income - $expenseTotal,
                'payments_count' => $branchPayments->count(),
                'expenses_count' => $branchExpenses->count(),
            ];
        });
    }

    private function sumItems(Collection $payments, string $type): float
    {
        return (float) $payments
            ->flatMap->items
            ->where('type', $type)
            ->sum(fn ($item) => (float) $item->total);
    }

    private function validateDateRange(): void
    {
        $this->validate([
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['required', 'date', 'after_or_equal:dateFrom'],
        ], [
            'dateTo.after_or_equal' => 'La fecha final debe ser igual o posterior a la fecha inicial.',
        ]);
    }

    private function canViewSummary(): bool
    {
        $user = Auth::user();
        $company = $this->company();
        $companyRole = $user
            ->companies()
            ->where('companies.id', $company->id)
            ->value('company_user.role');

        if ($this->roleNameCanView($companyRole)) {
            return true;
        }

        return $user
            ->branches()
            ->where('branches.company_id', $company->id)
            ->leftJoin('roles', 'roles.id', '=', 'branch_user.role_id')
            ->select(['roles.slug', 'roles.permissions'])
            ->get()
            ->contains(fn ($role) => $this->roleNameCanView($role->slug)
                || $this->rolePermissionsCanView($role->permissions));
    }

    private function roleNameCanView(?string $role): bool
    {
        return in_array($role, [
            'owner',
            'super_admin',
            'super-administrador',
            'admin',
            'administrator',
            'administrador',
            'gerente',
        ], true);
    }

    private function rolePermissionsCanView(mixed $permissions): bool
    {
        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true);
        }

        if (! is_array($permissions)) {
            return false;
        }

        $summaryPermissions = $permissions['resumen_financiero'] ?? [];

        if (is_string($summaryPermissions)) {
            $summaryPermissions = [$summaryPermissions];
        }

        return is_array($summaryPermissions)
            && (in_array('view', $summaryPermissions, true) || in_array('*', $summaryPermissions, true));
    }

    private function company(): Company
    {
        return Auth::user()->companies()->firstOrFail();
    }
}
