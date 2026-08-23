<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_billing_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_plan_id')->nullable()->constrained('company_plans')->nullOnDelete();
            $table->timestamp('paid_at');
            $table->date('period_starts_at')->nullable();
            $table->date('period_ends_at')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'paid_at']);
        });

        DB::table('companies')
            ->whereNotNull('last_paid_at')
            ->orderBy('id')
            ->get(['id', 'company_plan_id', 'last_paid_at', 'access_expires_at', 'billing_notes'])
            ->each(function (object $company): void {
                $plan = DB::table('company_plans')->where('id', $company->company_plan_id)->first();

                DB::table('company_billing_payments')->insert([
                    'company_id' => $company->id,
                    'company_plan_id' => $company->company_plan_id,
                    'paid_at' => $company->last_paid_at,
                    'period_starts_at' => \Illuminate\Support\Carbon::parse($company->last_paid_at)->toDateString(),
                    'period_ends_at' => $company->access_expires_at ? \Illuminate\Support\Carbon::parse($company->access_expires_at)->toDateString() : null,
                    'amount' => $plan?->monthly_price ?? 0,
                    'currency' => $plan?->currency ?? 'USD',
                    'notes' => $company->billing_notes,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_billing_payments');
    }
};
