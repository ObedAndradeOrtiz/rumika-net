<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->timestamp('onboarding_completed_at')->nullable()->after('status');
        });

        DB::table('companies')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('branches')
                    ->whereColumn('branches.company_id', 'companies.id');
            })
            ->whereNull('onboarding_completed_at')
            ->update(['onboarding_completed_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('onboarding_completed_at');
        });
    }
};
