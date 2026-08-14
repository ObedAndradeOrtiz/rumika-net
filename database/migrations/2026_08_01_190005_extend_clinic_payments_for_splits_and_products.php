<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('attended_by_user_id')->nullable()->after('client_id')->constrained('users')->nullOnDelete();
        });

        Schema::table('appointment_services', function (Blueprint $table) {
            $table->foreignId('performed_by_user_id')->nullable()->after('service_id')->constrained('users')->nullOnDelete();
        });

        Schema::table('treatment_payments', function (Blueprint $table) {
            $table->foreignId('received_by_user_id')->nullable()->after('treatment_plan_id')->constrained('users')->nullOnDelete();
            $table->foreignId('performed_by_user_id')->nullable()->after('received_by_user_id')->constrained('users')->nullOnDelete();
        });

        Schema::create('treatment_payment_splits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treatment_payment_id')->constrained()->cascadeOnDelete();
            $table->string('method');
            $table->decimal('amount', 12, 2);
            $table->string('reference')->nullable();
            $table->timestamps();
        });

        Schema::create('treatment_payment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treatment_payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('inventory_product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('inventory_product_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->string('name');
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatment_payment_items');
        Schema::dropIfExists('treatment_payment_splits');

        Schema::table('treatment_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('performed_by_user_id');
            $table->dropConstrainedForeignId('received_by_user_id');
        });

        Schema::table('appointment_services', function (Blueprint $table) {
            $table->dropConstrainedForeignId('performed_by_user_id');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('attended_by_user_id');
        });
    }
};
