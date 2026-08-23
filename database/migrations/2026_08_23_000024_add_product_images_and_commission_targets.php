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
        Schema::table('inventory_products', function (Blueprint $table) {
            if (! Schema::hasColumn('inventory_products', 'image_path')) {
                $table->string('image_path')->nullable()->after('description');
            }
        });

        Schema::create('commission_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('period_type')->default('monthly');
            $table->decimal('minimum_sales_amount', 12, 2)->default(0);
            $table->decimal('minimum_commission_amount', 12, 2)->default(0);
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['company_id', 'branch_id', 'user_id', 'period_type'], 'commission_targets_scope_idx');
        });

        foreach (CompanyPlanCatalog::plans() as $plan) {
            DB::table('company_plans')
                ->where('slug', $plan['slug'])
                ->update(['features' => json_encode($plan['features'])]);
        }

        DB::table('roles')
            ->where('slug', 'administrador')
            ->orderBy('id')
            ->each(function (object $role) {
                $permissions = json_decode((string) $role->permissions, true);

                if (! is_array($permissions)) {
                    $permissions = [];
                }

                $permissions['comisiones'] = ['view', 'create', 'edit', 'delete'];

                DB::table('roles')
                    ->where('id', $role->id)
                    ->update(['permissions' => json_encode($permissions)]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_targets');

        Schema::table('inventory_products', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_products', 'image_path')) {
                $table->dropColumn('image_path');
            }
        });

        DB::table('roles')->orderBy('id')->each(function (object $role) {
            $permissions = json_decode((string) $role->permissions, true);

            if (! is_array($permissions)) {
                return;
            }

            unset($permissions['comisiones']);

            DB::table('roles')
                ->where('id', $role->id)
                ->update(['permissions' => json_encode($permissions)]);
        });
    }
};
