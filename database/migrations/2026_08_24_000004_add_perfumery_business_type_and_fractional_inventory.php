<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_products', function (Blueprint $table) {
            if (! Schema::hasColumn('inventory_products', 'sale_unit_type')) {
                $table->string('sale_unit_type', 20)->default('unit')->after('unit_name');
            }

            if (! Schema::hasColumn('inventory_products', 'content_quantity')) {
                $table->decimal('content_quantity', 12, 2)->default(1)->after('sale_unit_type');
            }

            if (! Schema::hasColumn('inventory_products', 'content_unit_name')) {
                $table->string('content_unit_name', 30)->nullable()->after('content_quantity');
            }
        });

        Schema::table('product_sale_items', function (Blueprint $table) {
            if (! Schema::hasColumn('product_sale_items', 'sale_mode')) {
                $table->string('sale_mode', 20)->default('unit')->after('lot_code');
            }

            if (! Schema::hasColumn('product_sale_items', 'display_quantity')) {
                $table->decimal('display_quantity', 12, 2)->nullable()->after('quantity');
            }

            if (! Schema::hasColumn('product_sale_items', 'display_unit_name')) {
                $table->string('display_unit_name', 30)->nullable()->after('display_quantity');
            }

            if (! Schema::hasColumn('product_sale_items', 'stock_unit_name')) {
                $table->string('stock_unit_name', 30)->nullable()->after('display_unit_name');
            }

            if (! Schema::hasColumn('product_sale_items', 'stock_deduct_quantity')) {
                $table->decimal('stock_deduct_quantity', 12, 2)->nullable()->after('stock_unit_name');
            }
        });

        $modules = [
            'inicio',
            'clientes',
            'ventas_productos',
            'inventario',
            'inventario_operaciones',
            'caja',
            'crm',
            'facturacion',
            'deudas',
            'reportes',
            'gastos',
            'resumen_financiero',
            'estadisticas',
            'sucursales',
            'usuarios',
            'roles',
            'registros',
            'bitacora',
        ];

        DB::table('business_types')->updateOrInsert(
            ['slug' => 'perfumeria'],
            [
                'name' => 'Perfumeria',
                'description' => 'Venta directa de perfumes por frasco o por ml, compradores por NIT e inventario por sucursal.',
                'enabled_modules' => json_encode($modules),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        foreach (['farmacia', 'tienda'] as $slug) {
            DB::table('business_types')
                ->where('slug', $slug)
                ->update([
                    'enabled_modules' => json_encode($modules),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('product_sale_items', function (Blueprint $table) {
            foreach (['stock_deduct_quantity', 'stock_unit_name', 'display_unit_name', 'display_quantity', 'sale_mode'] as $column) {
                if (Schema::hasColumn('product_sale_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('inventory_products', function (Blueprint $table) {
            foreach (['content_unit_name', 'content_quantity', 'sale_unit_type'] as $column) {
                if (Schema::hasColumn('inventory_products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        DB::table('business_types')->where('slug', 'perfumeria')->delete();
    }
};
