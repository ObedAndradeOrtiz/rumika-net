<?php

namespace Tests\Feature\Inventory;

use App\Livewire\Inventory\InventoryManager;
use App\Models\Branch;
use App\Models\BusinessType;
use App\Models\Company;
use App\Models\InventoryProduct;
use App\Models\InventoryProductBatch;
use App\Models\InventoryCount;
use App\Models\InventoryCountItem;
use App\Models\InventoryMovement;
use App\Models\InventoryUseArea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InventoryManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_creates_products_batches_movements_and_assets_by_company(): void
    {
        [$admin, $company, $branch] = $this->companyContext('rumika-demo');
        [$otherAdmin] = $this->companyContext('otra-empresa');

        $this->actingAs($admin);
        session(['active_branch_id' => $branch->id]);

        Livewire::test(InventoryManager::class)
            ->set('useAreaName', 'Gabinete')
            ->set('useAreaDescription', 'Uso interno en cabina')
            ->call('saveUseArea')
            ->assertHasNoErrors()
            ->set('productName', 'Aguja mesoterapia 30G')
            ->set('unitName', 'unidad')
            ->set('packageName', 'Caja')
            ->set('unitsPerPackage', '100')
            ->set('purchaseCost', '1.50')
            ->set('minimumStock', '10')
            ->set('productUseAreaId', InventoryUseArea::query()->where('company_id', $company->id)->where('name', 'Gabinete')->value('id'))
            ->call('saveProduct')
            ->assertHasNoErrors()
            ->assertSee('Gabinete')
            ->set('productName', 'Aguja mesoterapia 30G')
            ->set('unitName', 'unidad')
            ->set('unitsPerPackage', '100')
            ->set('purchaseCost', '1.50')
            ->set('minimumStock', '10')
            ->call('saveProduct')
            ->assertHasNoErrors();

        $products = InventoryProduct::query()->where('company_id', $company->id)->orderBy('id')->get();

        $this->assertCount(2, $products);
        $this->assertNotSame($products[0]->code, $products[1]->code);

        Livewire::test(InventoryManager::class)
            ->set('movementType', 'purchase')
            ->set('movementProductId', $products[0]->id)
            ->set('movementQuantity', '25')
            ->set('movementUnitCost', '1.50')
            ->set('movementLotCode', '')
            ->set('movementReceivedAt', '2026-08-01')
            ->call('saveMovement')
            ->assertHasNoErrors();

        $batch = InventoryProductBatch::query()
            ->where('inventory_product_id', $products[0]->id)
            ->orderByDesc('current_quantity')
            ->firstOrFail();

        $this->assertSame('25.00', $batch->current_quantity);
        $this->assertNotEmpty($batch->lot_code);

        Livewire::test(InventoryManager::class)
            ->set('movementType', 'stock_out')
            ->set('movementProductId', $products[0]->id)
            ->set('movementBatchId', $batch->id)
            ->set('movementQuantity', '2')
            ->set('movementReason', 'Salida operativa')
            ->call('saveMovement')
            ->assertHasNoErrors();

        $this->assertSame('23.00', $batch->refresh()->current_quantity);
        $this->assertDatabaseHas('inventory_movements', [
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'type' => 'stock_out',
            'reason' => 'Salida operativa',
        ]);

        Livewire::test(InventoryManager::class)
            ->set('movementType', 'waste')
            ->set('movementProductId', $products[0]->id)
            ->set('movementBatchId', $batch->id)
            ->set('movementQuantity', '5')
            ->set('movementReason', 'Producto defectuoso')
            ->call('saveMovement')
            ->assertHasNoErrors();

        $this->assertSame('18.00', $batch->refresh()->current_quantity);
        $this->assertDatabaseHas('inventory_movements', [
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'type' => 'waste',
            'reason' => 'Producto defectuoso',
        ]);

        Livewire::test(InventoryManager::class)
            ->set('assetName', 'Camilla hidraulica')
            ->set('assetCategory', 'Equipo')
            ->set('assetPurchaseAmount', '2500')
            ->set('assetPurchaseDate', '2026-08-01')
            ->call('saveAsset')
            ->assertHasNoErrors();

        $asset = $company->inventoryAssets()->where('name', 'Camilla hidraulica')->firstOrFail();

        Livewire::test(InventoryManager::class)
            ->call('openRepair', $asset->id)
            ->set('repairAmount', '150')
            ->set('repairDescription', 'Cambio de piston')
            ->call('saveRepair')
            ->assertHasNoErrors()
            ->call('openWasteAsset', $asset->id)
            ->set('assetWasteReason', 'No recuperable')
            ->call('wasteAsset')
            ->assertHasNoErrors();

        $this->assertSame('wasted', $asset->refresh()->status);
        $this->assertSame('150.00', $asset->repair_total);

        $this->actingAs($otherAdmin);

        Livewire::test(InventoryManager::class)
            ->assertDontSee('Aguja mesoterapia 30G')
            ->assertDontSee('Camilla hidraulica');
    }

    public function test_inventory_stock_is_independent_per_branch(): void
    {
        [$admin, $company, $branch] = $this->companyContext('rumika-demo');
        $secondBranch = Branch::create([
            'company_id' => $company->id,
            'business_type_id' => $branch->business_type_id,
            'name' => 'Sucursal Norte',
            'slug' => 'sucursal-norte',
            'status' => 'active',
        ]);
        $secondBranch->users()->attach($admin->id, [
            'assigned_at' => now(),
        ]);

        $this->actingAs($admin);
        session(['active_branch_id' => $branch->id]);

        Livewire::test(InventoryManager::class)
            ->set('productName', 'Mascarilla facial hidratante')
            ->set('unitName', 'unidad')
            ->set('packageName', 'Caja')
            ->set('unitsPerPackage', '12')
            ->set('purchaseCost', '18')
            ->set('minimumStock', '8')
            ->call('saveProduct')
            ->assertHasNoErrors();

        $product = InventoryProduct::query()->where('company_id', $company->id)->firstOrFail();

        Livewire::test(InventoryManager::class)
            ->set('movementType', 'purchase')
            ->set('movementProductId', $product->id)
            ->set('movementQuantity', '36')
            ->set('movementUnitCost', '18')
            ->set('movementLotCode', 'MASK-001')
            ->set('movementReceivedAt', '2026-08-02')
            ->call('saveMovement')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('inventory_product_batches', [
            'branch_id' => $branch->id,
            'inventory_product_id' => $product->id,
            'current_quantity' => 36,
        ]);
        $this->assertDatabaseHas('inventory_product_batches', [
            'branch_id' => $secondBranch->id,
            'inventory_product_id' => $product->id,
            'current_quantity' => 0,
        ]);

        Livewire::withQueryParams(['page' => 1])
            ->test(InventoryManager::class)
            ->assertSee('Stock 36.00 unidad');

        session(['active_branch_id' => $secondBranch->id]);

        Livewire::test(InventoryManager::class)
            ->assertSee('Mascarilla facial hidratante')
            ->assertSee('Stock 0.00 unidad')
            ->assertDontSee('Stock 36.00 unidad');
    }

    public function test_movement_batches_hide_empty_duplicates_and_keep_one_emergency_batch(): void
    {
        [$admin, $company, $branch] = $this->companyContext('rumika-lotes-cero');
        $productWithStock = InventoryProduct::create([
            'company_id' => $company->id,
            'code' => 'CRE-STOCK',
            'name' => 'Crema con stock',
            'unit_name' => 'unidad',
            'units_per_package' => 1,
            'purchase_cost' => 10,
            'minimum_stock' => 1,
        ]);
        $productWithoutStock = InventoryProduct::create([
            'company_id' => $company->id,
            'code' => 'CRE-CERO',
            'name' => 'Crema sin stock',
            'unit_name' => 'unidad',
            'units_per_package' => 1,
            'purchase_cost' => 10,
            'minimum_stock' => 1,
        ]);

        InventoryProductBatch::create(['company_id' => $company->id, 'branch_id' => $branch->id, 'inventory_product_id' => $productWithStock->id, 'lot_code' => 'LOTE-POSITIVO', 'initial_quantity' => 5, 'current_quantity' => 5, 'unit_cost' => 10, 'status' => 'available']);
        InventoryProductBatch::create(['company_id' => $company->id, 'branch_id' => $branch->id, 'inventory_product_id' => $productWithStock->id, 'lot_code' => 'LOTE-CERO-OCULTO', 'initial_quantity' => 0, 'current_quantity' => 0, 'unit_cost' => 10, 'status' => 'available']);
        InventoryProductBatch::create(['company_id' => $company->id, 'branch_id' => $branch->id, 'inventory_product_id' => $productWithoutStock->id, 'lot_code' => 'LOTE-CERO-UNO', 'initial_quantity' => 0, 'current_quantity' => 0, 'unit_cost' => 10, 'status' => 'available']);
        InventoryProductBatch::create(['company_id' => $company->id, 'branch_id' => $branch->id, 'inventory_product_id' => $productWithoutStock->id, 'lot_code' => 'LOTE-CERO-DOS', 'initial_quantity' => 0, 'current_quantity' => 0, 'unit_cost' => 10, 'status' => 'available']);

        $this->actingAs($admin);
        session(['active_branch_id' => $branch->id]);

        Livewire::test(InventoryManager::class, ['screen' => 'operations'])
            ->call('openMovement', 'waste')
            ->set('movementProductId', $productWithStock->id)
            ->assertSee('LOTE-POSITIVO')
            ->assertDontSee('LOTE-CERO-OCULTO')
            ->set('movementProductId', $productWithoutStock->id)
            ->assertSee('LOTE-CERO-UNO')
            ->assertDontSee('LOTE-CERO-DOS');
    }

    public function test_use_areas_can_be_edited_and_only_deleted_when_unused(): void
    {
        [$admin, $company, $branch] = $this->companyContext('rumika-zonas');

        $this->actingAs($admin);
        session(['active_branch_id' => $branch->id]);

        Livewire::test(InventoryManager::class)
            ->set('useAreaName', 'Gabinete')
            ->set('useAreaDescription', 'Cabina')
            ->call('saveUseArea')
            ->assertHasNoErrors();

        $area = InventoryUseArea::query()
            ->where('company_id', $company->id)
            ->where('name', 'Gabinete')
            ->firstOrFail();

        Livewire::test(InventoryManager::class)
            ->call('editUseArea', $area->id)
            ->assertSet('editingUseAreaId', $area->id)
            ->set('useAreaName', 'Cabina estetica')
            ->set('useAreaDescription', 'Uso en tratamientos')
            ->call('saveUseArea')
            ->assertHasNoErrors()
            ->assertSet('showUseAreaModal', false);

        $this->assertDatabaseHas('inventory_use_areas', [
            'id' => $area->id,
            'company_id' => $company->id,
            'name' => 'Cabina estetica',
            'slug' => 'cabina-estetica',
            'description' => 'Uso en tratamientos',
        ]);

        Livewire::test(InventoryManager::class)
            ->call('confirmDeleteUseArea', $area->id)
            ->assertSet('confirmingUseAreaDeleteId', $area->id)
            ->call('deleteUseArea', $area->id)
            ->assertHasNoErrors()
            ->assertSet('confirmingUseAreaDeleteId', null);

        $this->assertDatabaseMissing('inventory_use_areas', [
            'id' => $area->id,
        ]);

        $usedArea = InventoryUseArea::create([
            'company_id' => $company->id,
            'name' => 'Venta',
            'slug' => 'venta',
            'description' => 'Productos para venta',
        ]);

        InventoryProduct::create([
            'company_id' => $company->id,
            'inventory_use_area_id' => $usedArea->id,
            'code' => 'CRE-VEN',
            'name' => 'Crema venta',
            'unit_name' => 'unidad',
            'units_per_package' => 1,
            'purchase_cost' => 10,
            'minimum_stock' => 1,
        ]);

        Livewire::test(InventoryManager::class)
            ->call('confirmDeleteUseArea', $usedArea->id)
            ->call('deleteUseArea', $usedArea->id)
            ->assertHasErrors(['deleteUseArea']);

        $this->assertDatabaseHas('inventory_use_areas', [
            'id' => $usedArea->id,
            'name' => 'Venta',
        ]);
    }

    public function test_products_with_current_month_use_or_sales_cannot_be_renamed_or_deleted(): void
    {
        [$admin, $company, $branch] = $this->companyContext('rumika-demo');

        $this->actingAs($admin);
        session(['active_branch_id' => $branch->id]);

        Livewire::test(InventoryManager::class)
            ->set('productName', 'Gel conductor ultrasonido')
            ->set('unitName', 'ml')
            ->set('unitsPerPackage', '1000')
            ->set('purchaseCost', '0.18')
            ->set('minimumStock', '100')
            ->call('saveProduct')
            ->assertHasNoErrors();

        $product = InventoryProduct::query()->where('company_id', $company->id)->where('name', 'Gel conductor ultrasonido')->firstOrFail();

        Livewire::test(InventoryManager::class)
            ->set('movementType', 'purchase')
            ->set('movementProductId', $product->id)
            ->set('movementQuantity', '50')
            ->set('movementUnitCost', '0.18')
            ->set('movementLotCode', 'GEL-001')
            ->set('movementReceivedAt', now()->toDateString())
            ->call('saveMovement')
            ->assertHasNoErrors();

        $batch = InventoryProductBatch::query()
            ->where('inventory_product_id', $product->id)
            ->orderByDesc('current_quantity')
            ->firstOrFail();

        Livewire::test(InventoryManager::class, ['screen' => 'operations'])
            ->set('movementType', 'cabinet')
            ->set('movementProductId', $product->id)
            ->set('movementBatchId', $batch->id)
            ->set('movementQuantity', '5')
            ->call('saveMovement')
            ->assertHasNoErrors();

        Livewire::test(InventoryManager::class)
            ->call('editProduct', $product->id)
            ->set('productName', 'Gel conductor cambiado')
            ->call('saveProduct')
            ->assertHasErrors(['productName']);

        Livewire::test(InventoryManager::class)
            ->call('confirmDeleteProduct', $product->id)
            ->call('deleteProduct', $product->id)
            ->assertHasErrors(['deleteProduct']);

        $this->assertDatabaseHas('inventory_products', [
            'id' => $product->id,
            'name' => 'Gel conductor ultrasonido',
        ]);

        Livewire::test(InventoryManager::class)
            ->set('productName', 'Producto sin uso mensual')
            ->set('unitName', 'unidad')
            ->set('unitsPerPackage', '1')
            ->set('purchaseCost', '1')
            ->set('minimumStock', '0')
            ->call('saveProduct')
            ->assertHasNoErrors();

        $deletable = InventoryProduct::query()->where('company_id', $company->id)->where('name', 'Producto sin uso mensual')->firstOrFail();

        Livewire::test(InventoryManager::class)
            ->call('confirmDeleteProduct', $deletable->id)
            ->call('deleteProduct', $deletable->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('inventory_products', [
            'id' => $deletable->id,
        ]);
    }

    public function test_inventory_counts_can_be_opened_by_zone_and_store_product_details(): void
    {
        [$admin, $company, $branch] = $this->companyContext('rumika-demo');
        $area = InventoryUseArea::create([
            'company_id' => $company->id,
            'name' => 'Gabinete',
            'slug' => 'gabinete',
        ]);

        $this->actingAs($admin);
        session(['active_branch_id' => $branch->id]);

        Livewire::test(InventoryManager::class)
            ->set('productName', 'Crema cabina')
            ->set('productUseAreaId', $area->id)
            ->set('unitName', 'unidad')
            ->set('unitsPerPackage', '1')
            ->set('purchaseCost', '12')
            ->set('minimumStock', '0')
            ->call('saveProduct')
            ->assertHasNoErrors();

        $product = InventoryProduct::where('company_id', $company->id)->where('name', 'Crema cabina')->firstOrFail();

        Livewire::test(InventoryManager::class)
            ->set('movementType', 'purchase')
            ->set('movementProductId', $product->id)
            ->set('movementQuantity', '10')
            ->set('movementUnitCost', '12')
            ->set('movementLotCode', 'CAB-001')
            ->call('saveMovement')
            ->assertHasNoErrors();

        Livewire::test(InventoryManager::class)
            ->set('countUseAreaId', (string) $area->id)
            ->call('openInventoryCount')
            ->assertHasNoErrors();

        $count = InventoryCount::where('company_id', $company->id)
            ->where('branch_id', $branch->id)
            ->where('inventory_use_area_id', $area->id)
            ->where('status', 'in_process')
            ->firstOrFail();

        $this->assertDatabaseHas('inventory_count_items', [
            'inventory_count_id' => $count->id,
            'inventory_product_id' => $product->id,
            'opening_quantity' => '10.00',
            'status' => 'existing',
        ]);

        $countItem = InventoryCountItem::where('inventory_count_id', $count->id)
            ->where('inventory_product_id', $product->id)
            ->firstOrFail();

        $batch = InventoryProductBatch::where('inventory_product_id', $product->id)
            ->orderByDesc('current_quantity')
            ->firstOrFail();

        Livewire::test(InventoryManager::class)
            ->call('openCountDetails', $count->id)
            ->set('countOpeningQuantities', [$countItem->id => '15'])
            ->call('saveCountOpeningQuantities')
            ->assertHasNoErrors();

        $this->assertSame('15.00', $batch->refresh()->current_quantity);
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_count_id' => $count->id,
            'inventory_product_id' => $product->id,
            'type' => 'opening_adjustment',
            'quantity' => '5.00',
        ]);

        Livewire::test(InventoryManager::class, ['screen' => 'operations'])
            ->set('movementType', 'cabinet')
            ->set('movementProductId', $product->id)
            ->set('movementBatchId', $batch->id)
            ->set('movementQuantity', '3')
            ->call('saveMovement')
            ->assertHasNoErrors();

        Livewire::test(InventoryManager::class)
            ->set('productName', 'Producto nuevo zona')
            ->set('productUseAreaId', $area->id)
            ->set('unitName', 'unidad')
            ->set('unitsPerPackage', '1')
            ->set('purchaseCost', '5')
            ->set('minimumStock', '0')
            ->call('saveProduct')
            ->assertHasNoErrors();

        $batch->update(['current_quantity' => 10]);

        Livewire::test(InventoryManager::class)
            ->call('confirmCloseInventory', $count->id)
            ->call('closeInventory')
            ->assertHasNoErrors();

        $this->assertSame('closed', $count->refresh()->status);
        $this->assertDatabaseHas('inventory_count_items', [
            'inventory_count_id' => $count->id,
            'inventory_product_id' => $product->id,
            'opening_quantity' => '15.00',
            'movement_out_quantity' => '3.00',
            'expected_quantity' => '12.00',
            'closed_quantity' => '10.00',
            'difference_quantity' => '-2.00',
        ]);
        $this->assertSame(1, InventoryCountItem::where('inventory_count_id', $count->id)->where('status', 'new')->count());

        Livewire::test(InventoryManager::class)
            ->call('openCountDetails', $count->id)
            ->set('countDetailSearch', 'nuevo')
            ->assertSee('Producto nuevo')
            ->set('countDetailSearch', '')
            ->set('countOnlyDifferences', true)
            ->assertSee('Crema cabina')
            ->assertSee('Mostrando 1 de 2 producto(s)')
            ->call('confirmDeleteInventoryCount', $count->id)
            ->call('deleteInventoryCount')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('inventory_counts', [
            'id' => $count->id,
        ]);
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
            'enabled_modules' => ['agenda', 'clientes', 'historial', 'inventario'],
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
