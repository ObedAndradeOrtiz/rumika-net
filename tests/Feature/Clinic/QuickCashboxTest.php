<?php

namespace Tests\Feature\Clinic;

use App\Livewire\Clinic\QuickCashbox;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\BusinessType;
use App\Models\Client;
use App\Models\Company;
use App\Models\ExpenseType;
use App\Models\TreatmentPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class QuickCashboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashbox_opens_closes_once_and_assigns_mixed_payments_to_services_then_products(): void
    {
        [$admin, $company, $branch] = $this->companyContext('rumika-demo');
        $client = Client::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'full_name' => 'Obed Andrade',
        ]);
        $appointment = Appointment::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'client_id' => $client->id,
            'scheduled_at' => '2026-08-02 17:00:00',
            'duration_minutes' => 60,
            'status' => 'scheduled',
        ]);
        $payment = TreatmentPayment::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'client_id' => $client->id,
            'appointment_id' => $appointment->id,
            'received_by_user_id' => $admin->id,
            'amount' => 100,
            'method' => 'mixed',
            'paid_at' => '2026-08-02 17:10:00',
        ]);
        $payment->splits()->create(['method' => 'cash', 'amount' => 90]);
        $payment->splits()->create(['method' => 'qr', 'amount' => 10]);
        $payment->items()->create([
            'type' => 'service',
            'name' => 'Limpieza facial',
            'quantity' => 1,
            'unit_price' => 50,
            'total' => 50,
        ]);
        $payment->items()->create([
            'type' => 'product',
            'name' => 'Mascarilla facial',
            'quantity' => 1,
            'unit_price' => 50,
            'total' => 50,
        ]);
        $expenseType = ExpenseType::create([
            'company_id' => $company->id,
            'name' => 'Insumos caja',
            'slug' => 'insumos-caja',
            'default_source' => 'cashbox',
            'requires_staff' => false,
            'status' => 'active',
        ]);
        $company->expenses()->create([
            'branch_id' => $branch->id,
            'expense_type_id' => $expenseType->id,
            'created_by_user_id' => $admin->id,
            'source' => 'cashbox',
            'amount' => 20,
            'spent_at' => '2026-08-02',
            'reference' => 'Recibo 001',
        ]);

        $this->actingAs($admin);
        session(['active_branch_id' => $branch->id]);
        Carbon::setTestNow('2026-08-02 08:00:00');

        $component = Livewire::test(QuickCashbox::class)
            ->call('open')
            ->set('selectedDate', '2026-08-02')
            ->set('openingAmount', '25')
            ->call('openCashbox')
            ->assertSee('Caja abierta')
            ->assertSee('Servicios')
            ->assertSee('Productos')
            ->assertSee('Gastos')
            ->assertSee('Limpieza facial')
            ->assertSee('Efectivo')
            ->set('paymentMethodFilter', 'qr')
            ->assertSee('Sin ingresos de servicios')
            ->set('paymentMethodFilter', '')
            ->call('setHistoryTab', 'products')
            ->assertSee('Mascarilla facial')
            ->assertSee('Mixto')
            ->call('setHistoryTab', 'expenses')
            ->assertSee('Insumos caja')
            ->assertSee('Gasto de caja')
            ->call('openCashbox')
            ->assertSee('Ya tienes una caja abierta');

        Carbon::setTestNow('2026-08-02 18:00:00');

        $component
            ->call('closeCashbox')
            ->assertSee('Caja cerrada')
            ->call('closeCashbox')
            ->assertSee('Primero debes abrir la caja')
            ->call('previewPrint')
            ->assertSee('Limpieza facial')
            ->assertSee('Mascarilla facial')
            ->assertSee('Cierre de caja')
            ->assertViewHas('printSummary');

        $summary = $component->viewData('printSummary');

        $this->assertSame(50.0, $summary['services']['cash']);
        $this->assertSame(0.0, $summary['services']['qr']);
        $this->assertSame(40.0, $summary['products']['cash']);
        $this->assertSame(10.0, $summary['products']['qr']);
        $this->assertDatabaseHas('cashbox_sessions', [
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'business_date' => '2026-08-02 00:00:00',
            'status' => 'closed',
            'shift_number' => 1,
            'opening_amount' => 25,
            'opened_by_user_id' => $admin->id,
            'closed_by_user_id' => $admin->id,
        ]);
        $this->assertDatabaseCount('cashbox_tickets', 2);
        $this->assertDatabaseHas('cashbox_tickets', [
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'type' => 'session_close',
            'reprint_count' => 1,
        ]);
        Carbon::setTestNow();
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
            'enabled_modules' => ['agenda', 'clientes', 'historial'],
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
