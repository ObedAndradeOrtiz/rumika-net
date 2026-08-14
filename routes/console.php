<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\InventoryProduct;
use App\Models\InventoryProductBatch;
use App\Models\InventoryUseArea;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('rumika:import-bethel-inventory {file=imports/bethel_inventory.json} {--company=rumika-demo}', function () {
    $company = \App\Models\Company::query()
        ->where('slug', $this->option('company'))
        ->firstOrFail();
    $branches = $company->branches()->orderBy('name')->get();
    $path = $this->argument('file');

    if (! Storage::exists($path)) {
        $this->error("No existe el archivo storage/app/{$path}.");

        return self::FAILURE;
    }

    $rows = json_decode(Storage::get($path), true, flags: JSON_THROW_ON_ERROR);

    DB::transaction(function () use ($company, $branches, $rows) {
        $company->inventoryMovements()->delete();
        $company->inventoryBatches()->delete();
        $company->inventoryProducts()->delete();
        $company->inventoryBrands()->delete();
        $company->inventoryUseAreas()->delete();

        $brands = [];
        $areas = [];

        foreach ($rows as $row) {
            $brandName = trim((string) ($row['brand'] ?? '')) ?: 'GENERAL';
            $areaName = trim((string) ($row['area'] ?? '')) ?: 'GENERAL';
            $productName = trim((string) ($row['product'] ?? ''));

            if ($productName === '') {
                continue;
            }

            $brandKey = Str::upper($brandName);
            $areaKey = Str::upper($areaName);

            $brands[$brandKey] ??= $company->inventoryBrands()->create([
                'name' => $brandName,
                'status' => 'active',
            ])->id;

            $areas[$areaKey] ??= $company->inventoryUseAreas()->create([
                'name' => $areaName,
                'slug' => uniqueInventoryAreaSlug($company->id, $areaName),
                'status' => 'active',
            ])->id;

            $product = $company->inventoryProducts()->create([
                'inventory_brand_id' => $brands[$brandKey],
                'inventory_use_area_id' => $areas[$areaKey],
                'code' => uniqueInventoryProductCode($company->id, $productName),
                'name' => $productName,
                'unit_name' => 'unidad',
                'units_per_package' => 1,
                'purchase_cost' => 0,
                'minimum_stock' => 0,
                'status' => 'active',
            ]);

            foreach ($branches as $branch) {
                InventoryProductBatch::query()->create([
                    'company_id' => $company->id,
                    'branch_id' => $branch->id,
                    'inventory_product_id' => $product->id,
                    'lot_code' => $product->code.'-CAT',
                    'received_at' => now()->toDateString(),
                    'initial_quantity' => 0,
                    'current_quantity' => 0,
                    'unit_cost' => 0,
                    'status' => 'available',
                ]);
            }
        }
    });

    $this->info('Inventario importado para '.$company->name.': '.$company->inventoryProducts()->count().' productos, '.$company->inventoryBrands()->count().' marcas, '.$company->inventoryUseAreas()->count().' areas de uso.');

    return self::SUCCESS;
})->purpose('Importar el inventario Bethel desde storage/app/imports/bethel_inventory.json');

if (! function_exists('uniqueInventoryProductCode')) {
    function uniqueInventoryProductCode(int $companyId, string $name): string
    {
        $base = collect(preg_split('/\s+/', Str::ascii($name), -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($word) => Str::upper(Str::substr(preg_replace('/[^A-Za-z0-9]/', '', $word), 0, 3)))
            ->filter()
            ->take(3)
            ->implode('-') ?: 'PROD';
        $candidate = $base;
        $sequence = 1;

        while (InventoryProduct::query()->where('company_id', $companyId)->where('code', $candidate)->exists()) {
            $candidate = $base.'-'.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
            $sequence++;
        }

        return $candidate;
    }
}

if (! function_exists('uniqueInventoryAreaSlug')) {
    function uniqueInventoryAreaSlug(int $companyId, string $name): string
    {
        $base = Str::slug(Str::ascii($name)) ?: 'area-uso';
        $candidate = $base;
        $sequence = 1;

        while (InventoryUseArea::query()->where('company_id', $companyId)->where('slug', $candidate)->exists()) {
            $candidate = $base.'-'.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
            $sequence++;
        }

        return $candidate;
    }
}
