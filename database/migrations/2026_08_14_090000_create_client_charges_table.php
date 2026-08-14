<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('appointment_service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('inventory_product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('inventory_product_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->string('name');
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('balance_amount', 12, 2)->default(0);
            $table->string('status')->default('pending');
            $table->dateTime('charged_at');
            $table->timestamps();

            $table->index(['company_id', 'branch_id', 'client_id', 'status']);
            $table->index(['appointment_service_id', 'status']);
            $table->index(['inventory_product_batch_id', 'status']);
        });

        Schema::create('client_charge_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_charge_id')->constrained()->cascadeOnDelete();
            $table->foreignId('treatment_payment_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->timestamps();

            $table->index(['client_charge_id', 'treatment_payment_id'], 'charge_payment_lookup_idx');
        });

        Schema::table('treatment_payment_items', function (Blueprint $table) {
            $table->foreignId('client_charge_id')->nullable()->after('treatment_payment_id')->constrained()->nullOnDelete();
            $table->decimal('charged_total', 12, 2)->default(0)->after('unit_price');
        });
    }

    public function down(): void
    {
        Schema::table('treatment_payment_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('client_charge_id');
            $table->dropColumn('charged_total');
        });

        Schema::dropIfExists('client_charge_payments');
        Schema::dropIfExists('client_charges');
    }
};
