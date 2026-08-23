<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('treatment_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('treatment_payments', 'invoice_nit')) {
                $table->string('invoice_nit', 40)->nullable()->after('invoice_requested');
            }
            if (! Schema::hasColumn('treatment_payments', 'invoice_name')) {
                $table->string('invoice_name', 180)->nullable()->after('invoice_nit');
            }
            if (! Schema::hasColumn('treatment_payments', 'invoice_status')) {
                $table->string('invoice_status', 30)->default('not_requested')->after('invoice_name');
            }
            if (! Schema::hasColumn('treatment_payments', 'invoiced_at')) {
                $table->timestamp('invoiced_at')->nullable()->after('invoice_status');
            }
            if (! Schema::hasColumn('treatment_payments', 'invoiced_by_user_id')) {
                $table->foreignId('invoiced_by_user_id')->nullable()->after('invoiced_at')->constrained('users')->nullOnDelete();
            }
        });

        Schema::table('product_sales', function (Blueprint $table) {
            if (! Schema::hasColumn('product_sales', 'invoice_status')) {
                $table->string('invoice_status', 30)->default('not_requested')->after('invoice_requested');
            }
            if (! Schema::hasColumn('product_sales', 'invoiced_at')) {
                $table->timestamp('invoiced_at')->nullable()->after('invoice_status');
            }
            if (! Schema::hasColumn('product_sales', 'invoiced_by_user_id')) {
                $table->foreignId('invoiced_by_user_id')->nullable()->after('invoiced_at')->constrained('users')->nullOnDelete();
            }
        });

        DB::table('treatment_payments')
            ->where('invoice_requested', true)
            ->where('invoice_status', 'not_requested')
            ->update(['invoice_status' => 'pending']);

        DB::table('product_sales')
            ->where('invoice_requested', true)
            ->where('invoice_status', 'not_requested')
            ->update(['invoice_status' => 'pending']);
    }

    public function down(): void
    {
        Schema::table('product_sales', function (Blueprint $table) {
            if (Schema::hasColumn('product_sales', 'invoiced_by_user_id')) {
                $table->dropConstrainedForeignId('invoiced_by_user_id');
            }
            $columns = array_values(array_filter([
                Schema::hasColumn('product_sales', 'invoiced_at') ? 'invoiced_at' : null,
                Schema::hasColumn('product_sales', 'invoice_status') ? 'invoice_status' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('treatment_payments', function (Blueprint $table) {
            if (Schema::hasColumn('treatment_payments', 'invoiced_by_user_id')) {
                $table->dropConstrainedForeignId('invoiced_by_user_id');
            }
            $columns = array_values(array_filter([
                Schema::hasColumn('treatment_payments', 'invoiced_at') ? 'invoiced_at' : null,
                Schema::hasColumn('treatment_payments', 'invoice_status') ? 'invoice_status' : null,
                Schema::hasColumn('treatment_payments', 'invoice_name') ? 'invoice_name' : null,
                Schema::hasColumn('treatment_payments', 'invoice_nit') ? 'invoice_nit' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
