<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('full_name');
            $table->string('identity_number')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->date('birth_date')->nullable();
            $table->text('clinical_notes')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'identity_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
