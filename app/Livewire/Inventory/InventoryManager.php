<?php

namespace App\Livewire\Inventory;

use App\Models\Branch;
use App\Models\Company;
use App\Models\InventoryAsset;
use App\Models\InventoryAssetRepair;
use App\Models\InventoryBrand;
use App\Models\InventoryCount;
use App\Models\InventoryCountItem;
use App\Models\InventoryMovement;
use App\Models\InventoryProduct;
use App\Models\InventoryProductBatch;
use App\Models\InventorySupplier;
use App\Models\TreatmentPaymentItem;
use App\Models\InventoryUseArea;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryManager extends Component
{
    use WithPagination;

    public string $screen = 'catalog';

    public string $activeTab = 'products';
    public string $search = '';
    public string $brandFilter = '';
    public string $useAreaFilter = '';
    public string $movementType = 'purchase';

    public bool $showProductModal = false;
    public bool $showMovementModal = false;
    public bool $showSupplierModal = false;
    public bool $showBrandModal = false;
    public bool $showUseAreaModal = false;
    public bool $showAssetModal = false;
    public bool $showRepairModal = false;
    public bool $showWasteAssetModal = false;
    public bool $showCountDetailsModal = false;
    public bool $showProductMovementsModal = false;
    public ?int $closingCountId = null;
    public string $countUseAreaId = '';
    public ?int $selectedCountId = null;
    public ?int $selectedProductId = null;
    public string $productMovementTab = 'sales';
    public string $countDetailSearch = '';
    public bool $countOnlyDifferences = false;
    public array $countOpeningQuantities = [];
    public ?int $confirmingCountDeleteId = null;

    public ?int $editingProductId = null;
    public ?int $confirmingProductDeleteId = null;
    public ?int $editingUseAreaId = null;
    public ?int $confirmingUseAreaDeleteId = null;
    public ?int $editingAssetId = null;
    public ?int $repairAssetId = null;
    public ?int $wasteAssetId = null;

    public string $supplierName = '';
    public string $supplierContact = '';
    public string $supplierPhone = '';
    public string $supplierEmail = '';

    public string $brandName = '';
    public ?int $brandSupplierId = null;

    public string $useAreaName = '';
    public string $useAreaDescription = '';

    public string $productName = '';
    public string $productDescription = '';
    public ?int $productSupplierId = null;
    public ?int $productBrandId = null;
    public ?int $productUseAreaId = null;
    public string $unitName = 'unidad';
    public string $packageName = '';
    public string $unitsPerPackage = '1';
    public string $purchaseCost = '0';
    public string $minimumStock = '0';

    public ?int $movementProductId = null;
    public ?int $movementBatchId = null;
    public ?int $relatedBranchId = null;
    public string $movementQuantity = '';
    public string $movementUnitCost = '';
    public string $movementLotCode = '';
    public string $movementExpiresAt = '';
    public string $movementReceivedAt = '';
    public string $movementReference = '';
    public string $movementReason = '';

    public string $assetName = '';
    public string $assetCategory = '';
    public string $assetPurchaseAmount = '';
    public string $assetPurchaseDate = '';
    public string $assetStatus = 'active';

    public string $repairAmount = '';
    public string $repairDate = '';
    public string $repairDescription = '';
    public string $assetWasteReason = '';

    public function mount(string $screen = 'catalog'): void
    {
        $this->screen = in_array($screen, ['catalog', 'operations'], true) ? $screen : 'catalog';
        $this->activeTab = $this->screen === 'operations' ? 'movements' : 'products';
    }

    public function setActiveTab(string $tab): void
    {
        $allowedTabs = $this->screen === 'operations'
            ? ['movements', 'waste']
            : ['products', 'suppliers', 'movements', 'assets', 'counts'];

        if (! in_array($tab, $allowedTabs, true)) {
            return;
        }

        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedBrandFilter(): void
    {
        $this->resetPage();
    }

    public function updatedUseAreaFilter(): void
    {
        $this->resetPage();
    }

    #[On('branch-switched')]
    public function refreshBranchContext(): void
    {
        $this->resetPage();
        $this->resetMovementForm();
        $this->reset(['closingCountId', 'selectedCountId', 'selectedProductId']);
    }

    public function createSupplier(): void
    {
        $this->reset(['supplierName', 'supplierContact', 'supplierPhone', 'supplierEmail']);
        $this->showSupplierModal = true;
    }

    public function saveSupplier(): void
    {
        $validated = $this->validate([
            'supplierName' => ['required', 'string', 'max:140'],
            'supplierContact' => ['nullable', 'string', 'max:120'],
            'supplierPhone' => ['nullable', 'string', 'max:40'],
            'supplierEmail' => ['nullable', 'email', 'max:120'],
        ]);

        $this->company()->inventorySuppliers()->create([
            'name' => $validated['supplierName'],
            'contact_name' => $validated['supplierContact'] ?: null,
            'phone' => $validated['supplierPhone'] ?: null,
            'email' => $validated['supplierEmail'] ?: null,
        ]);

        $this->showSupplierModal = false;
    }

    public function createBrand(): void
    {
        $this->reset(['brandName', 'brandSupplierId']);
        $this->showBrandModal = true;
    }

    public function saveBrand(): void
    {
        $supplierIds = $this->company()->inventorySuppliers()->pluck('id')->all();

        $validated = $this->validate([
            'brandName' => ['required', 'string', 'max:140'],
            'brandSupplierId' => ['nullable', Rule::in($supplierIds)],
        ]);

        $this->company()->inventoryBrands()->create([
            'name' => $validated['brandName'],
            'inventory_supplier_id' => $validated['brandSupplierId'],
        ]);

        $this->showBrandModal = false;
    }

    public function createUseArea(): void
    {
        $this->reset(['editingUseAreaId', 'useAreaName', 'useAreaDescription']);
        $this->resetErrorBag();
        $this->showUseAreaModal = true;
    }

    public function editUseArea(int $useAreaId): void
    {
        $area = $this->company()->inventoryUseAreas()->whereKey($useAreaId)->firstOrFail();

        $this->editingUseAreaId = $area->id;
        $this->useAreaName = $area->name;
        $this->useAreaDescription = $area->description ?? '';
        $this->resetErrorBag();
        $this->showUseAreaModal = true;
    }

    public function closeUseAreaModal(): void
    {
        $this->showUseAreaModal = false;
        $this->reset(['editingUseAreaId', 'useAreaName', 'useAreaDescription']);
        $this->resetErrorBag();
    }

    public function saveUseArea(): void
    {
        $company = $this->company();

        $validated = $this->validate([
            'useAreaName' => ['required', 'string', 'max:120'],
            'useAreaDescription' => ['nullable', 'string', 'max:180'],
        ]);
        $slug = Str::slug($validated['useAreaName']) ?: 'area-uso';
        $area = $this->editingUseAreaId
            ? $company->inventoryUseAreas()->whereKey($this->editingUseAreaId)->firstOrFail()
            : new InventoryUseArea(['company_id' => $company->id]);

        $slugExists = $company->inventoryUseAreas()
            ->where('slug', $slug)
            ->when($area->exists, fn ($query) => $query->where('id', '!=', $area->id))
            ->exists();

        if ($slugExists) {
            $this->addError('useAreaName', 'Ya existe un area de uso con ese nombre.');

            return;
        }

        $area->fill([
            'name' => $validated['useAreaName'],
            'slug' => $slug,
            'description' => $validated['useAreaDescription'] ?: null,
        ]);
        $area->save();

        $this->closeUseAreaModal();
    }

    public function confirmDeleteUseArea(int $useAreaId): void
    {
        $area = $this->company()->inventoryUseAreas()->whereKey($useAreaId)->firstOrFail();

        $this->resetErrorBag();
        $this->confirmingUseAreaDeleteId = $area->id;
    }

    public function cancelDeleteUseArea(): void
    {
        $this->confirmingUseAreaDeleteId = null;
    }

    public function deleteUseArea(int $useAreaId): void
    {
        $company = $this->company();
        $area = $company->inventoryUseAreas()->whereKey($useAreaId)->firstOrFail();

        if (
            $area->products()->exists()
            || $company->inventoryCounts()->where('inventory_use_area_id', $area->id)->exists()
            || $company->inventoryCountItems()->where('inventory_use_area_id', $area->id)->exists()
        ) {
            $this->addError('deleteUseArea', 'No se puede eliminar esta zona porque ya tiene productos o inventarios asociados.');

            return;
        }

        $area->delete();
        $this->confirmingUseAreaDeleteId = null;

        if ((int) $this->useAreaFilter === $area->id) {
            $this->useAreaFilter = '';
        }

        if ((int) $this->countUseAreaId === $area->id) {
            $this->countUseAreaId = '';
        }
    }

    public function createProduct(): void
    {
        $this->resetProductForm();
        $this->showProductModal = true;
    }

    public function editProduct(int $productId): void
    {
        $product = $this->company()->inventoryProducts()->whereKey($productId)->firstOrFail();

        $this->editingProductId = $product->id;
        $this->productName = $product->name;
        $this->productDescription = $product->description ?? '';
        $this->productSupplierId = $product->inventory_supplier_id;
        $this->productBrandId = $product->inventory_brand_id;
        $this->productUseAreaId = $product->inventory_use_area_id;
        $this->unitName = $product->unit_name;
        $this->packageName = $product->package_name ?? '';
        $this->unitsPerPackage = (string) $product->units_per_package;
        $this->purchaseCost = (string) $product->purchase_cost;
        $this->minimumStock = (string) $product->minimum_stock;
        $this->showProductModal = true;
    }

    public function saveProduct(): void
    {
        $company = $this->company();
        $supplierIds = $company->inventorySuppliers()->pluck('id')->all();
        $brandIds = $company->inventoryBrands()->pluck('id')->all();
        $useAreaIds = $company->inventoryUseAreas()->pluck('id')->all();

        $validated = $this->validate([
            'productName' => ['required', 'string', 'max:160'],
            'productDescription' => ['nullable', 'string', 'max:500'],
            'productSupplierId' => ['nullable', Rule::in($supplierIds)],
            'productBrandId' => ['nullable', Rule::in($brandIds)],
            'productUseAreaId' => ['nullable', Rule::in($useAreaIds)],
            'unitName' => ['required', 'string', 'max:40'],
            'packageName' => ['nullable', 'string', 'max:40'],
            'unitsPerPackage' => ['required', 'integer', 'min:1', 'max:100000'],
            'purchaseCost' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'minimumStock' => ['required', 'integer', 'min:0', 'max:1000000'],
        ]);

        $product = $this->editingProductId
            ? $company->inventoryProducts()->whereKey($this->editingProductId)->firstOrFail()
            : new InventoryProduct([
                'company_id' => $company->id,
                'code' => $this->generateProductCode($validated['productName']),
            ]);

        if (
            $this->editingProductId
            && $product->name !== $validated['productName']
            && $this->productHasCurrentMonthUseOrSales($product)
        ) {
            $this->addError('productName', 'No se puede cambiar el nombre porque este producto ya tiene uso o ventas en el mes actual.');

            return;
        }

        $product->fill([
            'inventory_supplier_id' => $validated['productSupplierId'],
            'inventory_brand_id' => $validated['productBrandId'],
            'inventory_use_area_id' => $validated['productUseAreaId'],
            'name' => $validated['productName'],
            'description' => $validated['productDescription'] ?: null,
            'unit_name' => $validated['unitName'],
            'package_name' => $validated['packageName'] ?: null,
            'units_per_package' => $validated['unitsPerPackage'],
            'purchase_cost' => $validated['purchaseCost'],
            'minimum_stock' => $validated['minimumStock'],
        ]);
        $product->save();

        if (! $this->editingProductId) {
            $this->createCatalogBatchesForProduct($company, $product);
        }

        $this->showProductModal = false;
        $this->resetProductForm();
    }

    public function confirmDeleteProduct(int $productId): void
    {
        $this->resetErrorBag();
        $this->confirmingProductDeleteId = $productId;
    }

    public function cancelDeleteProduct(): void
    {
        $this->confirmingProductDeleteId = null;
    }

    public function deleteProduct(int $productId): void
    {
        $product = $this->company()->inventoryProducts()->whereKey($productId)->firstOrFail();

        if ($this->productHasCurrentMonthUseOrSales($product)) {
            $this->addError('deleteProduct', 'No se puede eliminar porque este producto ya tiene uso o ventas en el mes actual.');

            return;
        }

        $product->delete();
        $this->confirmingProductDeleteId = null;

        if ($this->editingProductId === $productId) {
            $this->resetProductForm();
            $this->showProductModal = false;
        }
    }

    public function openMovement(string $type): void
    {
        $allowedTypes = $this->screen === 'operations'
            ? ['stock_out', 'transfer', 'waste', 'adjustment', 'cabinet']
            : ['purchase'];

        if (! in_array($type, $allowedTypes, true)) {
            return;
        }

        $this->resetMovementForm();
        $this->movementType = $type;
        $this->movementReceivedAt = now()->format('Y-m-d');
        $this->showMovementModal = true;
    }

    public function saveMovement(): void
    {
        $company = $this->company();
        $branch = $this->activeBranch();
        $productIds = $company->inventoryProducts()->pluck('id')->all();
        $branchIds = $this->availableBranches()->pluck('id')->reject(fn ($id) => $id === $branch->id)->all();
        $batches = $company->inventoryBatches()->where('branch_id', $branch->id)->pluck('id')->all();

        $rules = [
            'movementProductId' => ['required', Rule::in($productIds)],
            'movementQuantity' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'movementUnitCost' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'movementReference' => ['nullable', 'string', 'max:120'],
            'movementReason' => ['nullable', 'string', 'max:500'],
        ];

        if ($this->movementType === 'purchase') {
            $rules += [
                'movementLotCode' => ['nullable', 'string', 'max:80'],
                'movementExpiresAt' => ['nullable', 'date'],
                'movementReceivedAt' => ['nullable', 'date'],
            ];
        } else {
            $rules['movementBatchId'] = ['required', Rule::in($batches)];
        }

        if ($this->movementType === 'transfer') {
            $rules['relatedBranchId'] = ['required', Rule::in($branchIds)];
        }

        if (in_array($this->movementType, ['stock_out', 'waste', 'adjustment'], true)) {
            $rules['movementReason'] = ['required', 'string', 'max:500'];
        }

        $validated = $this->validate($rules);

        DB::transaction(function () use ($company, $branch, $validated) {
            $quantity = (float) $validated['movementQuantity'];
            $unitCost = (float) ($validated['movementUnitCost'] ?: 0);

            if ($this->movementType === 'purchase') {
                $product = $company->inventoryProducts()->whereKey($validated['movementProductId'])->firstOrFail();
                $count = $this->currentInventoryCountForProduct($branch, $product->id);
                $lotCode = $validated['movementLotCode'] ?: $this->generateLotCode($product->name, $branch->id);
                $batch = InventoryProductBatch::query()->firstOrCreate(
                    [
                        'branch_id' => $branch->id,
                        'inventory_product_id' => $product->id,
                        'lot_code' => $lotCode,
                    ],
                    [
                        'company_id' => $company->id,
                        'expires_at' => $validated['movementExpiresAt'] ?: null,
                        'received_at' => $validated['movementReceivedAt'] ?: now()->toDateString(),
                        'unit_cost' => $unitCost ?: (float) $product->purchase_cost,
                        'initial_quantity' => 0,
                        'current_quantity' => 0,
                    ],
                );

                $batch->increment('initial_quantity', $quantity);
                $batch->increment('current_quantity', $quantity);
                $unitCost = $unitCost ?: (float) $batch->unit_cost;

                $this->recordMovement($company, $branch, $count, $product->id, $batch->id, 'purchase', $quantity, $unitCost, $validated);

                return;
            }

            $batch = $company->inventoryBatches()
                ->where('branch_id', $branch->id)
                ->whereKey($validated['movementBatchId'])
                ->firstOrFail();

            if ((float) $batch->current_quantity < $quantity) {
                $this->addError('movementQuantity', 'No hay cantidad suficiente en este lote.');

                return;
            }

            $unitCost = $unitCost ?: (float) $batch->unit_cost;
            $type = $this->movementType === 'transfer' ? 'transfer_out' : $this->movementType;
            $count = $this->currentInventoryCountForProduct($branch, $batch->inventory_product_id);
            $batch->decrement('current_quantity', $quantity);
            $this->recordMovement($company, $branch, $count, $batch->inventory_product_id, $batch->id, $type, $quantity, $unitCost, $validated);

            if ($this->movementType === 'transfer') {
                $targetBranch = $this->availableBranches()->where('id', $validated['relatedBranchId'])->firstOrFail();
                $targetCount = $this->currentInventoryCountForProduct($targetBranch, $batch->inventory_product_id);
                $targetBatch = InventoryProductBatch::query()->firstOrCreate(
                    [
                        'branch_id' => $targetBranch->id,
                        'inventory_product_id' => $batch->inventory_product_id,
                        'lot_code' => $batch->lot_code,
                    ],
                    [
                        'company_id' => $company->id,
                        'expires_at' => $batch->expires_at,
                        'received_at' => now()->toDateString(),
                        'unit_cost' => $unitCost,
                        'initial_quantity' => 0,
                        'current_quantity' => 0,
                    ],
                );
                $targetBatch->increment('initial_quantity', $quantity);
                $targetBatch->increment('current_quantity', $quantity);
                $this->recordMovement($company, $targetBranch, $targetCount, $batch->inventory_product_id, $targetBatch->id, 'transfer_in', $quantity, $unitCost, $validated + ['relatedBranchId' => $branch->id]);
            }
        });

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $this->showMovementModal = false;
        $this->resetMovementForm();
    }

    public function createAsset(): void
    {
        $this->resetAssetForm();
        $this->showAssetModal = true;
    }

    public function editAsset(int $assetId): void
    {
        $asset = $this->company()->inventoryAssets()->whereKey($assetId)->firstOrFail();

        $this->editingAssetId = $asset->id;
        $this->assetName = $asset->name;
        $this->assetCategory = $asset->category ?? '';
        $this->assetPurchaseAmount = (string) $asset->purchase_amount;
        $this->assetPurchaseDate = $asset->purchase_date?->format('Y-m-d') ?? '';
        $this->assetStatus = $asset->status;
        $this->showAssetModal = true;
    }

    public function saveAsset(): void
    {
        $company = $this->company();
        $branch = $this->activeBranch();

        $validated = $this->validate([
            'assetName' => ['required', 'string', 'max:160'],
            'assetCategory' => ['nullable', 'string', 'max:120'],
            'assetPurchaseAmount' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'assetPurchaseDate' => ['nullable', 'date'],
            'assetStatus' => ['required', 'in:active,maintenance,wasted'],
        ]);

        $asset = $this->editingAssetId
            ? $company->inventoryAssets()->whereKey($this->editingAssetId)->firstOrFail()
            : new InventoryAsset([
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'code' => $this->generateAssetCode($validated['assetName']),
            ]);

        $asset->fill([
            'name' => $validated['assetName'],
            'category' => $validated['assetCategory'] ?: null,
            'purchase_amount' => $validated['assetPurchaseAmount'],
            'purchase_date' => $validated['assetPurchaseDate'] ?: null,
            'status' => $validated['assetStatus'],
        ]);
        $asset->save();

        $this->showAssetModal = false;
        $this->resetAssetForm();
    }

    public function openRepair(int $assetId): void
    {
        $this->company()->inventoryAssets()->whereKey($assetId)->firstOrFail();
        $this->reset(['repairAmount', 'repairDate', 'repairDescription']);
        $this->repairAssetId = $assetId;
        $this->showRepairModal = true;
    }

    public function saveRepair(): void
    {
        $asset = $this->company()->inventoryAssets()->whereKey($this->repairAssetId)->firstOrFail();

        $validated = $this->validate([
            'repairAmount' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'repairDate' => ['nullable', 'date'],
            'repairDescription' => ['nullable', 'string', 'max:500'],
        ]);

        InventoryAssetRepair::query()->create([
            'company_id' => $asset->company_id,
            'branch_id' => $asset->branch_id,
            'inventory_asset_id' => $asset->id,
            'amount' => $validated['repairAmount'],
            'repaired_at' => $validated['repairDate'] ?: now()->toDateString(),
            'description' => $validated['repairDescription'] ?: null,
        ]);
        $asset->increment('repair_total', (float) $validated['repairAmount']);
        $asset->update(['status' => 'maintenance']);

        $this->showRepairModal = false;
    }

    public function openWasteAsset(int $assetId): void
    {
        $this->company()->inventoryAssets()->whereKey($assetId)->firstOrFail();
        $this->wasteAssetId = $assetId;
        $this->assetWasteReason = '';
        $this->showWasteAssetModal = true;
    }

    public function wasteAsset(): void
    {
        $validated = $this->validate([
            'assetWasteReason' => ['required', 'string', 'max:500'],
        ]);

        $this->company()->inventoryAssets()->whereKey($this->wasteAssetId)->firstOrFail()->update([
            'status' => 'wasted',
            'wasted_at' => now()->toDateString(),
            'waste_reason' => $validated['assetWasteReason'],
        ]);

        $this->showWasteAssetModal = false;
    }

    public function openInventoryCount(): void
    {
        $company = $this->company();
        $branch = $this->activeBranch();
        $useAreaId = $this->countUseAreaId !== '' ? (int) $this->countUseAreaId : null;

        if (
            $company->inventoryCounts()
                ->where('branch_id', $branch->id)
                ->where('inventory_use_area_id', $useAreaId)
                ->where('status', 'in_process')
                ->exists()
        ) {
            $this->addError('countUseAreaId', 'Ya existe un inventario abierto para esta zona.');

            return;
        }

        $area = $useAreaId ? $company->inventoryUseAreas()->whereKey($useAreaId)->firstOrFail() : null;
        $count = $company->inventoryCounts()->create([
            'branch_id' => $branch->id,
            'inventory_use_area_id' => $useAreaId,
            'name' => 'Inventario '.$branch->name.' '.($area?->name ?? 'General').' '.now()->format('Y-m-d H:i'),
            'status' => 'in_process',
            'opened_at' => now(),
            'totals' => [
                'opening_stock' => $this->stockForArea($company, $branch, $useAreaId),
                'opening_value' => $this->stockValueForArea($company, $branch, $useAreaId),
            ],
        ]);

        $this->snapshotOpeningItems($count);
        $this->countUseAreaId = '';
        $this->resetErrorBag('countUseAreaId');
        $this->resetPage();
    }

    public function confirmCloseInventory(?int $countId = null): void
    {
        $this->closingCountId = $countId ?: $this->currentInventoryCount($this->activeBranch())->id;
    }

    public function closeInventory(): void
    {
        $company = $this->company();
        $branch = $this->activeBranch();
        $count = $company->inventoryCounts()
            ->where('branch_id', $branch->id)
            ->whereKey($this->closingCountId)
            ->firstOrFail();
        $useAreaId = $count->inventory_use_area_id;

        $stockValue = $company->inventoryBatches()
            ->where('branch_id', $branch->id)
            ->whereHas('product', fn ($query) => $query->when($useAreaId, fn ($areaQuery) => $areaQuery->where('inventory_use_area_id', $useAreaId)))
            ->selectRaw('COALESCE(SUM(current_quantity * unit_cost), 0) as total')
            ->value('total');
        $wasteValue = $company->inventoryMovements()
            ->where('branch_id', $branch->id)
            ->where('inventory_count_id', $count->id)
            ->where('type', 'waste')
            ->sum('total_cost');
        $this->refreshCountItems($count, true);

        $count->update([
            'status' => 'closed',
            'closed_at' => now(),
            'totals' => [
                'stock_value' => round((float) $stockValue, 2),
                'waste_value' => round((float) $wasteValue, 2),
                'closed_stock' => $this->stockForArea($company, $branch, $useAreaId),
                'products' => $count->items()->count(),
                'new_products' => $count->items()->where('status', 'new')->count(),
            ],
        ]);

        $this->closingCountId = null;
    }

    public function openCountDetails(int $countId): void
    {
        $count = $this->company()->inventoryCounts()
            ->where('branch_id', $this->activeBranch()->id)
            ->whereKey($countId)
            ->first();

        if ($count && $count->status === 'in_process') {
            $this->refreshCountItems($count);
        }

        $this->selectedCountId = $count?->id;
        $this->countDetailSearch = '';
        $this->countOnlyDifferences = false;
        $this->countOpeningQuantities = $count
            ? $count->items()->pluck('opening_quantity', 'id')->map(fn ($quantity) => number_format((float) $quantity, 2, '.', ''))->all()
            : [];
        $this->showCountDetailsModal = (bool) $this->selectedCountId;
    }

    public function confirmDeleteInventoryCount(int $countId): void
    {
        $this->resetErrorBag();
        $this->confirmingCountDeleteId = $this->company()->inventoryCounts()
            ->where('branch_id', $this->activeBranch()->id)
            ->whereKey($countId)
            ->value('id');
    }

    public function cancelDeleteInventoryCount(): void
    {
        $this->confirmingCountDeleteId = null;
    }

    public function deleteInventoryCount(): void
    {
        if (! $this->canManageInventoryClosures()) {
            $this->addError('countDelete', 'Solo administrador o super administrador puede eliminar cierres de inventario.');

            return;
        }

        $count = $this->company()->inventoryCounts()
            ->where('branch_id', $this->activeBranch()->id)
            ->whereKey($this->confirmingCountDeleteId)
            ->firstOrFail();

        if ($this->selectedCountId === $count->id) {
            $this->showCountDetailsModal = false;
            $this->selectedCountId = null;
        }

        $count->delete();
        $this->confirmingCountDeleteId = null;
        $this->resetPage();
    }

    public function saveCountOpeningQuantities(): void
    {
        $count = $this->company()->inventoryCounts()
            ->with('items.product')
            ->where('branch_id', $this->activeBranch()->id)
            ->whereKey($this->selectedCountId)
            ->firstOrFail();

        if ($count->status !== 'in_process') {
            $this->addError('countOpeningQuantities', 'Solo puedes editar la apertura de un inventario en proceso.');

            return;
        }

        $this->validate([
            'countOpeningQuantities' => ['array'],
            'countOpeningQuantities.*' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
        ]);

        DB::transaction(function () use ($count) {
            foreach ($count->items as $item) {
                $newOpening = round((float) ($this->countOpeningQuantities[$item->id] ?? $item->opening_quantity), 2);
                $oldOpening = round((float) $item->opening_quantity, 2);
                $delta = round($newOpening - $oldOpening, 2);

                if (abs($delta) > 0.009) {
                    $this->applyOpeningStockDelta($count, $item, $delta, $oldOpening, $newOpening);
                }

                $movementIn = $this->productMovementQuantity($count, $item->inventory_product_id, ['purchase', 'transfer_in']);
                $movementOut = $this->productMovementQuantity($count, $item->inventory_product_id, ['sale', 'stock_out', 'cabinet', 'waste', 'transfer_out', 'adjustment']);
                $closed = $this->productStock($count->branch, $item->inventory_product_id);
                $unitCost = $this->productUnitCost($count->branch, $item->inventory_product_id, (float) $item->product?->purchase_cost);
                $expected = round($newOpening + $movementIn - $movementOut, 2);

                $item->update([
                    'opening_quantity' => $newOpening,
                    'movement_in_quantity' => $movementIn,
                    'movement_out_quantity' => $movementOut,
                    'expected_quantity' => $expected,
                    'closed_quantity' => $closed,
                    'difference_quantity' => round($closed - $expected, 2),
                    'unit_cost' => $unitCost,
                    'stock_value' => $closed * $unitCost,
                    'notes' => $item->status === 'new' ? 'Producto nuevo' : $item->notes,
                ]);
            }

            $count->update([
                'totals' => [
                    ...($count->totals ?? []),
                    'opening_stock' => $count->items()->sum('opening_quantity'),
                    'opening_value' => (float) $count->items()->selectRaw('COALESCE(SUM(opening_quantity * unit_cost), 0) as total')->value('total'),
                ],
            ]);
        });

        $this->openCountDetails($count->id);
    }

    public function openProductMovements(int $productId): void
    {
        $this->selectedProductId = $this->company()->inventoryProducts()->whereKey($productId)->value('id');
        $this->productMovementTab = 'sales';
        $this->showProductMovementsModal = (bool) $this->selectedProductId;
    }

    public function setProductMovementTab(string $tab): void
    {
        if (! in_array($tab, ['sales', 'entries', 'waste', 'transfers', 'adjustments'], true)) {
            return;
        }

        $this->productMovementTab = $tab;
    }

    public function exportMovements(): StreamedResponse
    {
        $company = $this->company();
        $branch = $this->activeBranch();
        $filename = 'inventario-movimientos-'.$branch->slug.'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($company, $branch) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Fecha', 'Producto', 'Codigo', 'Lote', 'Tipo', 'Cantidad', 'Costo unitario', 'Total', 'Sucursal', 'Sucursal relacionada', 'Referencia', 'Motivo']);

            $company->inventoryMovements()
                ->with(['product', 'batch', 'branch', 'relatedBranch'])
                ->where('branch_id', $branch->id)
                ->latest('moved_at')
                ->chunk(200, function ($movements) use ($handle) {
                    foreach ($movements as $movement) {
                        fputcsv($handle, [
                            $movement->moved_at?->format('Y-m-d H:i'),
                            $movement->product?->name,
                            $movement->product?->code,
                            $movement->batch?->lot_code,
                            $movement->type,
                            $movement->quantity,
                            $movement->unit_cost,
                            $movement->total_cost,
                            $movement->branch?->name,
                            $movement->relatedBranch?->name,
                            $movement->reference,
                            $movement->reason,
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function exportCountDetails(string $format): StreamedResponse
    {
        $count = $this->company()->inventoryCounts()
            ->with(['branch', 'useArea', 'items.product.brand', 'items.product.useArea'])
            ->where('branch_id', $this->activeBranch()->id)
            ->whereKey($this->selectedCountId)
            ->firstOrFail();
        $items = $this->filteredCountItems($count);
        $baseName = Str::slug($count->name ?: 'inventario').'-detalle-'.now()->format('Ymd-His');

        if ($format === 'pdf') {
            $pdf = $this->countDetailsPdf($count, $items);

            return response()->streamDownload(
                fn () => print($pdf),
                $baseName.'.pdf',
                ['Content-Type' => 'application/pdf'],
            );
        }

        return response()->streamDownload(function () use ($count, $items) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Inventario', $count->name]);
            fputcsv($handle, ['Sucursal', $count->branch?->name]);
            fputcsv($handle, ['Zona', $count->useArea?->name ?? 'General']);
            fputcsv($handle, []);
            fputcsv($handle, ['Producto', 'Codigo', 'Marca', 'Zona', 'Estado', 'Apertura', 'Entradas', 'Salidas', 'Esperado', 'Cierre', 'Diferencia']);

            foreach ($items as $item) {
                fputcsv($handle, [
                    $item->product?->name,
                    $item->product?->code,
                    $item->brand?->name ?? $item->product?->brand?->name ?? 'GENERAL',
                    $item->useArea?->name ?? $item->product?->useArea?->name ?? 'Sin zona',
                    $item->status === 'new' ? 'Producto nuevo' : 'Existente',
                    $item->opening_quantity,
                    $item->movement_in_quantity,
                    $item->movement_out_quantity,
                    $item->expected_quantity,
                    $item->closed_quantity,
                    $item->difference_quantity,
                ]);
            }

            fclose($handle);
        }, $baseName.'.csv', ['Content-Type' => 'text/csv']);
    }

    public function movementTypeLabel(): string
    {
        return match ($this->movementType) {
            'purchase' => 'Entrada',
            'stock_out' => 'Salida',
            'stock_shortage' => 'Stock pendiente',
            'cabinet' => 'Gabinete',
            'transfer' => 'Traspaso',
            'waste' => 'Desecho',
            'adjustment' => 'Ajuste',
            'opening_adjustment' => 'Ajuste de apertura',
            default => ucfirst(str_replace('_', ' ', $this->movementType)),
        };
    }

    public function movementLabel(string $type): string
    {
        return match ($type) {
            'purchase' => 'Entrada',
            'sale' => 'Venta',
            'stock_out' => 'Salida',
            'stock_shortage' => 'Stock pendiente',
            'cabinet' => 'Gabinete',
            'transfer_in' => 'Traspaso recibido',
            'transfer_out' => 'Traspaso enviado',
            'waste' => 'Desecho',
            'adjustment' => 'Ajuste',
            'opening_adjustment' => 'Ajuste de apertura',
            default => ucfirst(str_replace('_', ' ', $type)),
        };
    }

    public function render()
    {
        $company = $this->company();
        $branch = $this->activeBranch();
        $search = trim($this->search);
        $brandFilter = $this->brandFilter !== '' ? (int) $this->brandFilter : null;
        $useAreaFilter = $this->useAreaFilter !== '' ? (int) $this->useAreaFilter : null;
        $selectedCount = $this->selectedCountId
            ? $company->inventoryCounts()->with(['useArea', 'items.product.brand', 'items.product.useArea'])->where('branch_id', $branch->id)->whereKey($this->selectedCountId)->first()
            : null;
        $selectedCountItems = $selectedCount
            ? $this->filteredCountItems($selectedCount)
            : collect();
        $selectedProduct = $this->selectedProductId
            ? $company->inventoryProducts()->with(['brand', 'useArea'])->whereKey($this->selectedProductId)->first()
            : null;

        return view('livewire.inventory.inventory-manager', [
            'branch' => $branch,
            'suppliers' => $company->inventorySuppliers()->orderBy('name')->get(),
            'brands' => $company->inventoryBrands()->with('supplier')->orderBy('name')->get(),
            'useAreas' => $company->inventoryUseAreas()->orderBy('name')->get(),
            'products' => $company->inventoryProducts()
                ->with(['supplier', 'brand', 'useArea'])
                ->withSum(['batches as current_stock' => fn ($query) => $query->where('branch_id', $branch->id)], 'current_quantity')
                ->when($search !== '', fn ($query) => $query->where(fn ($nested) => $nested->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%")))
                ->when($brandFilter, fn ($query) => $query->where('inventory_brand_id', $brandFilter))
                ->when($useAreaFilter, fn ($query) => $query->where('inventory_use_area_id', $useAreaFilter))
                ->latest()
                ->paginate(15),
            'batches' => $company->inventoryBatches()
                ->with('product')
                ->where('branch_id', $branch->id)
                ->where('status', 'available')
                ->orderByRaw('expires_at IS NULL, expires_at ASC')
                ->get(),
            'movements' => $company->inventoryMovements()
                ->with(['product', 'batch', 'branch', 'relatedBranch'])
                ->where('branch_id', $branch->id)
                ->when($this->activeTab === 'waste', fn ($query) => $query->where('type', 'waste'))
                ->latest('moved_at')
                ->paginate(15),
            'assets' => $company->inventoryAssets()
                ->where('branch_id', $branch->id)
                ->when($search !== '', fn ($query) => $query->where(fn ($nested) => $nested->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%")))
                ->latest()
                ->paginate(15),
            'counts' => $company->inventoryCounts()->with('useArea')->withCount('items')->where('branch_id', $branch->id)->latest()->paginate(15),
            'currentCount' => $this->currentInventoryCount($branch),
            'summary' => $this->summary($company, $branch),
            'branches' => $this->availableBranches(),
            'selectedCount' => $selectedCount,
            'selectedCountItems' => $selectedCountItems,
            'selectedProduct' => $selectedProduct,
            'selectedProductMovements' => $selectedProduct ? $this->productMovementData($company, $branch, $selectedProduct) : collect(),
            'canManageInventoryClosures' => $this->canManageInventoryClosures(),
        ]);
    }

    private function recordMovement(Company $company, Branch $branch, InventoryCount $count, int $productId, ?int $batchId, string $type, float $quantity, float $unitCost, array $data): void
    {
        InventoryMovement::query()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'related_branch_id' => $data['relatedBranchId'] ?? null,
            'inventory_count_id' => $count->id,
            'inventory_product_id' => $productId,
            'inventory_product_batch_id' => $batchId,
            'type' => $type,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'total_cost' => $quantity * $unitCost,
            'moved_at' => now(),
            'reference' => $data['movementReference'] ?: null,
            'reason' => $data['movementReason'] ?: null,
        ]);
    }

    private function applyOpeningStockDelta(InventoryCount $count, InventoryCountItem $item, float $delta, float $oldOpening, float $newOpening): void
    {
        $product = $item->product;
        $unitCost = $this->productUnitCost($count->branch, $item->inventory_product_id, (float) $product?->purchase_cost);
        $batch = InventoryProductBatch::query()
            ->where('branch_id', $count->branch_id)
            ->where('inventory_product_id', $item->inventory_product_id)
            ->where('status', 'available')
            ->orderByDesc('current_quantity')
            ->first();

        if (! $batch) {
            $batch = InventoryProductBatch::query()->create([
                'company_id' => $count->company_id,
                'branch_id' => $count->branch_id,
                'inventory_product_id' => $item->inventory_product_id,
                'lot_code' => 'APERTURA-'.$count->id.'-'.$item->inventory_product_id,
                'received_at' => now()->toDateString(),
                'initial_quantity' => 0,
                'current_quantity' => 0,
                'unit_cost' => $unitCost,
                'status' => 'available',
            ]);
        }

        $batch->increment('current_quantity', $delta);

        if ($delta > 0) {
            $batch->increment('initial_quantity', $delta);
        }

        InventoryMovement::query()->create([
            'company_id' => $count->company_id,
            'branch_id' => $count->branch_id,
            'inventory_count_id' => $count->id,
            'inventory_product_id' => $item->inventory_product_id,
            'inventory_product_batch_id' => $batch->id,
            'type' => 'opening_adjustment',
            'quantity' => abs($delta),
            'unit_cost' => $unitCost,
            'total_cost' => abs($delta) * $unitCost,
            'moved_at' => now(),
            'reference' => 'INV-'.$count->id,
            'reason' => 'Apertura manual: '.number_format($oldOpening, 2).' -> '.number_format($newOpening, 2),
        ]);
    }

    private function snapshotOpeningItems(InventoryCount $count): void
    {
        $products = $this->countProductsQuery($count)->get();

        foreach ($products as $product) {
            $openingQuantity = $this->productStock($count->branch, $product->id);
            $unitCost = $this->productUnitCost($count->branch, $product->id, (float) $product->purchase_cost);

            InventoryCountItem::query()->updateOrCreate(
                [
                    'inventory_count_id' => $count->id,
                    'inventory_product_id' => $product->id,
                ],
                [
                    'company_id' => $count->company_id,
                    'branch_id' => $count->branch_id,
                    'inventory_brand_id' => $product->inventory_brand_id,
                    'inventory_use_area_id' => $product->inventory_use_area_id,
                    'opening_quantity' => $openingQuantity,
                    'movement_in_quantity' => 0,
                    'movement_out_quantity' => 0,
                    'expected_quantity' => $openingQuantity,
                    'closed_quantity' => $openingQuantity,
                    'difference_quantity' => 0,
                    'unit_cost' => $unitCost,
                    'stock_value' => $openingQuantity * $unitCost,
                    'status' => 'existing',
                    'notes' => null,
                ],
            );
        }
    }

    private function refreshCountItems(InventoryCount $count, bool $closing = false): void
    {
        $this->countProductsQuery($count)->get()->each(function (InventoryProduct $product) use ($count, $closing) {
            $item = $count->items()->where('inventory_product_id', $product->id)->first();
            $openingQuantity = $item ? (float) $item->opening_quantity : 0.0;
            $movementIn = $this->productMovementQuantity($count, $product->id, ['purchase', 'transfer_in']);
            $movementOut = $this->productMovementQuantity($count, $product->id, ['sale', 'stock_out', 'cabinet', 'waste', 'transfer_out', 'adjustment']);
            $expected = round($openingQuantity + $movementIn - $movementOut, 2);
            $closed = $this->productStock($count->branch, $product->id);
            $unitCost = $this->productUnitCost($count->branch, $product->id, (float) $product->purchase_cost);

            InventoryCountItem::query()->updateOrCreate(
                [
                    'inventory_count_id' => $count->id,
                    'inventory_product_id' => $product->id,
                ],
                [
                    'company_id' => $count->company_id,
                    'branch_id' => $count->branch_id,
                    'inventory_brand_id' => $product->inventory_brand_id,
                    'inventory_use_area_id' => $product->inventory_use_area_id,
                    'opening_quantity' => $openingQuantity,
                    'movement_in_quantity' => $movementIn,
                    'movement_out_quantity' => $movementOut,
                    'expected_quantity' => $expected,
                    'closed_quantity' => $closed,
                    'difference_quantity' => round($closed - $expected, 2),
                    'unit_cost' => $unitCost,
                    'stock_value' => $closed * $unitCost,
                    'status' => $item ? $item->status : 'new',
                    'notes' => $item ? $item->notes : 'Producto nuevo',
                ],
            );
        });
    }

    private function countProductsQuery(InventoryCount $count)
    {
        return $count->company->inventoryProducts()
            ->with(['brand', 'useArea'])
            ->when($count->inventory_use_area_id, fn ($query) => $query->where('inventory_use_area_id', $count->inventory_use_area_id))
            ->orderBy('name');
    }

    private function productMovementQuantity(InventoryCount $count, int $productId, array $types): float
    {
        return round((float) $count->company->inventoryMovements()
            ->where('branch_id', $count->branch_id)
            ->where('inventory_count_id', $count->id)
            ->where('inventory_product_id', $productId)
            ->whereIn('type', $types)
            ->sum('quantity'), 2);
    }

    private function productStock(Branch $branch, int $productId): float
    {
        return round((float) InventoryProductBatch::query()
            ->where('branch_id', $branch->id)
            ->where('inventory_product_id', $productId)
            ->sum('current_quantity'), 2);
    }

    private function createCatalogBatchesForProduct(Company $company, InventoryProduct $product): void
    {
        foreach ($this->availableBranches() as $branch) {
            InventoryProductBatch::query()->firstOrCreate(
                [
                    'branch_id' => $branch->id,
                    'inventory_product_id' => $product->id,
                    'lot_code' => 'CATALOGO-'.$branch->id.'-'.$product->id,
                ],
                [
                    'company_id' => $company->id,
                    'received_at' => now()->toDateString(),
                    'initial_quantity' => 0,
                    'current_quantity' => 0,
                    'unit_cost' => (float) $product->purchase_cost,
                    'status' => 'available',
                ],
            );
        }
    }

    private function productUnitCost(Branch $branch, int $productId, float $fallback = 0): float
    {
        return round((float) (InventoryProductBatch::query()
            ->where('branch_id', $branch->id)
            ->where('inventory_product_id', $productId)
            ->where('unit_cost', '>', 0)
            ->latest('received_at')
            ->value('unit_cost') ?? $fallback), 2);
    }

    private function stockForArea(Company $company, Branch $branch, ?int $useAreaId): float
    {
        return round((float) $company->inventoryBatches()
            ->where('branch_id', $branch->id)
            ->whereHas('product', fn ($query) => $query->when($useAreaId, fn ($areaQuery) => $areaQuery->where('inventory_use_area_id', $useAreaId)))
            ->sum('current_quantity'), 2);
    }

    private function stockValueForArea(Company $company, Branch $branch, ?int $useAreaId): float
    {
        return round((float) $company->inventoryBatches()
            ->where('branch_id', $branch->id)
            ->whereHas('product', fn ($query) => $query->when($useAreaId, fn ($areaQuery) => $areaQuery->where('inventory_use_area_id', $useAreaId)))
            ->selectRaw('COALESCE(SUM(current_quantity * unit_cost), 0) as total')
            ->value('total'), 2);
    }

    private function productMovementData(Company $company, Branch $branch, InventoryProduct $product)
    {
        $types = match ($this->productMovementTab) {
            'entries' => ['purchase', 'transfer_in'],
            'waste' => ['waste', 'stock_out', 'cabinet', 'stock_shortage'],
            'transfers' => ['transfer_in', 'transfer_out'],
            'adjustments' => ['adjustment', 'opening_adjustment'],
            default => ['sale'],
        };

        $movements = $company->inventoryMovements()
            ->with(['batch', 'relatedBranch'])
            ->where('branch_id', $branch->id)
            ->where('inventory_product_id', $product->id)
            ->whereIn('type', $types)
            ->latest('moved_at')
            ->limit(80)
            ->get();

        $saleItems = collect();

        if ($this->productMovementTab === 'sales') {
            $saleItems = TreatmentPaymentItem::query()
                ->with(['payment.client', 'payment.receivedBy', 'soldBy', 'batch'])
                ->where('inventory_product_id', $product->id)
                ->whereHas('payment', fn ($query) => $query->where('company_id', $company->id)->where('branch_id', $branch->id))
                ->latest()
                ->limit(80)
                ->get();
        }

        return collect([
            'movements' => $movements,
            'sales' => $saleItems,
        ]);
    }

    private function filteredCountItems(InventoryCount $count)
    {
        $search = Str::lower(Str::ascii(trim($this->countDetailSearch)));

        return $count->items
            ->filter(function (InventoryCountItem $item) use ($search) {
                if ($this->countOnlyDifferences && abs((float) $item->difference_quantity) < 0.009) {
                    return false;
                }

                if ($search === '') {
                    return true;
                }

                $text = collect([
                    $item->product?->name,
                    $item->product?->code,
                    $item->brand?->name,
                    $item->product?->brand?->name,
                    $item->useArea?->name,
                    $item->product?->useArea?->name,
                    $item->status === 'new' ? 'producto nuevo' : '',
                ])->filter()->implode(' ');

                return str_contains(Str::lower(Str::ascii($text)), $search);
            })
            ->sortBy(fn ($item) => $item->product?->name ?? '')
            ->values();
    }

    private function canManageInventoryClosures(): bool
    {
        $user = Auth::user();
        $company = $this->company();
        $companyRole = $user->companies()
            ->where('companies.id', $company->id)
            ->value('company_user.role');

        if (in_array($companyRole, ['owner', 'admin', 'super_admin', 'super-administrador'], true)) {
            return true;
        }

        $role = $user->branches()
            ->where('branches.id', $this->activeBranch()->id)
            ->first()
            ?->pivot
            ?->role_id;

        if (! $role) {
            return false;
        }

        $branchRole = $company->roles()->whereKey($role)->first();
        $slug = Str::lower($branchRole?->slug ?? '');
        $name = Str::lower(Str::ascii($branchRole?->name ?? ''));

        return in_array($slug, ['administrador', 'admin', 'super-admin', 'super-administrador'], true)
            || str_contains($name, 'administrador');
    }

    private function countDetailsPdf(InventoryCount $count, $items): string
    {
        $lines = [
            'Inventario: '.$count->name,
            'Sucursal: '.$count->branch?->name.' | Zona: '.($count->useArea?->name ?? 'General'),
            'Estado: '.($count->status === 'in_process' ? 'En proceso' : 'Cerrado'),
            '',
            'Producto | Marca | Apertura | Entradas | Salidas | Esperado | Cierre | Diferencia',
        ];

        foreach ($items->take(90) as $item) {
            $lines[] = Str::limit($item->product?->name ?? 'Producto', 28, '')
                .' | '.Str::limit($item->brand?->name ?? $item->product?->brand?->name ?? 'GENERAL', 12, '')
                .' | '.number_format((float) $item->opening_quantity, 2)
                .' | '.number_format((float) $item->movement_in_quantity, 2)
                .' | '.number_format((float) $item->movement_out_quantity, 2)
                .' | '.number_format((float) $item->expected_quantity, 2)
                .' | '.number_format((float) $item->closed_quantity, 2)
                .' | '.number_format((float) $item->difference_quantity, 2);
        }

        if ($items->count() > 90) {
            $lines[] = '';
            $lines[] = 'Se muestran 90 productos. Usa Excel para el detalle completo.';
        }

        return $this->simplePdf($lines);
    }

    private function simplePdf(array $lines): string
    {
        $objects = [];
        $content = "BT\n/F1 10 Tf\n50 790 Td\n14 TL\n";

        foreach ($lines as $line) {
            $content .= '('.$this->pdfText($line).") Tj\nT*\n";
        }

        $content .= "ET\n";
        $objects[] = "<< /Type /Catalog /Pages 2 0 R >>";
        $objects[] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
        $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>";
        $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
        $objects[] = "<< /Length ".strlen($content)." >>\nstream\n{$content}endstream";
        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n{$object}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";

        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= str_pad((string) $offset, 10, '0', STR_PAD_LEFT)." 00000 n \n";
        }

        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";

        return $pdf;
    }

    private function pdfText(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\(', '\)'], Str::ascii($text));
    }

    private function currentInventoryCount(Branch $branch): InventoryCount
    {
        return InventoryCount::query()->firstOrCreate(
            [
                'company_id' => $this->company()->id,
                'branch_id' => $branch->id,
                'inventory_use_area_id' => null,
                'status' => 'in_process',
            ],
            [
                'name' => 'Inventario '.$branch->name.' '.now()->format('Y-m'),
                'opened_at' => now(),
            ],
        );
    }

    private function currentInventoryCountForProduct(Branch $branch, int $productId): InventoryCount
    {
        $company = $this->company();
        $product = $company->inventoryProducts()->whereKey($productId)->firstOrFail();
        $zoneCount = $company->inventoryCounts()
            ->where('branch_id', $branch->id)
            ->where('inventory_use_area_id', $product->inventory_use_area_id)
            ->where('status', 'in_process')
            ->first();

        return $zoneCount ?: $this->currentInventoryCount($branch);
    }

    private function company(): Company
    {
        return Auth::user()->companies()->firstOrFail();
    }

    private function activeBranch(): Branch
    {
        $company = $this->company();
        $branches = $this->availableBranches();
        $activeId = session('active_branch_id');

        return $branches->firstWhere('id', $activeId)
            ?? $branches->first()
            ?? $company->branches()->firstOrFail();
    }

    private function availableBranches()
    {
        $company = $this->company();
        $branches = Auth::user()
            ->branches()
            ->where('company_id', $company->id)
            ->orderBy('name')
            ->get();

        return $branches->isNotEmpty()
            ? $branches
            : $company->branches()->orderBy('name')->get();
    }

    private function generateProductCode(string $name): string
    {
        $base = collect(preg_split('/\s+/', Str::ascii($name), -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($word) => Str::upper(Str::substr(preg_replace('/[^A-Za-z0-9]/', '', $word), 0, 3)))
            ->filter()
            ->take(3)
            ->implode('-') ?: 'PROD';

        return $this->uniqueCode(InventoryProduct::class, $base);
    }

    private function generateAssetCode(string $name): string
    {
        $base = 'ACT-'.Str::upper(Str::substr(Str::slug(Str::ascii($name), ''), 0, 8));

        return $this->uniqueCode(InventoryAsset::class, $base ?: 'ACTIVO');
    }

    private function uniqueCode(string $modelClass, string $base): string
    {
        $company = $this->company();
        $candidate = $base;
        $sequence = 1;

        while ($modelClass::query()->where('company_id', $company->id)->where('code', $candidate)->exists()) {
            $candidate = $base.'-'.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
            $sequence++;
        }

        return $candidate;
    }

    private function generateLotCode(string $productName, int $branchId): string
    {
        $prefix = Str::upper(Str::substr(Str::slug(Str::ascii($productName), ''), 0, 6)) ?: 'LOTE';
        $base = $prefix.'-'.now()->format('Ymd');
        $sequence = InventoryProductBatch::query()
            ->where('branch_id', $branchId)
            ->where('lot_code', 'like', "{$base}%")
            ->count() + 1;

        return $base.'-'.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }

    private function productHasCurrentMonthUseOrSales(InventoryProduct $product): bool
    {
        return $product->movements()
            ->whereIn('type', ['cabinet', 'sale', 'stock_out', 'stock_shortage'])
            ->whereBetween('moved_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->exists();
    }

    private function summary(Company $company, Branch $branch): array
    {
        $stockValue = $company->inventoryBatches()
            ->where('branch_id', $branch->id)
            ->selectRaw('COALESCE(SUM(current_quantity * unit_cost), 0) as total')
            ->value('total');
        $assetValue = $company->inventoryAssets()->where('branch_id', $branch->id)->where('status', '!=', 'wasted')->sum('purchase_amount');
        $lossValue = $company->inventoryMovements()->where('branch_id', $branch->id)->where('type', 'waste')->sum('total_cost')
            + $company->inventoryAssets()->where('branch_id', $branch->id)->where('status', 'wasted')->sum('purchase_amount');
        $repairValue = $company->inventoryAssets()->where('branch_id', $branch->id)->sum('repair_total');

        return [
            'stock_value' => round((float) $stockValue, 2),
            'asset_value' => round((float) $assetValue, 2),
            'loss_value' => round((float) $lossValue, 2),
            'repair_value' => round((float) $repairValue, 2),
        ];
    }

    private function resetProductForm(): void
    {
        $this->reset(['editingProductId', 'productName', 'productDescription', 'productSupplierId', 'productBrandId', 'productUseAreaId', 'packageName']);
        $this->unitName = 'unidad';
        $this->unitsPerPackage = '1';
        $this->purchaseCost = '0';
        $this->minimumStock = '0';
        $this->resetErrorBag();
    }

    private function resetMovementForm(): void
    {
        $this->reset(['movementProductId', 'movementBatchId', 'relatedBranchId', 'movementQuantity', 'movementUnitCost', 'movementLotCode', 'movementExpiresAt', 'movementReceivedAt', 'movementReference', 'movementReason']);
        $this->resetErrorBag();
    }

    private function resetAssetForm(): void
    {
        $this->reset(['editingAssetId', 'assetName', 'assetCategory', 'assetPurchaseAmount', 'assetPurchaseDate']);
        $this->assetStatus = 'active';
        $this->resetErrorBag();
    }
}
