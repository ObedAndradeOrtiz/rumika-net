<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'tracks_attendance')) {
                $table->boolean('tracks_attendance')->default(false)->after('requires_face_verification');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'tracks_attendance')) {
                $table->dropColumn('tracks_attendance');
            }
        });
    }
};
