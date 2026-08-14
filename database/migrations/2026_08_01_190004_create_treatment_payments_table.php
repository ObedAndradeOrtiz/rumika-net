<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatment_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('treatment_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('method');
            $table->boolean('invoice_requested')->default(false);
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('paid_at');
            $table->timestamps();

            $table->index(['company_id', 'branch_id', 'paid_at']);
            $table->index(['client_id', 'method']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatment_payments');
    }
};
