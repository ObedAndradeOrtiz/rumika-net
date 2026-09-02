<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_pages', function (Blueprint $table) {
            if (! Schema::hasColumn('booking_pages', 'promotional_image_path')) {
                $table->string('promotional_image_path')->nullable()->after('background_image_path');
            }

            if (! Schema::hasColumn('booking_pages', 'publish_all_services')) {
                $table->boolean('publish_all_services')->default(true)->after('require_email');
            }
        });

        if (! Schema::hasTable('booking_page_services')) {
            Schema::create('booking_page_services', function (Blueprint $table) {
                $table->id();
                $table->foreignId('booking_page_id')->constrained()->cascadeOnDelete();
                $table->foreignId('service_id')->constrained()->cascadeOnDelete();
                $table->decimal('promotional_price', 10, 2)->nullable();
                $table->boolean('is_promoted')->default(false);
                $table->timestamps();

                $table->unique(['booking_page_id', 'service_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_page_services');

        foreach (['publish_all_services', 'promotional_image_path'] as $column) {
            if (Schema::hasColumn('booking_pages', $column)) {
                Schema::table('booking_pages', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }
    }
};
