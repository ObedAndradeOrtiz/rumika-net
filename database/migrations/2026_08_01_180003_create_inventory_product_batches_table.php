<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_product_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_product_id')->constrained()->cascadeOnDelete();
            $table->string('lot_code');
            $table->date('expires_at')->nullable();
            $table->date('received_at')->nullable();
            $table->decimal('initial_quantity', 12, 2)->default(0);
            $table->decimal('current_quantity', 12, 2)->default(0);
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->string('status')->default('available');
            $table->timestamps();

            $table->unique(['branch_id', 'inventory_product_id', 'lot_code'], 'inventory_batch_lot_unique');
            $table->index(['company_id', 'branch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_product_batches');
    }
};
