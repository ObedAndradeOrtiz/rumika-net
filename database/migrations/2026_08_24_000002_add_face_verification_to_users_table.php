<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('requires_face_verification')->default(false)->after('status');
            $table->json('face_descriptor')->nullable()->after('requires_face_verification');
            $table->timestamp('face_registered_at')->nullable()->after('face_descriptor');
            $table->timestamp('last_face_verified_at')->nullable()->after('face_registered_at');
            $table->string('last_face_verified_ip', 45)->nullable()->after('last_face_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'requires_face_verification',
                'face_descriptor',
                'face_registered_at',
                'last_face_verified_at',
                'last_face_verified_ip',
            ]);
        });
    }
};
