<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cashbox_sessions', function (Blueprint $table) {
            $table->index('company_id', 'cashbox_sessions_company_id_index');
            $table->index('branch_id', 'cashbox_sessions_branch_id_index');
            $table->dropUnique(['company_id', 'branch_id', 'business_date']);
            $table->unsignedInteger('shift_number')->default(1)->after('business_date');
            $table->decimal('opening_amount', 12, 2)->default(0)->after('status');
            $table->decimal('expected_cash_amount', 12, 2)->default(0)->after('closing_notes');
            $table->decimal('counted_cash_amount', 12, 2)->default(0)->after('expected_cash_amount');
            $table->decimal('cash_difference', 12, 2)->default(0)->after('counted_cash_amount');
            $table->decimal('cash_total', 12, 2)->default(0)->after('cash_difference');
            $table->decimal('qr_total', 12, 2)->default(0)->after('cash_total');
            $table->decimal('expense_total', 12, 2)->default(0)->after('qr_total');
            $table->decimal('net_total', 12, 2)->default(0)->after('expense_total');

            $table->unique(['company_id', 'branch_id', 'business_date', 'shift_number'], 'cashbox_sessions_branch_day_shift_unique');
            $table->index(['company_id', 'branch_id', 'status'], 'cashbox_sessions_branch_status_index');
        });

        Schema::create('cashbox_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cashbox_session_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('treatment_payment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('expense_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->string('ticket_number')->unique();
            $table->string('title');
            $table->json('payload');
            $table->foreignId('printed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('printed_at')->nullable();
            $table->unsignedInteger('reprint_count')->default(0);
            $table->string('status')->default('generated');
            $table->timestamps();

            $table->index(['company_id', 'branch_id', 'type']);
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->boolean('uses_ticket_printer')->default(false)->after('logo_path');
            $table->string('printer_name')->nullable()->after('uses_ticket_printer');
            $table->string('printer_bridge_url')->nullable()->after('printer_name');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn(['uses_ticket_printer', 'printer_name', 'printer_bridge_url']);
        });

        Schema::dropIfExists('cashbox_tickets');

        Schema::table('cashbox_sessions', function (Blueprint $table) {
            $table->dropUnique('cashbox_sessions_branch_day_shift_unique');
            $table->dropIndex('cashbox_sessions_branch_status_index');
            $table->dropIndex('cashbox_sessions_company_id_index');
            $table->dropIndex('cashbox_sessions_branch_id_index');
            $table->dropColumn([
                'shift_number',
                'opening_amount',
                'expected_cash_amount',
                'counted_cash_amount',
                'cash_difference',
                'cash_total',
                'qr_total',
                'expense_total',
                'net_total',
            ]);
            $table->unique(['company_id', 'branch_id', 'business_date']);
        });
    }
};
