<?php

namespace Tests\Feature\Dashboard;

use App\Livewire\Dashboard\HomeSummary;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\BusinessType;
use App\Models\Client;
use App\Models\Company;
use App\Models\Expense;
use App\Models\ExpenseType;
use App\Models\InventoryProduct;
use App\Models\InventoryProductBatch;
use App\Models\TreatmentPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class HomeSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_branch_summary_metrics(): void
    {
        Carbon::setTestNow('2026-08-14 09:00:00');
        [$user, $company, $branch] = $this->companyContext();
        $client = Client::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'full_name' => 'Marcela Roldon',
            'phone' => '70000001',
        ]);
        $appointment = Appointment::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'client_id' => $client->id,
            'scheduled_at' => '2026-08-14 10:30:00',
            'duration_minutes' => 60,
            'status' => 'scheduled',
        ]);
        $appointment->services()->create([
            'name' => 'Limpieza facial',
            'price' => 120,
        ]);
        $payment = TreatmentPayment::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'client_id' => $client->id,
            'appointment_id' => $appointment->id,
            'amount' => 150,
            'method' => 'mixed',
            'paid_at' => now(),
        ]);
        $payment->splits()->createMany([
            ['method' => 'cash', 'amount' => 100],
            ['method' => 'qr', 'amount' => 50],
        ]);
        $type = ExpenseType::create([
            'company_id' => $company->id,
            'name' => 'Almuerzo',
            'slug' => 'almuerzo',
            'default_source' => 'cashbox',
        ]);
        Expense::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'expense_type_id' => $type->id,
            'created_by_user_id' => $user->id,
            'source' => 'cashbox',
            'amount' => 20,
            'spent_at' => now(),
        ]);
        $product = InventoryProduct::create([
            'company_id' => $company->id,
            'code' => 'CRE-001',
            'name' => 'Crema facial',
            'unit_name' => 'unidad',
            'minimum_stock' => 5,
            'purchase_cost' => 10,
            'status' => 'active',
        ]);
        InventoryProductBatch::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'inventory_product_id' => $product->id,
            'lot_code' => 'L-001',
            'expires_at' => now()->addDays(12),
            'initial_quantity' => 2,
            'current_quantity' => 2,
            'unit_cost' => 10,
            'status' => 'active',
        ]);

        $this->actingAs($user);
        session(['active_branch_id' => $branch->id]);

        Livewire::test(HomeSummary::class)
            ->assertSee('Sucursal Centro')
            ->assertSee('Marcela Roldon')
            ->assertSee('Limpieza facial')
            ->assertSee('Bs 130.00')
            ->assertSee('Crema facial')
            ->assertSee('L-001')
            ->assertSee('Almuerzo');
    }

    private function companyContext(): array
    {
        $user = User::factory()->create(['email' => 'dashboard@rumika.test']);
        $company = Company::create([
            'name' => 'Rumika Demo',
            'slug' => 'rumika-demo',
        ]);
        $businessType = BusinessType::create([
            'name' => 'Clinica',
            'slug' => 'clinica',
            'enabled_modules' => ['agenda', 'clientes', 'inventario'],
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
