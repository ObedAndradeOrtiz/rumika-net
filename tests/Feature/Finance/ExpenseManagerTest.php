<?php

namespace Tests\Feature\Finance;

use App\Livewire\Clinic\CashboxSummary;
use App\Livewire\Finance\ExpenseManager;
use App\Models\Branch;
use App\Models\BusinessType;
use App\Models\Client;
use App\Models\Company;
use App\Models\Expense;
use App\Models\ExpenseType;
use App\Models\Role;
use App\Models\TreatmentPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExpenseManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_expenses_have_types_cashbox_external_and_monthly_staff_totals(): void
    {
        [$admin, $company, $branch] = $this->companyContext('rumika-demo');
        $staff = User::factory()->create([
            'name' => 'Ana Cabina',
            'email' => 'ana-cabina@rumika.test',
        ]);
        $company->users()->attach($staff->id, [
            'role' => 'staff',
            'joined_at' => now(),
        ]);
        $branch->users()->attach($staff->id, [
            'assigned_at' => now(),
        ]);

        $this->actingAs($admin);
        session(['active_branch_id' => $branch->id]);

        Livewire::test(ExpenseManager::class)
            ->call('setActiveTab', 'types')
            ->assertSee('Adelanto al personal')
            ->assertSee('Gasto externo');

        $staffType = ExpenseType::query()
            ->where('company_id', $company->id)
            ->where('name', 'Adelanto al personal')
            ->firstOrFail();
        $externalType = ExpenseType::query()
            ->where('company_id', $company->id)
            ->where('name', 'Gasto externo')
            ->firstOrFail();
        $cashboxType = ExpenseType::query()
            ->where('company_id', $company->id)
            ->where('name', 'Gasto de caja')
            ->firstOrFail();

        Livewire::test(ExpenseManager::class)
            ->call('createExpense')
            ->set('expenseTypeId', $cashboxType->id)
            ->assertSee('Responsable')
            ->assertDontSee('Sin personal')
            ->set('expenseTypeId', $staffType->id)
            ->assertSee('Personal')
            ->assertSee('Seleccionar personal');

        Livewire::test(ExpenseManager::class)
            ->set('expenseTypeId', $staffType->id)
            ->set('expenseAmount', '150')
            ->set('expenseSpentAt', now()->toDateString())
            ->call('saveExpense')
            ->assertHasErrors(['staffUserId'])
            ->set('staffUserId', $staff->id)
            ->call('saveExpense')
            ->assertHasNoErrors()
            ->set('expenseTypeId', $externalType->id)
            ->set('expenseSource', 'external')
            ->set('expenseAmount', '80')
            ->set('expenseSpentAt', now()->toDateString())
            ->call('saveExpense')
            ->assertHasNoErrors()
            ->call('setActiveTab', 'staff')
            ->assertSee('Ana Cabina')
            ->assertSee('Bs 150.00');

        $this->assertDatabaseHas('expenses', [
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'expense_type_id' => $staffType->id,
            'staff_user_id' => $staff->id,
            'created_by_user_id' => $admin->id,
            'source' => 'cashbox',
            'amount' => 150,
        ]);
        $this->assertDatabaseHas('expenses', [
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'expense_type_id' => $externalType->id,
            'source' => 'external',
            'amount' => 80,
        ]);
    }

    public function test_cashbox_summary_deducts_only_cashbox_expenses_for_the_active_branch(): void
    {
        [$admin, $company, $branch] = $this->companyContext('rumika-demo');
        $otherBranch = Branch::create([
            'company_id' => $company->id,
            'business_type_id' => $branch->business_type_id,
            'name' => 'Sucursal Norte',
            'slug' => 'sucursal-norte',
            'status' => 'active',
        ]);
        $type = ExpenseType::create([
            'company_id' => $company->id,
            'name' => 'Gasto de caja',
            'slug' => 'gasto-de-caja',
            'default_source' => 'cashbox',
            'requires_staff' => false,
            'status' => 'active',
        ]);

        $expense = Expense::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'expense_type_id' => $type->id,
            'created_by_user_id' => $admin->id,
            'source' => 'cashbox',
            'amount' => 25,
            'spent_at' => now()->toDateString(),
        ]);
        $client = Client::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'full_name' => 'Cliente Registro',
        ]);
        $payment = TreatmentPayment::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'client_id' => $client->id,
            'received_by_user_id' => $admin->id,
            'amount' => 50,
            'method' => 'cash',
            'paid_at' => now(),
        ]);
        $payment->splits()->create(['method' => 'cash', 'amount' => 50]);
        $payment->items()->create([
            'type' => 'service',
            'name' => 'Limpieza facial',
            'quantity' => 1,
            'unit_price' => 50,
            'charged_total' => 50,
            'total' => 50,
        ]);
        Expense::create([
            'company_id' => $company->id,
            'branch_id' => $otherBranch->id,
            'expense_type_id' => $type->id,
            'created_by_user_id' => $admin->id,
            'source' => 'cashbox',
            'amount' => 99,
            'spent_at' => now()->toDateString(),
        ]);
        Expense::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'expense_type_id' => $type->id,
            'created_by_user_id' => $admin->id,
            'source' => 'external',
            'amount' => 40,
            'spent_at' => now()->toDateString(),
        ]);

        $this->actingAs($admin);
        session(['active_branch_id' => $branch->id]);

        Livewire::test(CashboxSummary::class)
            ->assertViewHas('cashboxExpenseTotal', 25.0)
            ->assertViewHas('netCashTotal', 25.0)
            ->assertSee('Servicios')
            ->assertSee('Productos')
            ->assertSee('Gastos')
            ->assertSee('Limpieza facial')
            ->assertSee('Editar')
            ->assertSee('Eliminar')
            ->call('setHistoryTab', 'expenses')
            ->assertSee('Gasto de caja')
            ->call('editExpense', $expense->id)
            ->set('expenseAmount', '30')
            ->call('saveExpense')
            ->assertHasNoErrors()
            ->assertViewHas('cashboxExpenseTotal', 30.0)
            ->call('confirmDeletePayment', $payment->id)
            ->call('deletePayment', $payment->id)
            ->assertDontSee('Limpieza facial')
            ->assertSee('Gastos caja')
            ->assertSee('Caja neta');

        $this->assertDatabaseMissing('treatment_payments', ['id' => $payment->id]);
        $this->assertDatabaseHas('expenses', ['id' => $expense->id, 'amount' => 30]);

        $staff = User::factory()->create(['email' => 'solo-lectura@rumika.test']);
        $company->users()->attach($staff->id, [
            'role' => 'member',
            'joined_at' => now(),
        ]);
        $branch->users()->attach($staff->id, [
            'assigned_at' => now(),
        ]);
        $this->actingAs($staff);

        Livewire::test(CashboxSummary::class)
            ->call('setHistoryTab', 'expenses')
            ->assertDontSee('Editar')
            ->assertDontSee('Eliminar');

        $this->get(route('settings.records'))
            ->assertOk()
            ->assertSee('Registros');
    }

    public function test_cashbox_summary_reads_expenses_by_month_not_only_selected_day(): void
    {
        [$admin, $company, $branch] = $this->companyContext('rumika-gastos-mensuales');
        $type = ExpenseType::create([
            'company_id' => $company->id,
            'name' => 'Gasto de caja',
            'slug' => 'gasto-de-caja',
            'default_source' => 'cashbox',
            'requires_staff' => false,
            'status' => 'active',
        ]);

        Expense::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'expense_type_id' => $type->id,
            'created_by_user_id' => $admin->id,
            'source' => 'cashbox',
            'amount' => 25,
            'spent_at' => '2026-08-02',
            'reference' => 'Gasto mensual',
        ]);
        Expense::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'expense_type_id' => $type->id,
            'created_by_user_id' => $admin->id,
            'source' => 'cashbox',
            'amount' => 99,
            'spent_at' => '2026-09-02',
            'reference' => 'Otro mes',
        ]);

        $this->actingAs($admin);
        session(['active_branch_id' => $branch->id]);

        Livewire::test(CashboxSummary::class)
            ->set('selectedDate', '2026-08-14')
            ->call('setHistoryTab', 'expenses')
            ->assertViewHas('cashboxExpenseTotal', 25.0)
            ->assertSee('Gasto mensual')
            ->assertDontSee('Otro mes');
    }

    public function test_expense_history_can_filter_by_date_month_or_all(): void
    {
        [$admin, $company, $branch] = $this->companyContext('rumika-filtros-gastos');
        $type = ExpenseType::create([
            'company_id' => $company->id,
            'name' => 'Gasto de caja',
            'slug' => 'gasto-de-caja',
            'default_source' => 'cashbox',
            'requires_staff' => false,
            'status' => 'active',
        ]);

        Expense::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'expense_type_id' => $type->id,
            'created_by_user_id' => $admin->id,
            'source' => 'cashbox',
            'amount' => 13,
            'spent_at' => '2026-08-14',
            'reference' => 'Almuerzo',
        ]);
        Expense::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'expense_type_id' => $type->id,
            'created_by_user_id' => $admin->id,
            'source' => 'cashbox',
            'amount' => 20,
            'spent_at' => '2026-08-10',
            'reference' => 'Limpieza',
        ]);
        Expense::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'expense_type_id' => $type->id,
            'created_by_user_id' => $admin->id,
            'source' => 'cashbox',
            'amount' => 30,
            'spent_at' => '2026-09-01',
            'reference' => 'Septiembre',
        ]);

        $this->actingAs($admin);
        session(['active_branch_id' => $branch->id]);

        Livewire::test(ExpenseManager::class)
            ->call('setActiveTab', 'history')
            ->set('periodMode', 'date')
            ->set('selectedDate', '2026-08-14')
            ->assertSee('Almuerzo')
            ->assertDontSee('Limpieza')
            ->assertDontSee('Septiembre')
            ->assertViewHas('summary', fn (array $summary) => (float) $summary['total'] === 13.0)
            ->set('periodMode', 'month')
            ->set('month', '2026-08')
            ->assertSee('Almuerzo')
            ->assertSee('Limpieza')
            ->assertDontSee('Septiembre')
            ->assertViewHas('summary', fn (array $summary) => (float) $summary['total'] === 33.0)
            ->set('periodMode', 'all')
            ->assertSee('Almuerzo')
            ->assertSee('Limpieza')
            ->assertSee('Septiembre')
            ->assertViewHas('summary', fn (array $summary) => (float) $summary['total'] === 63.0);
    }

    public function test_branch_administrator_can_delete_records_even_when_company_role_is_member(): void
    {
        [$owner, $company, $branch] = $this->companyContext('rumika-registros-admin-sucursal');
        $branchAdmin = User::factory()->create(['email' => 'admin-sucursal@rumika.test']);
        $role = Role::create([
            'company_id' => $company->id,
            'name' => 'Administrador',
            'slug' => 'administrador',
            'scope' => 'company',
            'permissions' => [],
            'is_system' => true,
        ]);
        $type = ExpenseType::create([
            'company_id' => $company->id,
            'name' => 'Gasto de caja',
            'slug' => 'gasto-de-caja',
            'default_source' => 'cashbox',
            'requires_staff' => false,
            'status' => 'active',
        ]);
        $expense = Expense::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'expense_type_id' => $type->id,
            'created_by_user_id' => $owner->id,
            'source' => 'cashbox',
            'amount' => 13,
            'spent_at' => now()->toDateString(),
        ]);

        $company->users()->attach($branchAdmin->id, [
            'role' => 'member',
            'joined_at' => now(),
        ]);
        $branch->users()->attach($branchAdmin->id, [
            'role_id' => $role->id,
            'assigned_at' => now(),
        ]);

        $this->actingAs($branchAdmin);
        session(['active_branch_id' => $branch->id]);

        Livewire::test(CashboxSummary::class, ['context' => 'records'])
            ->call('setHistoryTab', 'expenses')
            ->assertSee('Eliminar')
            ->call('confirmDeleteExpense', $expense->id)
            ->call('deleteExpense', $expense->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }

    private function companyContext(string $slug): array
    {
        $user = User::factory()->create(['email' => "{$slug}@rumika.test"]);
        $company = Company::create([
            'name' => str($slug)->headline()->toString(),
            'slug' => $slug,
        ]);
        $businessType = BusinessType::create([
            'name' => "Clinica {$slug}",
            'slug' => "clinica-{$slug}",
            'enabled_modules' => ['agenda', 'clientes', 'historial', 'inventario', 'finanzas'],
        ]);
        $branch = Branch::create([
            'company_id' => $company->id,
            'business_type_id' => $businessType->id,
            'name' => 'Sucursal Centro',
            'slug' => 'sucursal-centro',
            'status' => 'active',
        ]);

        $company->users()->attach($user->id, [
            'role' => 'owner',
            'joined_at' => now(),
        ]);
        $branch->users()->attach($user->id, [
            'assigned_at' => now(),
        ]);

        return [$user, $company, $branch];
    }
}
