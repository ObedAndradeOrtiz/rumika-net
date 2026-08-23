<?php

namespace App\Livewire\Finance;

use App\Models\Branch;
use App\Models\Company;
use App\Support\ActiveBranch;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DebtManager extends Component
{
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $branchFilter = '';
    public string $typeFilter = '';
    public string $search = '';

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
        $rows = $this->debtRows($company);

        return view('livewire.finance.debt-manager', [
            'rows' => $rows,
            'branches' => $branches,
            'activeBranch' => $activeBranch,
            'currency' => Money::symbol($activeBranch),
            'totalDebt' => $rows->sum('balance'),
            'serviceDebt' => $rows->where('type', 'service')->sum('balance'),
            'productDebt' => $rows->where('type', 'product')->sum('balance'),
        ]);
    }

    private function debtRows(Company $company)
    {
        $search = trim($this->search);

        return $company->clientCharges()
            ->with(['client', 'soldBy'])
            ->whereIn('status', ['pending', 'partial'])
            ->where('balance_amount', '>', 0)
            ->whereBetween('charged_at', [
                Carbon::parse($this->dateFrom ?: now()->startOfMonth())->startOfDay(),
                Carbon::parse($this->dateTo ?: now())->endOfDay(),
            ])
            ->when($this->branchFilter !== '', fn ($query) => $query->where('branch_id', $this->branchFilter))
            ->when($this->typeFilter !== '', fn ($query) => $query->where('type', $this->typeFilter))
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $term = "%{$search}%";

                $query->where('name', 'like', $term)
                    ->orWhereHas('client', fn ($clientQuery) => $clientQuery
                        ->where('full_name', 'like', $term)
                        ->orWhere('identity_document', 'like', $term)
                        ->orWhere('phone', 'like', $term));
            }))
            ->latest('charged_at')
            ->get()
            ->map(fn ($charge) => [
                'id' => $charge->id,
                'type' => $charge->type,
                'client' => $charge->client?->full_name ?? 'Sin cliente',
                'phone' => $charge->client?->phone ?? 'Sin telefono',
                'name' => $charge->name,
                'date' => $charge->charged_at?->format('d/m/Y'),
                'total' => (float) $charge->total_amount,
                'paid' => (float) $charge->paid_amount,
                'balance' => (float) $charge->balance_amount,
                'responsible' => $charge->soldBy?->name ?? 'Sin responsable',
                'status' => $charge->status === 'partial' ? 'Parcial' : 'Pendiente',
            ]);
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
