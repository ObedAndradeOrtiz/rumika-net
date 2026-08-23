<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commission_targets', function (Blueprint $table) {
            if (! Schema::hasColumn('commission_targets', 'starts_at')) {
                $table->date('starts_at')->nullable()->after('period_type');
            }

            if (! Schema::hasColumn('commission_targets', 'ends_at')) {
                $table->date('ends_at')->nullable()->after('starts_at');
            }
        });

        DB::table('commission_targets')
            ->whereNull('starts_at')
            ->update(['starts_at' => DB::raw('DATE(created_at)')]);
    }

    public function down(): void
    {
        Schema::table('commission_targets', function (Blueprint $table) {
            if (Schema::hasColumn('commission_targets', 'ends_at')) {
                $table->dropColumn('ends_at');
            }

            if (Schema::hasColumn('commission_targets', 'starts_at')) {
                $table->dropColumn('starts_at');
            }
        });
    }
};
