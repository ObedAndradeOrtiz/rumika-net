<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->unsignedSmallInteger('attendance_grace_minutes')
                ->default(10)
                ->after('attendance_radius_meters');
        });

        Schema::create('staff_attendance_exemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->date('work_date');
            $table->string('type', 30)->default('holiday');
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'branch_id', 'user_id', 'work_date'], 'staff_attendance_exemptions_unique');
            $table->index(['company_id', 'work_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_attendance_exemptions');

        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('attendance_grace_minutes');
        });
    }
};
