<?php

use App\Support\CompanyPlanCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (! Schema::hasColumn('branches', 'product_commission_percent')) {
                $table->decimal('product_commission_percent', 5, 2)->default(0)->after('printer_bridge_url');
            }
            if (! Schema::hasColumn('branches', 'product_commission_min_sale')) {
                $table->decimal('product_commission_min_sale', 12, 2)->default(0)->after('product_commission_percent');
            }
            if (! Schema::hasColumn('branches', 'service_commission_percent')) {
                $table->decimal('service_commission_percent', 5, 2)->default(0)->after('product_commission_min_sale');
            }
            if (! Schema::hasColumn('branches', 'service_commission_min_sale')) {
                $table->decimal('service_commission_min_sale', 12, 2)->default(0)->after('service_commission_percent');
            }
        });

        Schema::table('inventory_products', function (Blueprint $table) {
            if (! Schema::hasColumn('inventory_products', 'commission_enabled')) {
                $table->boolean('commission_enabled')->default(true)->after('minimum_stock');
            }
        });

        Schema::table('services', function (Blueprint $table) {
            if (! Schema::hasColumn('services', 'commission_enabled')) {
                $table->boolean('commission_enabled')->default(true)->after('duration_minutes');
            }
        });

        Schema::table('treatment_payment_items', function (Blueprint $table) {
            if (! Schema::hasColumn('treatment_payment_items', 'commission_percent')) {
                $table->decimal('commission_percent', 5, 2)->default(0)->after('total');
            }
            if (! Schema::hasColumn('treatment_payment_items', 'commission_amount')) {
                $table->decimal('commission_amount', 12, 2)->default(0)->after('commission_percent');
            }
        });

        Schema::table('product_sale_items', function (Blueprint $table) {
            if (! Schema::hasColumn('product_sale_items', 'commission_percent')) {
                $table->decimal('commission_percent', 5, 2)->default(0)->after('total');
            }
            if (! Schema::hasColumn('product_sale_items', 'commission_amount')) {
                $table->decimal('commission_amount', 12, 2)->default(0)->after('commission_percent');
            }
        });

        foreach (CompanyPlanCatalog::plans() as $plan) {
            DB::table('company_plans')
                ->where('slug', $plan['slug'])
                ->update(['features' => json_encode($plan['features'])]);
        }

        DB::table('roles')
            ->whereIn('slug', ['administrador', 'gerente'])
            ->orderBy('id')
            ->each(function (object $role) {
                $permissions = json_decode((string) $role->permissions, true);

                if (! is_array($permissions)) {
                    $permissions = [];
                }

                $permissions['reportes'] = ['view'];
                $permissions['deudas'] = $role->slug === 'administrador' ? ['view', 'edit'] : ['view'];

                DB::table('roles')
                    ->where('id', $role->id)
                    ->update(['permissions' => json_encode($permissions)]);
            });
    }

    public function down(): void
    {
        Schema::table('product_sale_items', function (Blueprint $table) {
            $columns = array_values(array_filter([
                Schema::hasColumn('product_sale_items', 'commission_amount') ? 'commission_amount' : null,
                Schema::hasColumn('product_sale_items', 'commission_percent') ? 'commission_percent' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('treatment_payment_items', function (Blueprint $table) {
            $columns = array_values(array_filter([
                Schema::hasColumn('treatment_payment_items', 'commission_amount') ? 'commission_amount' : null,
                Schema::hasColumn('treatment_payment_items', 'commission_percent') ? 'commission_percent' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('services', function (Blueprint $table) {
            if (Schema::hasColumn('services', 'commission_enabled')) {
                $table->dropColumn('commission_enabled');
            }
        });

        Schema::table('inventory_products', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_products', 'commission_enabled')) {
                $table->dropColumn('commission_enabled');
            }
        });

        Schema::table('branches', function (Blueprint $table) {
            $columns = array_values(array_filter([
                Schema::hasColumn('branches', 'service_commission_min_sale') ? 'service_commission_min_sale' : null,
                Schema::hasColumn('branches', 'service_commission_percent') ? 'service_commission_percent' : null,
                Schema::hasColumn('branches', 'product_commission_min_sale') ? 'product_commission_min_sale' : null,
                Schema::hasColumn('branches', 'product_commission_percent') ? 'product_commission_percent' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
