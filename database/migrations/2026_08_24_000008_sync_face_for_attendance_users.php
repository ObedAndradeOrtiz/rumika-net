<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'tracks_attendance') && Schema::hasColumn('users', 'requires_face_verification')) {
            DB::table('users')
                ->where('tracks_attendance', true)
                ->update(['requires_face_verification' => true]);
        }
    }
};
