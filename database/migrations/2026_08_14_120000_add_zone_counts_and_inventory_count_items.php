<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_counts', function (Blueprint $table) {
            $table->foreignId('inventory_use_area_id')
                ->nullable()
                ->after('branch_id')
                ->constrained()
                ->nullOnDelete();

            $table->index(['company_id', 'branch_id', 'inventory_use_area_id', 'status'], 'inventory_counts_zone_status_idx');
        });

        Schema::create('inventory_count_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_count_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_brand_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('inventory_use_area_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('opening_quantity', 12, 2)->default(0);
            $table->decimal('movement_in_quantity', 12, 2)->default(0);
            $table->decimal('movement_out_quantity', 12, 2)->default(0);
            $table->decimal('expected_quantity', 12, 2)->default(0);
            $table->decimal('closed_quantity', 12, 2)->default(0);
            $table->decimal('difference_quantity', 12, 2)->default(0);
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->decimal('stock_value', 12, 2)->default(0);
            $table->string('status')->default('existing');
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['inventory_count_id', 'inventory_product_id'], 'inventory_count_product_unique');
            $table->index(['company_id', 'branch_id', 'inventory_use_area_id'], 'inventory_count_items_zone_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_count_items');

        Schema::table('inventory_counts', function (Blueprint $table) {
            $table->dropIndex('inventory_counts_zone_status_idx');
            $table->dropConstrainedForeignId('inventory_use_area_id');
        });
    }
};
