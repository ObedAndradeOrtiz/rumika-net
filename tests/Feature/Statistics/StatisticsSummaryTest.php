<?php

namespace Tests\Feature\Statistics;

use App\Livewire\Statistics\StatisticsSummary;
use App\Models\Appointment;
use App\Models\BusinessType;
use App\Models\Client;
use App\Models\Company;
use App\Models\Expense;
use App\Models\ExpenseType;
use App\Models\Service;
use App\Models\TreatmentPayment;
use App\Models\TreatmentPaymentItem;
use App\Models\TreatmentPaymentSplit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StatisticsSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_statistics_summary_shows_attendance_income_and_expenses(): void
    {
        $user = User::factory()->create();
        $seller = User::factory()->create(['name' => 'Vendedora Principal']);
        $company = Company::create(['name' => 'Rumika Stats', 'slug' => 'rumika-stats']);
        $type = BusinessType::create(['name' => 'Clinica', 'slug' => 'clinica']);
        $branch = $company->branches()->create([
            'business_type_id' => $type->id,
            'name' => 'Sucursal Centro',
            'slug' => 'sucursal-centro',
            'status' => 'active',
        ]);
        $company->users()->attach($user->id, ['role' => 'owner', 'joined_at' => now()]);
        $company->users()->attach($seller->id, ['role' => 'staff', 'joined_at' => now()]);
        $branch->users()->attach($user->id, ['assigned_at' => now()]);
        $branch->users()->attach($seller->id, ['assigned_at' => now()]);
        $client = Client::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'full_name' => 'Cliente Estadistica',
        ]);
        $service = Service::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => 'Limpieza facial',
            'price' => 100,
            'duration_minutes' => 60,
        ]);
        $appointment = Appointment::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'client_id' => $client->id,
            'scheduled_at' => '2026-08-15 10:00:00',
            'duration_minutes' => 60,
            'status' => 'attended',
            'attended' => true,
        ]);
        $appointment->services()->create([
            'service_id' => $service->id,
            'name' => $service->name,
            'price' => 100,
            'duration_minutes' => 60,
        ]);
        Appointment::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'client_id' => $client->id,
            'scheduled_at' => '2026-08-16 10:00:00',
            'duration_minutes' => 60,
            'status' => 'no_show',
            'attended' => false,
        ]);
        $payment = TreatmentPayment::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'client_id' => $client->id,
            'appointment_id' => $appointment->id,
            'amount' => 130,
            'method' => 'mixed',
            'paid_at' => '2026-08-15 10:30:00',
        ]);
        TreatmentPaymentItem::create([
            'treatment_payment_id' => $payment->id,
            'type' => 'service',
            'name' => 'Limpieza facial',
            'quantity' => 1,
            'unit_price' => 100,
            'charged_total' => 100,
            'total' => 100,
        ]);
        TreatmentPaymentItem::create([
            'treatment_payment_id' => $payment->id,
            'sold_by_user_id' => $seller->id,
            'type' => 'product',
            'name' => 'Crema',
            'quantity' => 1,
            'unit_price' => 30,
            'charged_total' => 30,
            'total' => 30,
        ]);
        TreatmentPaymentSplit::create([
            'treatment_payment_id' => $payment->id,
            'method' => 'cash',
            'amount' => 80,
        ]);
        TreatmentPaymentSplit::create([
            'treatment_payment_id' => $payment->id,
            'method' => 'qr',
            'amount' => 50,
        ]);
        $expenseType = ExpenseType::create([
            'company_id' => $company->id,
            'name' => 'Operativo',
            'slug' => 'operativo',
            'default_source' => 'cashbox',
            'status' => 'active',
        ]);
        Expense::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'expense_type_id' => $expenseType->id,
            'created_by_user_id' => $user->id,
            'source' => 'cashbox',
            'amount' => 20,
            'spent_at' => '2026-08-15',
            'description' => 'Gasto operativo',
        ]);

        $this->actingAs($user);
        session(['active_branch_id' => $branch->id]);

        Livewire::test(StatisticsSummary::class)
            ->set('dateFrom', '2026-08-01')
            ->set('dateTo', '2026-08-31')
            ->assertSee('50%')
            ->assertSee('Bs 130.00')
            ->assertSee('Bs 20.00')
            ->assertSee('Sucursal Centro')
            ->assertSee('Limpieza facial')
            ->assertSee('Vendedora Principal')
            ->assertSee('Crema')
            ->assertSee('Panel anual 2026')
            ->assertSee('Agosto');
    }
}
