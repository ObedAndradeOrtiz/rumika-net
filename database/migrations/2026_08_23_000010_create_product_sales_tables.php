<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buyers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('full_name')->nullable();
            $table->string('nit')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->unique(['company_id', 'nit'], 'buyers_company_nit_unique');
            $table->unique(['company_id', 'phone'], 'buyers_company_phone_unique');
        });

        Schema::create('product_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('buyer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sold_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('received_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('buyer_name')->nullable();
            $table->string('buyer_nit')->nullable();
            $table->string('buyer_phone')->nullable();
            $table->string('buyer_email')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('cash_amount', 12, 2)->default(0);
            $table->decimal('qr_amount', 12, 2)->default(0);
            $table->string('method')->default('cash');
            $table->boolean('invoice_requested')->default(false);
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('sold_at');
            $table->timestamps();

            $table->index(['company_id', 'branch_id', 'sold_at']);
            $table->index(['buyer_id', 'sold_at']);
        });

        Schema::create('product_sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_product_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('lot_code')->nullable();
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('stock_quantity', 12, 2)->default(0);
            $table->decimal('pending_quantity', 12, 2)->default(0);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('missing_reason')->nullable();
            $table->timestamps();

            $table->index(['inventory_product_id', 'inventory_product_batch_id'], 'product_sale_item_product_batch_idx');
        });

        DB::table('roles')
            ->whereIn('slug', ['administrador', 'gerente'])
            ->orderBy('id')
            ->each(function (object $role) {
                $permissions = json_decode((string) $role->permissions, true);

                if (! is_array($permissions)) {
                    $permissions = [];
                }

                $permissions['ventas_productos'] = $role->slug === 'administrador'
                    ? ['view', 'create', 'edit', 'delete']
                    : ['view', 'create', 'edit'];

                DB::table('roles')
                    ->where('id', $role->id)
                    ->update(['permissions' => json_encode($permissions)]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_sale_items');
        Schema::dropIfExists('product_sales');
        Schema::dropIfExists('buyers');

        DB::table('roles')->orderBy('id')->each(function (object $role) {
            $permissions = json_decode((string) $role->permissions, true);

            if (! is_array($permissions)) {
                return;
            }

            unset($permissions['ventas_productos']);

            DB::table('roles')
                ->where('id', $role->id)
                ->update(['permissions' => json_encode($permissions)]);
        });
    }
};
