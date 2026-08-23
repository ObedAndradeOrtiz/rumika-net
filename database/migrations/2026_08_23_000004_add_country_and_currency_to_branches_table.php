<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->string('country_code', 2)->default('BO')->after('slug');
            $table->string('currency_code', 3)->default('BOB')->after('country_code');
            $table->string('currency_symbol', 8)->default('Bs')->after('currency_code');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn(['country_code', 'currency_code', 'currency_symbol']);
        });
    }
};
