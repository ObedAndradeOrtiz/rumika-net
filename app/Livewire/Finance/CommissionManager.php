<?php

namespace App\Livewire\Finance;

use App\Models\Branch;
use App\Models\CommissionTarget;
use App\Models\Company;
use App\Models\InventoryProduct;
use App\Models\TreatmentPaymentItem;
use App\Models\User;
use App\Support\ActiveBranch;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class CommissionManager extends Component
{
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $branchFilter = '';
    public string $periodFilter = 'monthly';

    public bool $showTargetModal = false;
    public ?int $editingTargetId = null;
    public ?int $confirmingTargetDeleteId = null;
    public string $targetUserId = '';
    public string $targetBranchId = '';
    public string $targetPeriodType = 'monthly';
    public string $targetMinimumSales = '0';
    public string $targetMinimumCommission = '0';
    public string $targetStatus = 'active';

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function createTarget(): void
    {
        $this->resetTargetForm();
        $this->showTargetModal = true;
    }

    public function editTarget(int $targetId): void
    {
        $target = $this->company()->commissionTargets()->whereKey($targetId)->firstOrFail();

        $this->editingTargetId = $target->id;
        $this->targetUserId = (string) $target->user_id;
        $this->targetBranchId = (string) $target->branch_id;
        $this->targetPeriodType = $target->period_type;
        $this->targetMinimumSales = (string) $target->minimum_sales_amount;
        $this->targetMinimumCommission = (string) $target->minimum_commission_amount;
        $this->targetStatus = $target->status;
        $this->resetErrorBag();
        $this->showTargetModal = true;
    }

    public function saveTarget(): void
    {
        $company = $this->company();
        $branchIds = $company->branches()->pluck('id')->all();
        $userIds = $company->users()->pluck('users.id')->all();

        $validated = $this->validate([
            'targetUserId' => ['required', Rule::in($userIds)],
            'targetBranchId' => ['nullable', Rule::in($branchIds)],
            'targetPeriodType' => ['required', Rule::in(['weekly', 'biweekly', 'monthly'])],
            'targetMinimumSales' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'targetMinimumCommission' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'targetStatus' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $target = $this->editingTargetId
            ? $company->commissionTargets()->whereKey($this->editingTargetId)->firstOrFail()
            : new CommissionTarget(['company_id' => $company->id]);

        $target->fill([
            'branch_id' => $validated['targetBranchId'] !== '' ? (int) $validated['targetBranchId'] : null,
            'user_id' => (int) $validated['targetUserId'],
            'period_type' => $validated['targetPeriodType'],
            'minimum_sales_amount' => $validated['targetMinimumSales'],
            'minimum_commission_amount' => $validated['targetMinimumCommission'],
            'status' => $validated['targetStatus'],
        ])->save();

        $this->showTargetModal = false;
        $this->resetTargetForm();
    }

    public function confirmDeleteTarget(int $targetId): void
    {
        $this->confirmingTargetDeleteId = $this->company()->commissionTargets()->whereKey($targetId)->value('id');
    }

    public function cancelDeleteTarget(): void
    {
        $this->confirmingTargetDeleteId = null;
    }

    public function deleteTarget(int $targetId): void
    {
        $this->company()->commissionTargets()->whereKey($targetId)->delete();
        $this->confirmingTargetDeleteId = null;
    }

    public function render()
    {
        $company = $this->company();
        $branches = $company->branches()->orderBy('name')->get();
        $activeBranch = ActiveBranch::resolve(Auth::user(), $branches);
        $branchIds = $this->branchFilter !== ''
            ? [(int) $this->branchFilter]
            : $branches->pluck('id')->all();
        $currency = Money::symbol($activeBranch);
        $data = $this->commissionData($company, $branchIds);

        return view('livewire.finance.commission-manager', [
            'branches' => $branches,
            'staffUsers' => $company->users()->orderBy('name')->get(),
            'currency' => $currency,
            'periods' => $this->periodOptions(),
            'rows' => $data['rows'],
            'targets' => $company->commissionTargets()->with(['user', 'branch'])->latest()->get(),
            'totals' => $data['totals'],
            'rangeLabel' => Carbon::parse($this->dateFrom)->format('d/m/Y').' - '.Carbon::parse($this->dateTo)->format('d/m/Y'),
        ]);
    }

    private function commissionData(Company $company, array $branchIds): array
    {
        $range = $this->range();
        $rows = collect();

        $payments = $company->treatmentPayments()
            ->with(['branch', 'performedBy', 'items.product', 'items.soldBy', 'items.appointmentService.service', 'items.appointmentService.performedBy', 'items.appointmentService.referredBy'])
            ->whereIn('branch_id', $branchIds)
            ->whereBetween('paid_at', $range)
            ->get();

        $payments->flatMap(fn ($payment) => $payment->items->map(fn ($item) => ['payment' => $payment, 'item' => $item]))
            ->each(function (array $row) use ($rows) {
                $item = $row['item'];
                $user = $item->type === 'service'
                    ? ($item->appointmentService?->referredBy ?? $item->appointmentService?->performedBy ?? $row['payment']->performedBy)
                    : ($item->soldBy ?? null);

                $this->putRow($rows, $user, $row['payment']->branch, $item->type, (float) $item->total, $item->type === 'service'
                    ? $this->serviceCommissionValue($row)
                    : $this->productCommissionValue($row));
            });

        $company->productSales()
            ->with(['branch', 'soldBy', 'items.product'])
            ->whereIn('branch_id', $branchIds)
            ->whereBetween('sold_at', $range)
            ->get()
            ->flatMap(fn ($sale) => $sale->items->map(fn ($item) => ['sale' => $sale, 'item' => $item]))
            ->each(fn (array $row) => $this->putRow(
                $rows,
                $row['sale']->soldBy,
                $row['sale']->branch,
                'product',
                (float) $row['item']->total,
                $this->productCommissionValue($row)
            ));

        $targets = $company->commissionTargets()
            ->where('status', 'active')
            ->where('period_type', $this->periodFilter)
            ->get();

        $company->users()->orderBy('name')->get()->each(function (User $user) use ($rows, $targets, $branchIds) {
            $userTargets = $targets->where('user_id', $user->id)
                ->filter(fn (CommissionTarget $target) => $target->branch_id === null || in_array($target->branch_id, $branchIds, true));

            foreach ($userTargets as $target) {
                $branchKey = $target->branch_id ?: 0;
                $key = $this->rowKey($user->id, $branchKey);

                if (! $rows->has($key)) {
                    $rows->put($key, $this->emptyRow($user, $target->branch));
                }
            }
        });

        $rows = $rows->map(function (array $row) use ($targets) {
            $target = $this->matchingTarget($targets, $row['user_id'], $row['branch_id']);
            $row['target_sales'] = (float) ($target?->minimum_sales_amount ?? 0);
            $row['target_commission'] = (float) ($target?->minimum_commission_amount ?? 0);
            $row['sales_shortfall'] = max(0, $row['target_sales'] - $row['total_sales']);
            $row['commission_shortfall'] = max(0, $row['target_commission'] - $row['commission']);
            $row['target_met'] = $row['sales_shortfall'] <= 0.01 && $row['commission_shortfall'] <= 0.01;

            return $row;
        })->sortByDesc('total_sales')->values();

        return [
            'rows' => $rows,
            'totals' => [
                'services' => (float) $rows->sum('services'),
                'products' => (float) $rows->sum('products'),
                'sales' => (float) $rows->sum('total_sales'),
                'commission' => (float) $rows->sum('commission'),
                'pending_sales' => (float) $rows->sum('sales_shortfall'),
            ],
        ];
    }

    private function putRow(Collection $rows, ?User $user, ?Branch $branch, string $type, float $amount, float $commission): void
    {
        $key = $this->rowKey($user?->id, $branch?->id);
        $current = $rows->get($key, $this->emptyRow($user, $branch));

        if ($type === 'service') {
            $current['services'] += $amount;
        } else {
            $current['products'] += $amount;
        }

        $current['total_sales'] += $amount;
        $current['commission'] += $commission;
        $rows->put($key, $current);
    }

    private function emptyRow(?User $user, ?Branch $branch): array
    {
        return [
            'user_id' => $user?->id,
            'branch_id' => $branch?->id,
            'name' => $user?->name ?? 'Sin responsable',
            'branch' => $branch?->name ?? 'Todas las sucursales',
            'services' => 0.0,
            'products' => 0.0,
            'total_sales' => 0.0,
            'commission' => 0.0,
        ];
    }

    private function rowKey(?int $userId, ?int $branchId): string
    {
        return ($userId ?: 'none').'-'.($branchId ?: 0);
    }

    private function matchingTarget(Collection $targets, ?int $userId, ?int $branchId): ?CommissionTarget
    {
        return $targets->first(fn (CommissionTarget $target) => $target->user_id === $userId && $target->branch_id === $branchId)
            ?? $targets->first(fn (CommissionTarget $target) => $target->user_id === $userId && $target->branch_id === null);
    }

    private function serviceCommissionValue(array $row): float
    {
        $saved = (float) ($row['item']->commission_amount ?? 0);

        if ($saved > 0) {
            return $saved;
        }

        $branch = $row['payment']->branch ?? null;
        $service = $row['item']->appointmentService?->service;
        $amount = (float) ($row['item']->total ?? 0);

        if (! $branch || $amount <= 0 || $service?->commission_enabled === false) {
            return 0.0;
        }

        $percent = (float) $branch->service_commission_percent;
        $minimum = (float) $branch->service_commission_min_sale;

        return $percent > 0 && $amount >= $minimum ? round($amount * $percent / 100, 2) : 0.0;
    }

    private function productCommissionValue(array $row): float
    {
        $saved = (float) ($row['item']->commission_amount ?? 0);

        if ($saved > 0) {
            return $saved;
        }

        $branch = isset($row['sale']) ? ($row['sale']->branch ?? null) : ($row['payment']->branch ?? null);
        $product = $row['item']->product instanceof InventoryProduct ? $row['item']->product : null;
        $amount = (float) ($row['item']->total ?? 0);

        if (! $branch || $amount <= 0 || $product?->commission_enabled === false) {
            return 0.0;
        }

        $percent = (float) $branch->product_commission_percent;
        $minimum = (float) $branch->product_commission_min_sale;

        return $percent > 0 && $amount >= $minimum ? round($amount * $percent / 100, 2) : 0.0;
    }

    private function range(): array
    {
        return [
            Carbon::parse($this->dateFrom ?: now()->startOfMonth())->startOfDay(),
            Carbon::parse($this->dateTo ?: now())->endOfDay(),
        ];
    }

    private function periodOptions(): array
    {
        return [
            'weekly' => 'Semanal',
            'biweekly' => 'Quincenal',
            'monthly' => 'Mensual',
        ];
    }

    private function resetTargetForm(): void
    {
        $this->reset(['editingTargetId', 'targetUserId', 'targetBranchId']);
        $this->targetPeriodType = 'monthly';
        $this->targetMinimumSales = '0';
        $this->targetMinimumCommission = '0';
        $this->targetStatus = 'active';
        $this->resetErrorBag();
    }

    private function company(): Company
    {
        return Auth::user()->companies()->firstOrFail();
    }
}
