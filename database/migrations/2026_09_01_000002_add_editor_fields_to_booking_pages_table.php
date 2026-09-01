<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_pages', function (Blueprint $table) {
            if (! Schema::hasColumn('booking_pages', 'hero_label')) {
                $table->string('hero_label', 80)->nullable()->after('subtitle');
            }

            if (! Schema::hasColumn('booking_pages', 'button_label')) {
                $table->string('button_label', 60)->nullable()->after('hero_label');
            }

            if (! Schema::hasColumn('booking_pages', 'success_message')) {
                $table->string('success_message', 240)->nullable()->after('button_label');
            }

            if (! Schema::hasColumn('booking_pages', 'min_days_ahead')) {
                $table->unsignedSmallInteger('min_days_ahead')->default(0)->after('default_duration_minutes');
            }

            if (! Schema::hasColumn('booking_pages', 'max_days_ahead')) {
                $table->unsignedSmallInteger('max_days_ahead')->default(30)->after('min_days_ahead');
            }

            if (! Schema::hasColumn('booking_pages', 'show_branch_cards')) {
                $table->boolean('show_branch_cards')->default(true)->after('show_prices');
            }

            if (! Schema::hasColumn('booking_pages', 'show_service_duration')) {
                $table->boolean('show_service_duration')->default(true)->after('show_branch_cards');
            }

            if (! Schema::hasColumn('booking_pages', 'show_company_logo')) {
                $table->boolean('show_company_logo')->default(true)->after('show_service_duration');
            }

            if (! Schema::hasColumn('booking_pages', 'require_identity')) {
                $table->boolean('require_identity')->default(false)->after('show_company_logo');
            }

            if (! Schema::hasColumn('booking_pages', 'require_email')) {
                $table->boolean('require_email')->default(false)->after('require_identity');
            }
        });
    }

    public function down(): void
    {
        foreach ([
            'require_email',
            'require_identity',
            'show_company_logo',
            'show_service_duration',
            'show_branch_cards',
            'max_days_ahead',
            'min_days_ahead',
            'success_message',
            'button_label',
            'hero_label',
        ] as $column) {
            if (Schema::hasColumn('booking_pages', $column)) {
                Schema::table('booking_pages', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }
    }
};
