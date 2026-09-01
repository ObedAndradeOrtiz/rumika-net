<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_pages', function (Blueprint $table) {
            $table->string('hero_label', 80)->nullable()->after('subtitle');
            $table->string('button_label', 60)->nullable()->after('hero_label');
            $table->string('success_message', 240)->nullable()->after('button_label');
            $table->unsignedSmallInteger('min_days_ahead')->default(0)->after('default_duration_minutes');
            $table->unsignedSmallInteger('max_days_ahead')->default(30)->after('min_days_ahead');
            $table->boolean('show_branch_cards')->default(true)->after('show_prices');
            $table->boolean('show_service_duration')->default(true)->after('show_branch_cards');
            $table->boolean('show_company_logo')->default(true)->after('show_service_duration');
            $table->boolean('require_identity')->default(false)->after('show_company_logo');
            $table->boolean('require_email')->default(false)->after('require_identity');
        });
    }

    public function down(): void
    {
        Schema::table('booking_pages', function (Blueprint $table) {
            $table->dropColumn([
                'hero_label',
                'button_label',
                'success_message',
                'min_days_ahead',
                'max_days_ahead',
                'show_branch_cards',
                'show_service_duration',
                'show_company_logo',
                'require_identity',
                'require_email',
            ]);
        });
    }
};
