<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\InventoryBrand;
use App\Models\InventoryCount;
use App\Models\InventoryMovement;
use App\Models\InventoryProduct;
use App\Models\InventoryProductBatch;
use App\Models\InventorySupplier;
use App\Models\InventoryUseArea;
use App\Models\Service;
use App\Models\ServicePackage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RumikaDemoCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->where('slug', 'rumika-demo')->first();

        if (! $company) {
            return;
        }

        $branch = $company->branches()->where('slug', 'sucursal-centro')->first()
            ?? $company->branches()->first();

        if (! $branch) {
            return;
        }

        $supplier = InventorySupplier::query()->updateOrCreate(
            ['company_id' => $company->id, 'name' => 'Dermocosmetica Andina'],
            [
                'contact_name' => 'Ejecutivo comercial',
                'phone' => '70000001',
                'email' => 'ventas@dermoandina.test',
                'status' => 'active',
            ],
        );

        $brand = InventoryBrand::query()->updateOrCreate(
            ['company_id' => $company->id, 'name' => 'SpaCare Pro'],
            [
                'inventory_supplier_id' => $supplier->id,
                'status' => 'active',
            ],
        );

        $useAreas = collect([
            'Venta' => 'Productos disponibles para venta al cliente.',
            'Gabinete' => 'Productos para uso interno durante tratamientos.',
            'Insumo clinico' => 'Material consumible para procedimientos.',
            'Limpieza' => 'Productos de higiene, limpieza o desinfeccion.',
        ])->mapWithKeys(fn (string $description, string $name) => [
            $name => InventoryUseArea::query()->updateOrCreate(
                ['company_id' => $company->id, 'slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'description' => $description,
                    'status' => 'active',
                ],
            ),
        ]);

        $products = [
            ['name' => 'Aceite esencial lavanda', 'unit' => 'ml', 'package' => 'Frasco', 'units' => 250, 'cost' => 0.45, 'stock' => 500, 'min' => 80, 'months' => 18, 'area' => 'Gabinete'],
            ['name' => 'Mascarilla facial hidratante', 'unit' => 'unidad', 'package' => 'Caja', 'units' => 12, 'cost' => 18.00, 'stock' => 36, 'min' => 8, 'months' => 12, 'area' => 'Venta'],
            ['name' => 'Crema exfoliante corporal', 'unit' => 'ml', 'package' => 'Pote', 'units' => 500, 'cost' => 0.32, 'stock' => 1000, 'min' => 150, 'months' => 14, 'area' => 'Gabinete'],
            ['name' => 'Gel conductor ultrasonido', 'unit' => 'ml', 'package' => 'Botella', 'units' => 1000, 'cost' => 0.18, 'stock' => 3000, 'min' => 500, 'months' => 24, 'area' => 'Gabinete'],
            ['name' => 'Aguja mesoterapia 30G', 'unit' => 'unidad', 'package' => 'Caja', 'units' => 100, 'cost' => 1.20, 'stock' => 300, 'min' => 50, 'months' => 36, 'area' => 'Insumo clinico'],
            ['name' => 'Guantes nitrilo talla M', 'unit' => 'unidad', 'package' => 'Caja', 'units' => 100, 'cost' => 0.55, 'stock' => 500, 'min' => 120, 'months' => 30, 'area' => 'Insumo clinico'],
            ['name' => 'Gasa esteril 10x10', 'unit' => 'unidad', 'package' => 'Paquete', 'units' => 50, 'cost' => 0.35, 'stock' => 250, 'min' => 80, 'months' => 30, 'area' => 'Insumo clinico'],
            ['name' => 'Suero fisiologico 500ml', 'unit' => 'unidad', 'package' => 'Caja', 'units' => 24, 'cost' => 7.50, 'stock' => 48, 'min' => 12, 'months' => 18, 'area' => 'Insumo clinico'],
            ['name' => 'Cera depilatoria elastica', 'unit' => 'g', 'package' => 'Bolsa', 'units' => 1000, 'cost' => 0.09, 'stock' => 3000, 'min' => 600, 'months' => 18, 'area' => 'Gabinete'],
            ['name' => 'Toalla facial desechable', 'unit' => 'unidad', 'package' => 'Paquete', 'units' => 100, 'cost' => 0.40, 'stock' => 400, 'min' => 100, 'months' => 48, 'area' => 'Limpieza'],
        ];

        $count = InventoryCount::query()->firstOrCreate(
            ['company_id' => $company->id, 'branch_id' => $branch->id, 'status' => 'in_process'],
            ['name' => 'Inventario '.$branch->name.' '.now()->format('Y-m'), 'opened_at' => now()],
        );

        foreach ($products as $index => $item) {
            $product = InventoryProduct::query()->updateOrCreate(
                ['company_id' => $company->id, 'code' => $this->codeFor($company, $item['name'])],
                [
                    'inventory_supplier_id' => $supplier->id,
                    'inventory_brand_id' => $brand->id,
                    'inventory_use_area_id' => $useAreas[$item['area']]->id,
                    'name' => $item['name'],
                    'description' => 'Producto demo para spa y centros esteticos.',
                    'unit_name' => $item['unit'],
                    'package_name' => $item['package'],
                    'units_per_package' => $item['units'],
                    'purchase_cost' => $item['cost'],
                    'minimum_stock' => $item['min'],
                    'status' => 'active',
                ],
            );

            $lot = 'SPA-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT).'-'.now()->format('Ym');
            $batch = InventoryProductBatch::query()->updateOrCreate(
                [
                    'branch_id' => $branch->id,
                    'inventory_product_id' => $product->id,
                    'lot_code' => $lot,
                ],
                [
                    'company_id' => $company->id,
                    'expires_at' => now()->addMonths($item['months'])->toDateString(),
                    'received_at' => now()->subDays(7)->toDateString(),
                    'initial_quantity' => $item['stock'],
                    'current_quantity' => $item['stock'],
                    'unit_cost' => $item['cost'],
                    'status' => 'available',
                ],
            );

            InventoryMovement::query()->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'branch_id' => $branch->id,
                    'inventory_product_id' => $product->id,
                    'inventory_product_batch_id' => $batch->id,
                    'type' => 'purchase',
                    'reference' => 'DEMO-SPA-'.$lot,
                ],
                [
                    'inventory_count_id' => $count->id,
                    'quantity' => $item['stock'],
                    'unit_cost' => $item['cost'],
                    'total_cost' => $item['stock'] * $item['cost'],
                    'moved_at' => now()->subDays(7),
                    'reason' => 'Carga inicial demo',
                ],
            );
        }

        $services = [
            ['name' => 'Limpieza facial profunda', 'price' => 160, 'duration' => 60],
            ['name' => 'Masaje relajante aromaterapia', 'price' => 220, 'duration' => 75],
            ['name' => 'Depilacion piernas completas', 'price' => 180, 'duration' => 70],
            ['name' => 'Tratamiento hidratante corporal', 'price' => 260, 'duration' => 90],
            ['name' => 'Radiofrecuencia facial', 'price' => 190, 'duration' => 45],
        ];

        $createdServices = collect($services)->map(function (array $item) use ($company, $branch) {
            return Service::query()->updateOrCreate(
                ['company_id' => $company->id, 'name' => $item['name']],
                [
                    'branch_id' => $branch->id,
                    'description' => 'Servicio demo para spa.',
                    'price' => $item['price'],
                    'duration_minutes' => $item['duration'],
                    'status' => 'available',
                ],
            );
        });

        $relax = ServicePackage::query()->updateOrCreate(
            ['company_id' => $company->id, 'name' => 'Paquete relax mensual'],
            [
                'branch_id' => $branch->id,
                'description' => 'Limpieza facial y masaje relajante.',
                'price' => 340,
                'starts_at' => now()->toDateString(),
                'expires_at' => now()->addMonth()->toDateString(),
                'status' => 'available',
            ],
        );
        $relax->services()->sync($createdServices->take(2)->mapWithKeys(fn (Service $service) => [$service->id => ['quantity' => 1]])->all());

        $beauty = ServicePackage::query()->updateOrCreate(
            ['company_id' => $company->id, 'name' => 'Paquete beauty completo'],
            [
                'branch_id' => $branch->id,
                'description' => 'Hidratacion corporal, radiofrecuencia y depilacion.',
                'price' => 560,
                'starts_at' => now()->toDateString(),
                'expires_at' => now()->addWeeks(6)->toDateString(),
                'status' => 'available',
            ],
        );
        $beauty->services()->sync($createdServices->slice(2)->mapWithKeys(fn (Service $service) => [$service->id => ['quantity' => 1]])->all());
    }

    private function codeFor(Company $company, string $name): string
    {
        $base = collect(preg_split('/\s+/', Str::ascii($name), -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($word) => Str::upper(Str::substr(preg_replace('/[^A-Za-z0-9]/', '', $word), 0, 3)))
            ->filter()
            ->take(3)
            ->implode('-') ?: 'PROD';

        $candidate = $base;
        $sequence = 1;

        while (InventoryProduct::query()
            ->where('company_id', $company->id)
            ->where('code', $candidate)
            ->where('name', '!=', $name)
            ->exists()) {
            $candidate = $base.'-'.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
            $sequence++;
        }

        return $candidate;
    }
}
