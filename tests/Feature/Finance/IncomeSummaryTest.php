<?php

namespace Tests\Feature\Finance;

use App\Livewire\Finance\IncomeSummary;
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

class IncomeSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_income_summary_for_all_company_branches(): void
    {
        [$admin, $company, $branch] = $this->companyContext('rumika-resumen-financiero');
        $otherBranch = Branch::create([
            'company_id' => $company->id,
            'business_type_id' => $branch->business_type_id,
            'name' => 'Sucursal Norte',
            'slug' => 'sucursal-norte',
            'status' => 'active',
        ]);
        $client = Client::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'full_name' => 'Cliente Finanzas',
        ]);
        $type = ExpenseType::create([
            'company_id' => $company->id,
            'name' => 'Gasto de caja',
            'slug' => 'gasto-de-caja',
            'default_source' => 'cashbox',
            'requires_staff' => false,
            'status' => 'active',
        ]);

        $this->payment($company, $branch, $client, $admin, 160, [
            ['type' => 'service', 'name' => 'Consulta', 'total' => 100],
            ['type' => 'product', 'name' => 'Crema', 'total' => 60],
        ], ['cash' => 110, 'qr' => 50], '2026-08-10 10:30:00');
        $this->payment($company, $otherBranch, $client, $admin, 90, [
            ['type' => 'service', 'name' => 'Limpieza facial', 'total' => 90],
        ], ['cash' => 90], '2026-08-11 12:00:00');
        Expense::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'expense_type_id' => $type->id,
            'created_by_user_id' => $admin->id,
            'source' => 'cashbox',
            'amount' => 25,
            'spent_at' => '2026-08-10',
        ]);
        Expense::create([
            'company_id' => $company->id,
            'branch_id' => $otherBranch->id,
            'expense_type_id' => $type->id,
            'created_by_user_id' => $admin->id,
            'source' => 'external',
            'amount' => 30,
            'spent_at' => '2026-08-11',
        ]);

        $this->actingAs($admin);

        Livewire::test(IncomeSummary::class)
            ->set('dateFrom', '2026-08-01')
            ->set('dateTo', '2026-08-31')
            ->assertSee('Resumen de ingresos')
            ->assertSee('Sucursal Centro')
            ->assertSee('Sucursal Norte')
            ->assertSee('Bs 190.00')
            ->assertSee('Bs 60.00')
            ->assertSee('Bs 55.00')
            ->assertSee('Bs 195.00');
    }

    public function test_financial_summary_requires_admin_or_explicit_role_permission(): void
    {
        [$owner, $company, $branch] = $this->companyContext('rumika-resumen-permisos');
        $plainUser = User::factory()->create(['email' => 'sin-resumen@rumika.test']);
        $authorizedUser = User::factory()->create(['email' => 'con-resumen@rumika.test']);
        $plainRole = Role::create([
            'company_id' => $company->id,
            'name' => 'Recepcion',
            'slug' => 'recepcion',
            'scope' => 'company',
            'permissions' => ['agenda' => ['view']],
            'is_system' => false,
        ]);
        $summaryRole = Role::create([
            'company_id' => $company->id,
            'name' => 'Analista',
            'slug' => 'analista',
            'scope' => 'company',
            'permissions' => ['resumen_financiero' => ['view']],
            'is_system' => false,
        ]);

        foreach ([$plainUser, $authorizedUser] as $user) {
            $company->users()->attach($user->id, [
                'role' => 'member',
                'joined_at' => now(),
            ]);
        }
        $branch->users()->attach($plainUser->id, [
            'role_id' => $plainRole->id,
            'assigned_at' => now(),
        ]);
        $branch->users()->attach($authorizedUser->id, [
            'role_id' => $summaryRole->id,
            'assigned_at' => now(),
        ]);

        $this->actingAs($plainUser);
        $this->get(route('finance.summary'))->assertForbidden();

        $this->actingAs($authorizedUser);
        $this->get(route('finance.summary'))
            ->assertOk()
            ->assertSee('Resumen de ingresos');

        $this->actingAs($owner);
        $this->get(route('finance.summary'))->assertOk();
    }

    private function payment(Company $company, Branch $branch, Client $client, User $user, float $amount, array $items, array $splits, string $paidAt): void
    {
        $payment = TreatmentPayment::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'client_id' => $client->id,
            'received_by_user_id' => $user->id,
            'amount' => $amount,
            'method' => count($splits) > 1 ? 'mixed' : array_key_first($splits),
            'paid_at' => $paidAt,
        ]);

        foreach ($splits as $method => $splitAmount) {
            $payment->splits()->create([
                'method' => $method,
                'amount' => $splitAmount,
            ]);
        }

        foreach ($items as $item) {
            $payment->items()->create([
                'type' => $item['type'],
                'name' => $item['name'],
                'quantity' => 1,
                'unit_price' => $item['total'],
                'charged_total' => $item['total'],
                'total' => $item['total'],
            ]);
        }
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
            'enabled_modules' => ['agenda', 'clientes', 'inventario', 'finanzas'],
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
