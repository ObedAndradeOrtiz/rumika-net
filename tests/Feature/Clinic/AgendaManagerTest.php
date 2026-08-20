<?php

namespace Tests\Feature\Clinic;

use App\Livewire\Clinic\AgendaManager;
use App\Livewire\Clinic\QuickCashbox;
use App\Livewire\Inventory\InventoryManager;
use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\Branch;
use App\Models\BusinessType;
use App\Models\CashboxSession;
use App\Models\Client;
use App\Models\Company;
use App\Models\ClientCharge;
use App\Models\InventoryMovement;
use App\Models\InventoryProduct;
use App\Models\InventoryProductBatch;
use App\Models\Role;
use App\Models\Service;
use App\Models\TreatmentPayment;
use App\Models\TreatmentPaymentSplit;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class AgendaManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_clinic_agenda_creates_client_appointment_payment_attendance_and_reschedule(): void
    {
        [$admin, $company, $branch] = $this->companyContext('rumika-demo');
        $service = Service::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => 'Limpieza facial profunda',
            'price' => 160,
            'duration_minutes' => 60,
        ]);
        $extraService = Service::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => 'Mascarilla calmante',
            'price' => 80,
            'duration_minutes' => 30,
        ]);

        $this->actingAs($admin);
        session(['active_branch_id' => $branch->id]);

        Livewire::test(AgendaManager::class)
            ->set('clientMode', 'new')
            ->set('clientName', 'Maria Lopez')
            ->set('clientCi', '1234567')
            ->set('clientPhone', '70000000')
            ->set('scheduledDate', '2026-08-01')
            ->set('scheduledTime', '10:30')
            ->set('serviceIds', [(string) $service->id])
            ->set('plannedSessions', '4')
            ->set('paymentAmount', '160')
            ->set('paymentMethod', 'qr')
            ->set('invoiceRequested', true)
            ->call('saveAppointment')
            ->assertHasNoErrors();

        $appointment = Appointment::with(['client', 'payments', 'treatmentPlan'])->firstOrFail();

        $this->assertSame('Maria Lopez', $appointment->client->full_name);
        $this->assertTrue($appointment->locked_by_payment);
        $this->assertSame('160.00', $appointment->payments->first()->amount);
        $this->assertSame('qr', $appointment->payments->first()->method);

        Livewire::test(AgendaManager::class)
            ->call('markAttended', $appointment->id)
            ->assertSet('showAttendanceModal', true)
            ->set('attendanceUserId', $admin->id)
            ->call('confirmAttendance')
            ->call('openReschedule', $appointment->id)
            ->set('rescheduleDate', '2026-08-08')
            ->set('rescheduleTime', '10:30')
            ->set('rescheduleReason', 'Siguiente sesion')
            ->set('rescheduleServiceIds', [(string) $service->id, (string) $extraService->id])
            ->call('saveReschedule')
            ->assertHasNoErrors();

        $this->assertSame('rescheduled', $appointment->refresh()->status);
        $this->assertTrue($appointment->attended);
        $this->assertDatabaseHas('appointments', [
            'client_id' => $appointment->client_id,
            'rescheduled_from_id' => $appointment->id,
            'status' => 'scheduled',
            'reschedule_reason' => 'Siguiente sesion',
        ]);
        $this->assertSame('Siguiente sesion', $appointment->refresh()->reschedule_reason);
        $newAppointment = Appointment::with('services')->where('rescheduled_from_id', $appointment->id)->firstOrFail();
        $this->assertSame(['Limpieza facial profunda', 'Mascarilla calmante'], $newAppointment->services->pluck('name')->sort()->values()->all());
        $this->assertSame(1, $appointment->treatmentPlan->refresh()->completed_sessions);
        $this->assertSame(160.0, (float) TreatmentPayment::where('method', 'qr')->sum('amount'));
    }

    public function test_services_can_be_added_to_an_existing_appointment_and_completed(): void
    {
        [$admin, $company, $branch] = $this->companyContext('rumika-demo');
        $client = Client::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'full_name' => 'Cliente Historial',
        ]);
        $service = Service::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => 'Limpieza facial',
            'price' => 100,
            'duration_minutes' => 60,
        ]);
        $extraService = Service::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => 'Drenaje linfatico',
            'price' => 140,
            'duration_minutes' => 45,
        ]);

        $this->actingAs($admin);
        session(['active_branch_id' => $branch->id]);

        Livewire::test(AgendaManager::class)
            ->set('clientMode', 'existing')
            ->set('clientId', $client->id)
            ->set('scheduledDate', '2026-08-01')
            ->set('scheduledTime', '14:30')
            ->set('serviceIds', [(string) $service->id])
            ->call('saveAppointment')
            ->assertHasNoErrors();

        $appointment = Appointment::with('services')->firstOrFail();

        Livewire::test(AgendaManager::class)
            ->call('openAddServices', $appointment->id)
            ->set('addServiceIds', [(string) $extraService->id])
            ->call('saveAddedServices')
            ->assertHasNoErrors();

        $this->assertSame(2, $appointment->services()->count());
        $serviceLine = AppointmentService::where('service_id', $extraService->id)->firstOrFail();

        Livewire::test(AgendaManager::class)
            ->call('completeAppointmentService', $serviceLine->id)
            ->assertHasNoErrors();

        $this->assertSame('completed', $serviceLine->refresh()->status);
        $this->assertNotNull($serviceLine->completed_at);
        $this->assertSame('scheduled', $appointment->refresh()->status);
    }

    public function test_product_sale_picker_hides_empty_duplicate_batches_and_keeps_one_emergency_batch(): void
    {
        [$admin, $company, $branch] = $this->companyContext('rumika-agenda-lotes');
        $client = Client::create([
            'company_id' => $company->id,
            'full_name' => 'Cliente Producto',
        ]);
        $appointment = Appointment::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'client_id' => $client->id,
            'scheduled_at' => '2026-08-15 10:00:00',
            'duration_minutes' => 60,
            'status' => 'scheduled',
        ]);
        $appointment->services()->create([
            'name' => 'Consulta',
            'price' => 0,
        ]);
        $productWithStock = InventoryProduct::create([
            'company_id' => $company->id,
            'code' => 'SER-STOCK',
            'name' => 'Serum con stock',
            'unit_name' => 'unidad',
            'units_per_package' => 1,
            'purchase_cost' => 10,
            'minimum_stock' => 1,
        ]);
        $productWithoutStock = InventoryProduct::create([
            'company_id' => $company->id,
            'code' => 'SER-CERO',
            'name' => 'Serum sin stock',
            'unit_name' => 'unidad',
            'units_per_package' => 1,
            'purchase_cost' => 10,
            'minimum_stock' => 1,
        ]);

        InventoryProductBatch::create(['company_id' => $company->id, 'branch_id' => $branch->id, 'inventory_product_id' => $productWithStock->id, 'lot_code' => 'VENTA-POSITIVO', 'initial_quantity' => 4, 'current_quantity' => 4, 'unit_cost' => 10, 'status' => 'available']);
        InventoryProductBatch::create(['company_id' => $company->id, 'branch_id' => $branch->id, 'inventory_product_id' => $productWithStock->id, 'lot_code' => 'VENTA-CERO-OCULTO', 'initial_quantity' => 0, 'current_quantity' => 0, 'unit_cost' => 10, 'status' => 'available']);
        InventoryProductBatch::create(['company_id' => $company->id, 'branch_id' => $branch->id, 'inventory_product_id' => $productWithoutStock->id, 'lot_code' => 'VENTA-CERO-UNO', 'initial_quantity' => 0, 'current_quantity' => 0, 'unit_cost' => 10, 'status' => 'available']);
        InventoryProductBatch::create(['company_id' => $company->id, 'branch_id' => $branch->id, 'inventory_product_id' => $productWithoutStock->id, 'lot_code' => 'VENTA-CERO-DOS', 'initial_quantity' => 0, 'current_quantity' => 0, 'unit_cost' => 10, 'status' => 'available']);

        $this->actingAs($admin);
        session(['active_branch_id' => $branch->id]);

        Livewire::test(AgendaManager::class)
            ->call('openPayment', $appointment->id)
            ->call('addPaymentProductLine')
            ->assertSee('VENTA-POSITIVO')
            ->assertDontSee('VENTA-CERO-OCULTO')
            ->assertSee('VENTA-CERO-UNO')
            ->assertDontSee('VENTA-CERO-DOS');
    }

    public function test_appointment_actions_are_limited_to_the_active_branch(): void
    {
        [$admin, $company, $branch] = $this->companyContext('rumika-branch-agenda');
        $otherBranch = Branch::create([
            'company_id' => $company->id,
            'business_type_id' => $branch->business_type_id,
            'name' => 'Sucursal Norte',
            'slug' => 'sucursal-norte',
            'status' => 'active',
        ]);
        $otherBranch->users()->attach($admin->id, ['assigned_at' => now()]);
        $client = Client::create([
            'company_id' => $company->id,
            'full_name' => 'Cliente compartido',
        ]);
        $otherAppointment = Appointment::create([
            'company_id' => $company->id,
            'branch_id' => $otherBranch->id,
            'client_id' => $client->id,
            'scheduled_at' => '2026-08-15 09:00:00',
            'duration_minutes' => 60,
            'status' => 'scheduled',
        ]);

        $this->actingAs($admin);
        session(['active_branch_id' => $branch->id]);

        try {
            Livewire::test(AgendaManager::class)
                ->call('markAttended', $otherAppointment->id);

            $this->fail('A cross-branch appointment should not be available from the active branch.');
        } catch (ModelNotFoundException) {
            $this->assertTrue(true);
        }

        $this->assertFalse($otherAppointment->refresh()->attended);

        session(['active_branch_id' => $otherBranch->id]);

        Livewire::test(AgendaManager::class)
            ->call('markAttended', $otherAppointment->id)
            ->assertSet('showAttendanceModal', true)
            ->set('attendanceUserId', $admin->id)
            ->call('confirmAttendance')
            ->assertHasNoErrors();

        $this->assertTrue($otherAppointment->refresh()->attended);
    }

    public function test_agenda_search_filters_daily_appointments_by_client_and_service(): void
    {
        [$admin, $company, $branch] = $this->companyContext('rumika-agenda-search');
        $client = Client::create([
            'company_id' => $company->id,
            'full_name' => 'Marcela Roldon',
            'identity_number' => '445566',
            'phone' => '70009988',
        ]);
        $otherClient = Client::create([
            'company_id' => $company->id,
            'full_name' => 'Carlos Vega',
            'identity_number' => '778899',
            'phone' => '71112233',
        ]);
        $appointment = Appointment::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'client_id' => $client->id,
            'scheduled_at' => '2026-08-15 10:00:00',
            'duration_minutes' => 60,
            'status' => 'scheduled',
        ]);
        $appointment->services()->create([
            'name' => 'Limpieza facial profunda',
            'price' => 0,
        ]);
        $otherAppointment = Appointment::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'client_id' => $otherClient->id,
            'scheduled_at' => '2026-08-15 11:00:00',
            'duration_minutes' => 60,
            'status' => 'scheduled',
        ]);
        $otherAppointment->services()->create([
            'name' => 'Masaje relajante',
            'price' => 0,
        ]);

        $this->actingAs($admin);
        session(['active_branch_id' => $branch->id]);

        Livewire::test(AgendaManager::class)
            ->set('selectedDate', '2026-08-15')
            ->assertSee('Marcela Roldon')
            ->assertSee('Carlos Vega')
            ->set('appointmentSearch', 'facial')
            ->assertSee('Marcela Roldon')
            ->assertDontSee('Carlos Vega')
            ->set('appointmentSearch', '71112233')
            ->assertSee('Carlos Vega')
            ->assertDontSee('Marcela Roldon');
    }

    public function test_payment_can_be_split_and_include_products_without_duplicate_stock_on_edit(): void
    {
        [$admin, $company, $branch] = $this->companyContext('rumika-demo');
        $service = Service::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => 'Masaje relajante',
            'price' => 200,
            'duration_minutes' => 60,
        ]);
        $product = InventoryProduct::create([
            'company_id' => $company->id,
            'code' => 'ACE-REL',
            'name' => 'Aceite relajante',
            'unit_name' => 'ml',
            'purchase_cost' => 2,
        ]);
        $batch = InventoryProductBatch::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'inventory_product_id' => $product->id,
            'lot_code' => 'LOTE-1',
            'initial_quantity' => 10,
            'current_quantity' => 10,
            'unit_cost' => 2,
        ]);

        $this->actingAs($admin);
        session(['active_branch_id' => $branch->id]);

        Livewire::test(AgendaManager::class)
            ->set('clientMode', 'new')
            ->set('clientName', 'Ana Torres')
            ->set('scheduledDate', '2026-08-01')
            ->set('scheduledTime', '11:30')
            ->set('serviceIds', [(string) $service->id])
            ->call('saveAppointment')
            ->assertHasNoErrors();

        $appointment = Appointment::with('services')->firstOrFail();

        Livewire::test(AgendaManager::class)
            ->call('openPayment', $appointment->id)
            ->set('paymentCashAmount', '40')
            ->set('paymentQrAmount', '170')
            ->set('paymentAttendedByUserId', $admin->id)
            ->set('paymentServiceLineIds', [(string) $appointment->services->first()->id])
            ->set('paymentProductLines', [[
                'batch_id' => (string) $batch->id,
                'quantity' => '2',
                'unit_price' => '5',
            ]])
            ->call('savePayment')
            ->assertHasNoErrors();

        $payment = TreatmentPayment::with(['splits', 'items'])->firstOrFail();

        $this->assertSame('mixed', $payment->method);
        $this->assertSame('210.00', $payment->amount);
        $this->assertSame(40.0, (float) $payment->splits->where('method', 'cash')->sum('amount'));
        $this->assertSame(170.0, (float) $payment->splits->where('method', 'qr')->sum('amount'));
        $this->assertSame('8.00', $batch->refresh()->current_quantity);
        $this->assertDatabaseHas('inventory_movements', [
            'branch_id' => $branch->id,
            'inventory_product_batch_id' => $batch->id,
            'type' => 'sale',
            'quantity' => '2.00',
            'reference' => 'PAY-'.$payment->id,
        ]);

        Livewire::test(AgendaManager::class)
            ->call('editPayment', $payment->id)
            ->assertSee('Aceite relajante')
            ->set('paymentCashAmount', '55')
            ->set('paymentQrAmount', '150')
            ->set('paymentProductLines', [[
                'batch_id' => (string) $batch->id,
                'locked_name' => '',
                'quantity' => '1',
                'unit_price' => '5',
            ]])
            ->call('savePayment')
            ->assertHasNoErrors();

        $this->assertSame('9.00', $batch->refresh()->current_quantity);
        $this->assertSame('205.00', $payment->refresh()->amount);
        $this->assertSame(55.0, (float) $payment->splits()->where('method', 'cash')->sum('amount'));
        $this->assertSame(1, InventoryMovement::where('reference', 'PAY-'.$payment->id)->count());
    }

    public function test_product_price_is_suggested_when_selecting_batch_for_payment_sale(): void
    {
        [$admin, $company, $branch] = $this->companyContext('rumika-precio-producto');
        $service = Service::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => 'Control',
            'price' => 0,
            'duration_minutes' => 30,
        ]);
        $product = InventoryProduct::create([
            'company_id' => $company->id,
            'code' => 'SER-PRE',
            'name' => 'Serum precio',
            'unit_name' => 'unidad',
            'purchase_cost' => 35,
        ]);
        $batch = InventoryProductBatch::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'inventory_product_id' => $product->id,
            'lot_code' => 'PRECIO-1',
            'initial_quantity' => 4,
            'current_quantity' => 4,
            'unit_cost' => 35,
        ]);
        $client = Client::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'full_name' => 'Cliente Producto Precio',
        ]);
        $appointment = Appointment::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'client_id' => $client->id,
            'scheduled_at' => '2026-08-14 09:00:00',
            'duration_minutes' => 30,
            'status' => 'scheduled',
        ]);
        AppointmentService::create([
            'appointment_id' => $appointment->id,
            'service_id' => $service->id,
            'name' => $service->name,
            'price' => $service->price,
            'duration_minutes' => $service->duration_minutes,
            'status' => 'pending',
        ]);

        $this->actingAs($admin);
        session(['active_branch_id' => $branch->id]);

        Livewire::test(AgendaManager::class)
            ->call('openPayment', $appointment->id)
            ->call('addPaymentProductLine')
            ->assertSee('Serum precio')
            ->assertSee('Bs 35.00')
            ->set('paymentProductLines.0.quantity', '2')
            ->set('paymentProductLines.0.batch_id', (string) $batch->id)
            ->assertSet('paymentProductLines.0.unit_price', '35')
            ->assertSet('paymentProductLines.0.paid_amount', '70');
    }

    public function test_new_inventory_product_without_entry_appears_for_sale_as_catalog_batch(): void
    {
        [$admin, $company, $branch] = $this->companyContext('rumika-catalogo-venta');
        $service = Service::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => 'Control',
            'price' => 0,
            'duration_minutes' => 30,
        ]);
        $client = Client::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'full_name' => 'Cliente Catalogo',
        ]);
        $appointment = Appointment::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'client_id' => $client->id,
            'scheduled_at' => '2026-08-14 09:00:00',
            'duration_minutes' => 30,
            'status' => 'scheduled',
        ]);
        AppointmentService::create([
            'appointment_id' => $appointment->id,
            'service_id' => $service->id,
            'name' => $service->name,
            'price' => $service->price,
            'duration_minutes' => $service->duration_minutes,
            'status' => 'pending',
        ]);

        $this->actingAs($admin);
        session(['active_branch_id' => $branch->id]);

        Livewire::test(InventoryManager::class)
            ->call('createProduct')
            ->set('productName', 'Crema catalogo nueva')
            ->set('productDescription', 'Producto vendido aunque el stock aun no este cargado')
            ->set('unitName', 'unidad')
            ->set('purchaseCost', '45')
            ->set('minimumStock', '1')
            ->call('saveProduct')
            ->assertHasNoErrors();

        $product = InventoryProduct::where('name', 'Crema catalogo nueva')->firstOrFail();
        $batch = InventoryProductBatch::where('branch_id', $branch->id)
            ->where('inventory_product_id', $product->id)
            ->firstOrFail();

        $this->assertSame('0.00', $batch->current_quantity);

        Livewire::test(AgendaManager::class)
            ->call('openPayment', $appointment->id)
            ->set('productSearch', 'Crema catalogo')
            ->call('addPaymentProductLine')
            ->assertSee('Crema catalogo nueva')
            ->set('paymentProductLines.0.batch_id', (string) $batch->id)
            ->set('paymentProductLines.0.quantity', '1')
            ->set('paymentProductLines.0.unit_price', '60')
            ->set('paymentProductLines.0.paid_amount', '60')
            ->set('paymentProductLines.0.stock_shortage_reason', 'Producto nuevo vendido desde catalogo')
            ->set('paymentCashAmount', '60')
            ->set('paymentServiceLineIds', [])
            ->call('savePayment')
            ->assertHasNoErrors();

        $payment = TreatmentPayment::firstOrFail();

        $this->assertSame('-1.00', $batch->refresh()->current_quantity);
        $this->assertDatabaseHas('inventory_movements', [
            'reference' => 'PAY-'.$payment->id,
            'inventory_product_batch_id' => $batch->id,
            'type' => 'stock_shortage',
            'quantity' => '1.00',
            'reason' => 'Producto nuevo vendido desde catalogo',
        ]);
    }

    public function test_administrator_role_with_agenda_delete_permission_can_delete_appointments(): void
    {
        [$admin, $company, $branch] = $this->companyContext('rumika-admin-delete-cita');

        DB::table('company_user')
            ->where('company_id', $company->id)
            ->where('user_id', $admin->id)
            ->update(['role' => 'member']);

        $role = Role::create([
            'company_id' => $company->id,
            'name' => 'Administrador',
            'slug' => 'administrador',
            'scope' => 'company',
            'permissions' => [
                'agenda' => ['view', 'create', 'edit', 'delete'],
            ],
            'is_system' => true,
        ]);

        DB::table('branch_user')
            ->where('branch_id', $branch->id)
            ->where('user_id', $admin->id)
            ->update(['role_id' => $role->id]);

        $client = Client::create([
            'company_id' => $company->id,
            'full_name' => 'Cliente Administrador',
        ]);
        $appointment = Appointment::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'client_id' => $client->id,
            'scheduled_at' => '2026-08-15 10:00:00',
            'duration_minutes' => 60,
            'status' => 'scheduled',
        ]);

        $this->actingAs($admin);
        session(['active_branch_id' => $branch->id]);

        Livewire::test(AgendaManager::class)
            ->set('selectedDate', '2026-08-15')
            ->assertSeeHtml('title="Eliminar cita"')
            ->call('confirmDeleteAppointment', $appointment->id)
            ->assertSet('confirmingAppointmentDeleteId', $appointment->id)
            ->call('deleteAppointment', $appointment->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('appointments', ['id' => $appointment->id]);
    }

    public function test_super_admin_can_delete_appointment_and_reverse_products_payments_and_charges(): void
    {
        [$admin, $company, $branch] = $this->companyContext('rumika-delete-cita');
        $service = Service::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => 'Tratamiento correctivo',
            'price' => 100,
            'duration_minutes' => 60,
        ]);
        $product = InventoryProduct::create([
            'company_id' => $company->id,
            'code' => 'CRE-ERR',
            'name' => 'Crema error venta',
            'unit_name' => 'unidad',
            'purchase_cost' => 20,
        ]);
        $batch = InventoryProductBatch::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'inventory_product_id' => $product->id,
            'lot_code' => 'ERR-1',
            'initial_quantity' => 5,
            'current_quantity' => 5,
            'unit_cost' => 20,
        ]);

        $this->actingAs($admin);
        session(['active_branch_id' => $branch->id]);

        Livewire::test(AgendaManager::class)
            ->set('clientMode', 'new')
            ->set('clientName', 'Cliente Error')
            ->set('scheduledDate', '2026-08-01')
            ->set('scheduledTime', '09:30')
            ->set('serviceIds', [(string) $service->id])
            ->call('saveAppointment')
            ->assertHasNoErrors();

        $appointment = Appointment::with(['services', 'treatmentPlan'])->firstOrFail();

        Livewire::test(AgendaManager::class)
            ->call('markAttended', $appointment->id)
            ->assertSet('showAttendanceModal', true)
            ->set('attendanceUserId', $admin->id)
            ->call('confirmAttendance')
            ->call('openPayment', $appointment->id)
            ->set('paymentCashAmount', '120')
            ->set('paymentServiceLineIds', [(string) $appointment->services->first()->id])
            ->set('paymentProductLines', [[
                'batch_id' => (string) $batch->id,
                'quantity' => '1',
                'unit_price' => '20',
            ]])
            ->call('savePayment')
            ->assertHasNoErrors();

        $payment = TreatmentPayment::firstOrFail();

        $this->assertSame('4.00', $batch->refresh()->current_quantity);
        $this->assertSame(2, ClientCharge::where('appointment_id', $appointment->id)->count());
        $this->assertSame(1, InventoryMovement::where('reference', 'PAY-'.$payment->id)->count());
        $this->assertSame(1, $appointment->treatmentPlan->refresh()->completed_sessions);
        $this->assertSame(120.0, (float) $appointment->treatmentPlan->paid_amount);

        Livewire::test(AgendaManager::class)
            ->call('confirmDeleteAppointment', $appointment->id)
            ->assertSet('confirmingAppointmentDeleteId', $appointment->id)
            ->call('deleteAppointment', $appointment->id)
            ->assertHasNoErrors()
            ->assertSet('confirmingAppointmentDeleteId', null);

        $this->assertDatabaseMissing('appointments', ['id' => $appointment->id]);
        $this->assertSame('5.00', $batch->refresh()->current_quantity);
        $this->assertSame(0, TreatmentPayment::count());
        $this->assertSame(0, ClientCharge::where('appointment_id', $appointment->id)->count());
        $this->assertSame(0, InventoryMovement::where('reference', 'PAY-'.$payment->id)->count());
        $this->assertSame(0, $appointment->treatmentPlan->refresh()->completed_sessions);
        $this->assertSame(0.0, (float) $appointment->treatmentPlan->paid_amount);
    }

    public function test_product_sale_groups_duplicate_batch_lines_before_subtracting_stock(): void
    {
        [$admin, $company, $branch] = $this->companyContext('rumika-demo');
        $service = Service::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => 'Limpieza express',
            'price' => 50,
            'duration_minutes' => 30,
        ]);
        $product = InventoryProduct::create([
            'company_id' => $company->id,
            'code' => 'MAS-FAC',
            'name' => 'Mascarilla facial',
            'unit_name' => 'unidad',
            'purchase_cost' => 10,
        ]);
        $batch = InventoryProductBatch::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'inventory_product_id' => $product->id,
            'lot_code' => 'LOTE-2',
            'initial_quantity' => 3,
            'current_quantity' => 3,
            'unit_cost' => 10,
        ]);

        $this->actingAs($admin);
        session(['active_branch_id' => $branch->id]);

        Livewire::test(AgendaManager::class)
            ->set('clientMode', 'new')
            ->set('clientName', 'Lucia Perez')
            ->set('scheduledDate', '2026-08-01')
            ->set('scheduledTime', '12:30')
            ->set('serviceIds', [(string) $service->id])
            ->call('saveAppointment')
            ->assertHasNoErrors();

        $appointment = Appointment::with('services')->firstOrFail();

        Livewire::test(AgendaManager::class)
            ->call('openPayment', $appointment->id)
            ->set('paymentCashAmount', '90')
            ->set('paymentServiceLineIds', [(string) $appointment->services->first()->id])
            ->set('paymentProductLines', [
                [
                    'batch_id' => (string) $batch->id,
                    'quantity' => '2',
                    'unit_price' => '10',
                ],
                [
                    'batch_id' => (string) $batch->id,
                    'quantity' => '2',
                    'unit_price' => '10',
                ],
            ])
            ->call('savePayment')
            ->assertHasErrors(['paymentProductLines']);

        $this->assertSame('3.00', $batch->refresh()->current_quantity);
        $this->assertSame(0, TreatmentPayment::count());
        $this->assertSame(0, InventoryMovement::count());

        Livewire::test(AgendaManager::class)
            ->call('openPayment', $appointment->id)
            ->set('paymentCashAmount', '90')
            ->set('paymentServiceLineIds', [(string) $appointment->services->first()->id])
            ->set('paymentProductLines', [
                [
                    'batch_id' => (string) $batch->id,
                    'quantity' => '2',
                    'unit_price' => '10',
                    'stock_shortage_reason' => 'Conteo fisico pendiente de regularizar',
                ],
                [
                    'batch_id' => (string) $batch->id,
                    'quantity' => '2',
                    'unit_price' => '10',
                ],
            ])
            ->call('savePayment')
            ->assertHasNoErrors();

        $payment = TreatmentPayment::firstOrFail();

        $this->assertSame('-1.00', $batch->refresh()->current_quantity);
        $this->assertSame(3, InventoryMovement::where('reference', 'PAY-'.$payment->id)->count());
        $this->assertSame(4.0, (float) InventoryMovement::where('reference', 'PAY-'.$payment->id)->where('type', 'sale')->sum('quantity'));
        $this->assertDatabaseHas('inventory_movements', [
            'branch_id' => $branch->id,
            'inventory_product_batch_id' => $batch->id,
            'type' => 'stock_shortage',
            'quantity' => '1.00',
            'reason' => 'Conteo fisico pendiente de regularizar',
        ]);
    }

    public function test_client_can_take_services_and_products_on_account_and_pay_later(): void
    {
        [$admin, $company, $branch] = $this->companyContext('rumika-demo');
        $client = Client::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'full_name' => 'Cliente Cuenta',
        ]);
        $service = Service::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => 'Tratamiento laser',
            'price' => 100,
            'duration_minutes' => 45,
        ]);
        $product = InventoryProduct::create([
            'company_id' => $company->id,
            'code' => 'CRE-POS',
            'name' => 'Crema post tratamiento',
            'unit_name' => 'unidad',
            'purchase_cost' => 30,
        ]);
        $batch = InventoryProductBatch::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'inventory_product_id' => $product->id,
            'lot_code' => 'LOTE-CUENTA',
            'initial_quantity' => 5,
            'current_quantity' => 5,
            'unit_cost' => 30,
        ]);

        $this->actingAs($admin);
        session(['active_branch_id' => $branch->id]);
        $seller = User::factory()->create(['email' => 'vendedor-productos@rumika.test']);
        $company->users()->attach($seller->id, [
            'role' => 'staff',
            'joined_at' => now(),
        ]);
        $branch->users()->attach($seller->id, [
            'assigned_at' => now(),
        ]);

        Livewire::test(AgendaManager::class)
            ->set('clientMode', 'existing')
            ->set('clientId', $client->id)
            ->set('scheduledDate', '2026-08-01')
            ->set('scheduledTime', '13:30')
            ->set('serviceIds', [(string) $service->id])
            ->call('saveAppointment')
            ->assertHasNoErrors();

        $appointment = Appointment::with('services')->firstOrFail();
        $serviceLine = $appointment->services->first();

        Livewire::test(AgendaManager::class)
            ->call('openPayment', $appointment->id)
            ->set('paymentCashAmount', '70')
            ->set('paymentQrAmount', '')
            ->set('paymentServiceLineIds', [(string) $serviceLine->id])
            ->set('paymentServiceLinePrices', [(string) $serviceLine->id => '120'])
            ->set('paymentServiceLinePayments', [(string) $serviceLine->id => '60'])
            ->set('paymentProductSoldByUserId', $seller->id)
            ->set('paymentProductLines', [[
                'batch_id' => (string) $batch->id,
                'quantity' => '1',
                'unit_price' => '50',
                'paid_amount' => '10',
            ]])
            ->call('savePayment')
            ->assertHasNoErrors();

        $this->assertSame('4.00', $batch->refresh()->current_quantity);
        $this->assertSame(2, ClientCharge::where('client_id', $client->id)->where('status', 'partial')->count());
        $this->assertSame(100.0, (float) ClientCharge::where('client_id', $client->id)->sum('balance_amount'));
        $this->assertDatabaseHas('client_charges', [
            'client_id' => $client->id,
            'type' => 'service',
            'total_amount' => '120.00',
            'paid_amount' => '60.00',
            'balance_amount' => '60.00',
        ]);
        $this->assertDatabaseHas('client_charges', [
            'client_id' => $client->id,
            'type' => 'product',
            'sold_by_user_id' => $seller->id,
        ]);
        $this->assertDatabaseHas('treatment_payment_items', [
            'type' => 'product',
            'name' => 'Crema post tratamiento',
            'sold_by_user_id' => $seller->id,
        ]);

        Livewire::test(AgendaManager::class)
            ->call('openHistory', $client->id)
            ->assertSee('Citas')
            ->assertSee('Productos')
            ->assertSee('Tratamientos')
            ->assertSee('A cuenta')
            ->set('historyTab', 'products')
            ->assertSee('Productos comprados')
            ->assertSee('Crema post tratamiento')
            ->set('historyTab', 'service_debts')
            ->assertSee('Tratamientos pendientes')
            ->assertSee('Tratamiento laser')
            ->set('historyTab', 'product_debts')
            ->assertSee('Productos a cuenta')
            ->assertSee('Crema post tratamiento')
            ->assertSee($seller->name);

        $pendingCharges = ClientCharge::where('client_id', $client->id)->pluck('balance_amount', 'id');

        Livewire::test(AgendaManager::class)
            ->call('openPayment', $appointment->id)
            ->set('paymentServiceLineIds', [])
            ->set('paymentServiceLinePayments', [])
            ->set('paymentProductLines', [])
            ->set('pendingChargePayments', $pendingCharges->map(fn ($amount) => (string) $amount)->all())
            ->set('paymentCashAmount', '100')
            ->set('paymentQrAmount', '')
            ->call('savePayment')
            ->assertHasNoErrors();

        $this->assertSame('4.00', $batch->refresh()->current_quantity);
        $this->assertSame(2, ClientCharge::where('client_id', $client->id)->where('status', 'paid')->count());
        $this->assertSame(0.0, (float) ClientCharge::where('client_id', $client->id)->sum('balance_amount'));
    }

    public function test_product_debt_stays_visible_when_editing_payment_and_adding_another_product(): void
    {
        [$admin, $company, $branch] = $this->companyContext('rumika-producto-deuda');
        $client = Client::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'full_name' => 'Cliente Deuda Producto',
        ]);
        $service = Service::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => 'Consulta facial',
            'price' => 100,
            'duration_minutes' => 45,
        ]);
        $debtProduct = InventoryProduct::create([
            'company_id' => $company->id,
            'code' => 'CRE-DEU',
            'name' => 'Crema en deuda',
            'unit_name' => 'unidad',
            'purchase_cost' => 30,
        ]);
        $newProduct = InventoryProduct::create([
            'company_id' => $company->id,
            'code' => 'SER-NUE',
            'name' => 'Serum nuevo',
            'unit_name' => 'unidad',
            'purchase_cost' => 25,
        ]);
        $debtBatch = InventoryProductBatch::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'inventory_product_id' => $debtProduct->id,
            'lot_code' => 'DEUDA-1',
            'initial_quantity' => 5,
            'current_quantity' => 5,
            'unit_cost' => 30,
        ]);
        $newBatch = InventoryProductBatch::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'inventory_product_id' => $newProduct->id,
            'lot_code' => 'NUEVO-1',
            'initial_quantity' => 5,
            'current_quantity' => 5,
            'unit_cost' => 25,
        ]);

        $this->actingAs($admin);
        session(['active_branch_id' => $branch->id]);

        Livewire::test(AgendaManager::class)
            ->set('clientMode', 'existing')
            ->set('clientId', $client->id)
            ->set('scheduledDate', '2026-08-01')
            ->set('scheduledTime', '16:30')
            ->set('serviceIds', [(string) $service->id])
            ->call('saveAppointment')
            ->assertHasNoErrors();

        $appointment = Appointment::with('services')->firstOrFail();

        Livewire::test(AgendaManager::class)
            ->call('openPayment', $appointment->id)
            ->set('paymentCashAmount', '100')
            ->set('paymentServiceLineIds', [(string) $appointment->services->first()->id])
            ->set('paymentProductLines', [[
                'batch_id' => (string) $debtBatch->id,
                'quantity' => '1',
                'unit_price' => '30',
                'paid_amount' => '0',
            ]])
            ->call('savePayment')
            ->assertHasNoErrors();

        $payment = TreatmentPayment::with('items')->firstOrFail();

        $this->assertDatabaseHas('treatment_payment_items', [
            'treatment_payment_id' => $payment->id,
            'type' => 'product',
            'name' => 'Crema en deuda',
            'total' => '0.00',
        ]);

        Livewire::test(AgendaManager::class)
            ->call('editPayment', $payment->id)
            ->assertSee('Crema en deuda')
            ->set('paymentCashAmount', '105')
            ->set('paymentProductLines', [
                [
                    'batch_id' => (string) $debtBatch->id,
                    'quantity' => '1',
                    'unit_price' => '30',
                    'paid_amount' => '0',
                ],
                [
                    'batch_id' => (string) $newBatch->id,
                    'quantity' => '1',
                    'unit_price' => '5',
                    'paid_amount' => '5',
                ],
            ])
            ->call('savePayment')
            ->assertHasNoErrors();

        $this->assertSame(1, ClientCharge::where('client_id', $client->id)->where('name', 'Crema en deuda')->count());
        $this->assertDatabaseHas('treatment_payment_items', [
            'treatment_payment_id' => $payment->id,
            'type' => 'product',
            'name' => 'Serum nuevo',
            'total' => '5.00',
        ]);
    }

    public function test_pending_client_debt_accepts_qr_cash_and_decimal_commas(): void
    {
        [$admin, $company, $branch] = $this->companyContext('rumika-deuda-mixta');
        $client = Client::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'full_name' => 'Cliente Deuda Mixta',
        ]);
        $service = Service::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => 'Control facial',
            'price' => 0,
            'duration_minutes' => 30,
        ]);
        $appointment = Appointment::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'client_id' => $client->id,
            'scheduled_at' => '2026-08-15 11:00:00',
            'duration_minutes' => 30,
            'status' => 'scheduled',
        ]);
        AppointmentService::create([
            'appointment_id' => $appointment->id,
            'service_id' => $service->id,
            'name' => $service->name,
            'price' => 0,
        ]);
        $charge = ClientCharge::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'client_id' => $client->id,
            'type' => 'service',
            'name' => 'Tratamiento pendiente',
            'quantity' => 1,
            'unit_price' => 100,
            'total_amount' => 100,
            'paid_amount' => 0,
            'balance_amount' => 100,
            'status' => 'pending',
            'charged_at' => '2026-08-01 10:00:00',
        ]);

        $this->actingAs($admin);
        session(['active_branch_id' => $branch->id]);

        Livewire::test(AgendaManager::class)
            ->call('openPayment', $appointment->id)
            ->set('paymentServiceLineIds', [])
            ->set('paymentServiceLinePayments', [])
            ->set('pendingChargePayments', [(string) $charge->id => '75,50'])
            ->set('paymentQrAmount', '50,50')
            ->set('extraPaymentSplits', [[
                'method' => 'cash',
                'amount' => '25,00',
                'reference' => 'Abono caja',
            ]])
            ->call('savePayment')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('client_charges', [
            'id' => $charge->id,
            'paid_amount' => '75.50',
            'balance_amount' => '24.50',
            'status' => 'partial',
        ]);
        $this->assertDatabaseHas('treatment_payment_splits', [
            'method' => 'qr',
            'amount' => '50.50',
        ]);
        $this->assertDatabaseHas('treatment_payment_splits', [
            'method' => 'cash',
            'amount' => '25.00',
            'reference' => 'Abono caja',
        ]);
    }

    public function test_appointment_modal_filters_clients_services_and_creates_mixed_initial_payment(): void
    {
        [$admin, $company, $branch] = $this->companyContext('rumika-demo');
        $services = collect(['Alpha facial', 'Beta corporal', 'Gamma laser', 'Drenaje linfatico', 'Radiofrecuencia facial'])
            ->map(fn (string $name) => Service::create([
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'name' => $name,
                'price' => 100,
                'duration_minutes' => 60,
            ]));
        Client::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'full_name' => 'Carlos Prado',
            'identity_number' => '998877',
            'phone' => '76543210',
        ]);
        $client = Client::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'full_name' => 'Lucia Salvatierra',
            'identity_number' => '123456',
            'phone' => '70001234',
        ]);

        $this->actingAs($admin);
        session(['active_branch_id' => $branch->id]);

        Livewire::test(AgendaManager::class)
            ->call('createAppointment')
            ->assertSee('Alpha facial')
            ->assertSee('Beta corporal')
            ->assertSee('Drenaje linfatico')
            ->assertDontSee('Gamma laser')
            ->set('serviceSearch', 'Radio')
            ->assertSee('Radiofrecuencia facial')
            ->assertDontSee('Alpha facial')
            ->set('clientSearch', '70001234')
            ->assertSee('Lucia Salvatierra')
            ->assertDontSee('Carlos Prado')
            ->set('clientId', $client->id)
            ->set('scheduledDate', '2026-08-01')
            ->set('scheduledTime', '15:00')
            ->set('serviceIds', [(string) $services->last()->id])
            ->set('paymentMethod', 'mixed')
            ->set('paymentCashAmount', '70')
            ->set('paymentQrAmount', '30')
            ->call('saveAppointment')
            ->assertHasNoErrors();

        $payment = TreatmentPayment::with('splits')->firstOrFail();

        $this->assertSame('mixed', $payment->method);
        $this->assertSame('100.00', $payment->amount);
        $this->assertSame(70.0, (float) $payment->splits->where('method', 'cash')->sum('amount'));
        $this->assertSame(30.0, (float) $payment->splits->where('method', 'qr')->sum('amount'));
    }

    public function test_first_cashbox_shift_counts_existing_day_income_after_reopening_from_zero(): void
    {
        [$admin, $company, $branch] = $this->companyContext('rumika-caja-reapertura');
        $client = Client::create([
            'company_id' => $company->id,
            'full_name' => 'Cliente Caja',
        ]);
        $payment = TreatmentPayment::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'client_id' => $client->id,
            'amount' => 120,
            'method' => 'cash',
            'paid_at' => '2026-08-15 09:00:00',
        ]);
        TreatmentPaymentSplit::create([
            'treatment_payment_id' => $payment->id,
            'method' => 'cash',
            'amount' => 120,
        ]);

        $this->actingAs($admin);
        session(['active_branch_id' => $branch->id]);

        Livewire::test(QuickCashbox::class)
            ->set('selectedDate', '2026-08-15')
            ->set('openingAmount', '0')
            ->call('openCashbox')
            ->assertHasNoErrors()
            ->call('closeCashbox')
            ->assertHasNoErrors();

        $session = CashboxSession::firstOrFail();

        $this->assertSame(1, $session->shift_number);
        $this->assertSame('closed', $session->status);
        $this->assertSame('120.00', $session->cash_total);
        $this->assertSame('120.00', $session->expected_cash_amount);

        Livewire::test(QuickCashbox::class)
            ->set('selectedDate', '2026-08-15')
            ->call('confirmDeleteClosedCashbox', $session->id)
            ->call('deleteClosedCashbox')
            ->assertHasNoErrors();

        $this->assertSame(0, CashboxSession::count());

        Livewire::test(QuickCashbox::class)
            ->set('selectedDate', '2026-08-15')
            ->set('openingAmount', '0')
            ->call('openCashbox')
            ->assertHasNoErrors()
            ->call('closeCashbox')
            ->assertHasNoErrors();

        $newSession = CashboxSession::firstOrFail();

        $this->assertSame(1, $newSession->shift_number);
        $this->assertSame('120.00', $newSession->cash_total);
        $this->assertSame('120.00', $newSession->expected_cash_amount);
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
