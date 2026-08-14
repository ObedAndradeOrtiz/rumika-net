<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_charges', function (Blueprint $table) {
            $table->foreignId('sold_by_user_id')->nullable()->after('inventory_product_batch_id')->constrained('users')->nullOnDelete();
        });

        Schema::table('treatment_payment_items', function (Blueprint $table) {
            $table->foreignId('sold_by_user_id')->nullable()->after('inventory_product_batch_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('treatment_payment_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sold_by_user_id');
        });

        Schema::table('client_charges', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sold_by_user_id');
        });
    }
};
