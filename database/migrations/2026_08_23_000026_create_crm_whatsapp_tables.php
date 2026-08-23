<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('phone_number')->nullable();
            $table->string('phone_number_id')->unique();
            $table->string('waba_id')->nullable();
            $table->string('api_version')->default('v23.0');
            $table->text('access_token');
            $table->text('verify_token')->nullable();
            $table->text('audio_converter_api_key')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'is_active']);
            $table->index(['branch_id', 'is_active']);
        });

        Schema::create('crm_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('phone');
            $table->string('email')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('last_interaction_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'phone']);
            $table->index(['company_id', 'last_interaction_at']);
        });

        Schema::create('crm_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('whatsapp_channel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('crm_contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('open');
            $table->unsignedInteger('unread_count')->default(0);
            $table->text('last_message')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('last_customer_message_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status', 'last_message_at']);
            $table->index(['whatsapp_channel_id', 'last_message_at']);
        });

        Schema::create('crm_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('crm_conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('whatsapp_channel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('crm_contact_id')->constrained()->cascadeOnDelete();
            $table->string('wamid')->nullable()->unique();
            $table->string('direction');
            $table->string('type')->default('text');
            $table->text('body')->nullable();
            $table->string('status')->default('received');
            $table->string('media_id')->nullable();
            $table->string('media_url')->nullable();
            $table->string('media_mime_type')->nullable();
            $table->string('media_filename')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamp('message_at')->nullable();
            $table->boolean('is_read')->default(false);
            $table->string('reply_to_wamid')->nullable();
            $table->text('reply_preview')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'message_at']);
            $table->index(['crm_conversation_id', 'message_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_messages');
        Schema::dropIfExists('crm_conversations');
        Schema::dropIfExists('crm_contacts');
        Schema::dropIfExists('whatsapp_channels');
    }
};
