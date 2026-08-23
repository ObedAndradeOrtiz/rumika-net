<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical_specialties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'name']);
        });

        Schema::create('clinical_specialty_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinical_specialty_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['clinical_specialty_id', 'user_id'], 'clinical_specialty_user_unique');
        });

        Schema::create('clinical_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('category')->default('ficha_inicial');
            $table->longText('body')->nullable();
            $table->json('fields')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'category', 'is_active']);
        });

        Schema::create('clinical_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('appointment_service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('clinical_template_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('type')->default('ficha');
            $table->longText('content')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'client_id', 'type']);
        });

        Schema::create('clinical_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('appointment_service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('clinical_record_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('file_path');
            $table->string('file_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'client_id']);
        });

        Schema::create('clinical_prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('appointment_service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('issued_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title')->default('Receta');
            $table->longText('indications');
            $table->date('issued_at');
            $table->timestamps();

            $table->index(['company_id', 'client_id', 'issued_at']);
        });

        Schema::create('clinical_patient_accesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('granted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('can_view')->default(true);
            $table->boolean('can_create')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'client_id', 'user_id'], 'clinical_patient_access_unique');
            $table->index(['company_id', 'user_id', 'can_view']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_patient_accesses');
        Schema::dropIfExists('clinical_prescriptions');
        Schema::dropIfExists('clinical_documents');
        Schema::dropIfExists('clinical_records');
        Schema::dropIfExists('clinical_templates');
        Schema::dropIfExists('clinical_specialty_user');
        Schema::dropIfExists('clinical_specialties');
    }
};
