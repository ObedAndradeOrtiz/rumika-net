<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->string('template', 40)->default('clean');
            $table->string('mode', 20)->default('general');
            $table->string('primary_color', 20)->default('#008b7d');
            $table->string('accent_color', 20)->default('#dff7f2');
            $table->string('background_color', 20)->default('#f6f8fb');
            $table->string('background_image_path')->nullable();
            $table->string('font_family', 80)->default('Figtree');
            $table->string('icon_shape', 20)->default('rounded');
            $table->time('available_from')->default('09:00:00');
            $table->time('available_to')->default('18:00:00');
            $table->unsignedSmallInteger('slot_interval_minutes')->default(30);
            $table->unsignedSmallInteger('default_duration_minutes')->default(60);
            $table->boolean('show_prices')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_pages');
    }
};
