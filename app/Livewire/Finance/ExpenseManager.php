<?php

namespace App\Livewire\Finance;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Expense;
use App\Models\ExpenseType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class ExpenseManager extends Component
{
    use WithPagination;

    public string $activeTab = 'register';
    public string $search = '';
    public string $dateFrom = '';
    public string $dateTo = '';

    public bool $showExpenseModal = false;
    public bool $showTypeModal = false;
    public ?int $editingExpenseId = null;
    public ?int $editingTypeId = null;
    public ?int $confirmingExpenseDeleteId = null;
    public ?int $confirmingTypeDeleteId = null;

    public ?int $expenseTypeId = null;
    public ?int $staffUserId = null;
    public string $expenseSource = 'cashbox';
    public string $expenseAmount = '';
    public string $expenseSpentAt = '';
    public string $expenseReference = '';
    public string $expenseDescription = '';

    public string $typeName = '';
    public string $typeDefaultSource = 'cashbox';
    public bool $typeRequiresStaff = false;
    public string $typeDescription = '';
    public string $typeStatus = 'active';

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->expenseSpentAt = now()->format('Y-m-d');
        $this->ensureDefaultTypes();
    }

    public function setActiveTab(string $tab): void
    {
        if (! in_array($tab, ['register', 'history', 'types', 'staff'], true)) {
            return;
        }

        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function createExpense(): void
    {
        $this->resetExpenseForm();
        $this->showExpenseModal = true;
    }

    public function editExpense(int $expenseId): void
    {
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
        $company = $this->company();
        $branch = $this->activeBranch();
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

        $expense = $this->editingExpenseId
            ? $company->expenses()->whereKey($this->editingExpenseId)->firstOrFail()
            : new Expense([
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'created_by_user_id' => Auth::id(),
            ]);

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

    public function confirmDeleteExpense(int $expenseId): void
    {
        $this->confirmingExpenseDeleteId = $expenseId;
    }

    public function deleteExpense(int $expenseId): void
    {
        $this->company()->expenses()->whereKey($expenseId)->firstOrFail()->delete();
        $this->confirmingExpenseDeleteId = null;
    }

    public function createType(): void
    {
        $this->resetTypeForm();
        $this->showTypeModal = true;
    }

    public function editType(int $typeId): void
    {
        $type = $this->company()->expenseTypes()->whereKey($typeId)->firstOrFail();

        $this->editingTypeId = $type->id;
        $this->typeName = $type->name;
        $this->typeDefaultSource = $type->default_source;
        $this->typeRequiresStaff = $type->requires_staff;
        $this->typeDescription = $type->description ?? '';
        $this->typeStatus = $type->status;
        $this->showTypeModal = true;
    }

    public function saveType(): void
    {
        $company = $this->company();

        $validated = $this->validate([
            'typeName' => ['required', 'string', 'max:120'],
            'typeDefaultSource' => ['required', 'in:cashbox,external'],
            'typeRequiresStaff' => ['boolean'],
            'typeDescription' => ['nullable', 'string', 'max:180'],
            'typeStatus' => ['required', 'in:active,inactive'],
        ]);
        $type = $this->editingTypeId
            ? $company->expenseTypes()->whereKey($this->editingTypeId)->firstOrFail()
            : new ExpenseType(['company_id' => $company->id]);
        $slug = $this->uniqueTypeSlug($company, $validated['typeName'], $type->id);

        $type->fill([
            'name' => $validated['typeName'],
            'slug' => $slug,
            'default_source' => $validated['typeDefaultSource'],
            'requires_staff' => $validated['typeRequiresStaff'],
            'description' => $validated['typeDescription'] ?: null,
            'status' => $validated['typeStatus'],
        ]);
        $type->save();

        $this->showTypeModal = false;
        $this->resetTypeForm();
    }

    public function confirmDeleteType(int $typeId): void
    {
        $this->resetErrorBag();
        $this->confirmingTypeDeleteId = $typeId;
    }

    public function deleteType(int $typeId): void
    {
        $type = $this->company()->expenseTypes()->whereKey($typeId)->firstOrFail();

        if ($type->expenses()->exists()) {
            $this->addError('deleteType', 'No se puede eliminar porque ya tiene gastos registrados.');

            return;
        }

        $type->delete();
        $this->confirmingTypeDeleteId = null;
    }

    public function render()
    {
        $company = $this->company();
        $branch = $this->activeBranch();
        $range = $this->periodRange();
        $search = trim($this->search);
        $types = $company->expenseTypes()->orderBy('name')->get();
        $activeTypes = $types->where('status', 'active')->values();
        $selectedExpenseType = $activeTypes->firstWhere('id', $this->expenseTypeId)
            ?? $types->firstWhere('id', $this->expenseTypeId);
        $expensesQuery = $company->expenses()
            ->with(['type', 'staffUser', 'createdBy', 'branch'])
            ->where('branch_id', $branch->id)
            ->when($range, fn ($query) => $query->whereBetween('spent_at', $range))
            ->when($search !== '', fn ($query) => $query->where(fn ($nested) => $nested
                ->where('reference', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhereHas('type', fn ($typeQuery) => $typeQuery->where('name', 'like', "%{$search}%"))
                ->orWhereHas('staffUser', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%"))
                ->orWhereHas('createdBy', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%"))));
        $allExpenses = (clone $expensesQuery)->get();

        return view('livewire.finance.expense-manager', [
            'branch' => $branch,
            'types' => $types,
            'activeTypes' => $activeTypes,
            'selectedExpenseTypeRequiresStaff' => (bool) $selectedExpenseType?->requires_staff,
            'expenseResponsibleName' => Auth::user()->name,
            'staffUsers' => $company->users()->orderBy('name')->get(),
            'expenses' => $expensesQuery->latest('spent_at')->paginate(15),
            'periodLabel' => $this->periodLabel(),
            'summary' => [
                'cashbox' => (float) $allExpenses->where('source', 'cashbox')->sum('amount'),
                'external' => (float) $allExpenses->where('source', 'external')->sum('amount'),
                'total' => (float) $allExpenses->sum('amount'),
                'staff' => (float) $allExpenses->whereNotNull('staff_user_id')->sum('amount'),
            ],
            'staffTotals' => $allExpenses
                ->whereNotNull('staff_user_id')
                ->groupBy('staff_user_id')
                ->map(fn ($items) => [
                    'name' => $items->first()->staffUser?->name ?? 'Sin personal',
                    'total' => (float) $items->sum('amount'),
                    'count' => $items->count(),
                ])
                ->sortBy('name')
                ->values(),
        ]);
    }

    private function ensureDefaultTypes(): void
    {
        $company = $this->company();
        collect([
            ['name' => 'Gasto de caja', 'default_source' => 'cashbox', 'requires_staff' => false, 'description' => 'Salida de dinero desde caja diaria.'],
            ['name' => 'Gasto externo', 'default_source' => 'external', 'requires_staff' => false, 'description' => 'Gasto general que no afecta caja.'],
            ['name' => 'Adelanto al personal', 'default_source' => 'cashbox', 'requires_staff' => true, 'description' => 'Anticipo mensual asignado a un trabajador.'],
            ['name' => 'Pago al personal', 'default_source' => 'cashbox', 'requires_staff' => true, 'description' => 'Pago mensual o parcial de personal.'],
            ['name' => 'Liquidacion al personal', 'default_source' => 'external', 'requires_staff' => true, 'description' => 'Pago de liquidacion o cierre laboral.'],
        ])->each(function (array $type) use ($company) {
            $company->expenseTypes()->firstOrCreate(
                ['slug' => Str::slug($type['name'])],
                $type + ['status' => 'active'],
            );
        });
    }

    private function periodRange(): ?array
    {
        $from = Carbon::parse($this->dateFrom ?: now()->startOfMonth()->format('Y-m-d'))->startOfDay();
        $to = Carbon::parse($this->dateTo ?: now()->format('Y-m-d'))->endOfDay();

        if ($to->lt($from)) {
            $to = $from->copy()->endOfDay();
            $this->dateTo = $from->toDateString();
        }

        return [$from, $to];
    }

    private function periodLabel(): string
    {
        return 'del rango';
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

    private function uniqueTypeSlug(Company $company, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'tipo-gasto';
        $slug = $base;
        $counter = 2;

        while ($company->expenseTypes()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function resetExpenseForm(): void
    {
        $this->reset(['editingExpenseId', 'expenseTypeId', 'staffUserId', 'expenseAmount', 'expenseReference', 'expenseDescription']);
        $this->expenseSource = 'cashbox';
        $this->expenseSpentAt = now()->format('Y-m-d');
        $this->resetErrorBag();
    }

    private function resetTypeForm(): void
    {
        $this->reset(['editingTypeId', 'typeName', 'typeDescription']);
        $this->typeDefaultSource = 'cashbox';
        $this->typeRequiresStaff = false;
        $this->typeStatus = 'active';
        $this->resetErrorBag();
    }
}
