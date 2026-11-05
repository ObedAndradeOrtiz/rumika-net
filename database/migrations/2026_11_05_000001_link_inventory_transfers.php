<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('inventory_movements', 'transfer_key')) {
            Schema::table('inventory_movements', function (Blueprint $table) {
                $table->uuid('transfer_key')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropColumn('transfer_key');
        });
    }
};
