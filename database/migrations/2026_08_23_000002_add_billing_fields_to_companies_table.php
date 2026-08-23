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
            $table->timestamp('trial_ends_at')->nullable()->after('onboarding_completed_at');
            $table->timestamp('access_expires_at')->nullable()->after('trial_ends_at');
            $table->timestamp('last_paid_at')->nullable()->after('access_expires_at');
            $table->date('next_payment_due_at')->nullable()->after('last_paid_at');
            $table->string('billing_status')->default('trial')->after('next_payment_due_at');
            $table->text('billing_notes')->nullable()->after('billing_status');
        });

        DB::table('companies')
            ->whereNull('trial_ends_at')
            ->orderBy('id')
            ->get(['id', 'created_at'])
            ->each(function ($company): void {
                DB::table('companies')
                    ->where('id', $company->id)
                    ->update([
                        'trial_ends_at' => \Illuminate\Support\Carbon::parse($company->created_at)->addDays(3),
                    ]);
            });

        DB::table('companies')
            ->where('status', 'active')
            ->whereNull('access_expires_at')
            ->update([
                'billing_status' => 'paid',
                'last_paid_at' => now(),
                'access_expires_at' => now()->addMonth(),
                'next_payment_due_at' => now()->addMonth()->toDateString(),
            ]);
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'trial_ends_at',
                'access_expires_at',
                'last_paid_at',
                'next_payment_due_at',
                'billing_status',
                'billing_notes',
            ]);
        });
    }
};
