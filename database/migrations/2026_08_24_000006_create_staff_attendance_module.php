<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Support\CompanyPlanCatalog;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table): void {
            if (! Schema::hasColumn('branches', 'attendance_latitude')) {
                $table->decimal('attendance_latitude', 10, 7)->nullable()->after('address');
            }

            if (! Schema::hasColumn('branches', 'attendance_longitude')) {
                $table->decimal('attendance_longitude', 10, 7)->nullable()->after('attendance_latitude');
            }

            if (! Schema::hasColumn('branches', 'attendance_radius_meters')) {
                $table->unsignedInteger('attendance_radius_meters')->default(120)->after('attendance_longitude');
            }
        });

        Schema::create('staff_schedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('weekday');
            $table->boolean('is_working_day')->default(true);
            $table->time('starts_at')->nullable();
            $table->time('ends_at')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'user_id', 'weekday']);
            $table->index(['company_id', 'user_id']);
        });

        Schema::create('staff_attendance_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('work_date');
            $table->string('status', 30)->default('open');
            $table->dateTime('check_in_at')->nullable();
            $table->foreignId('check_in_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->decimal('check_in_latitude', 10, 7)->nullable();
            $table->decimal('check_in_longitude', 10, 7)->nullable();
            $table->unsignedInteger('check_in_distance_meters')->nullable();
            $table->unsignedTinyInteger('check_in_face_similarity')->nullable();
            $table->string('check_in_photo_path')->nullable();
            $table->dateTime('check_out_at')->nullable();
            $table->foreignId('check_out_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->decimal('check_out_latitude', 10, 7)->nullable();
            $table->decimal('check_out_longitude', 10, 7)->nullable();
            $table->unsignedInteger('check_out_distance_meters')->nullable();
            $table->unsignedTinyInteger('check_out_face_similarity')->nullable();
            $table->string('check_out_photo_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'user_id', 'work_date']);
            $table->index(['company_id', 'work_date']);
            $table->index(['user_id', 'status']);
        });

        DB::table('roles')
            ->whereIn('slug', ['administrador', 'gerente'])
            ->update([
                'permissions' => DB::raw("JSON_SET(COALESCE(permissions, JSON_OBJECT()), '$.recursos_humanos', JSON_ARRAY('view', 'create', 'edit', 'delete'))"),
            ]);

        foreach (CompanyPlanCatalog::plans() as $plan) {
            DB::table('company_plans')
                ->where('slug', $plan['slug'])
                ->update([
                    'features' => json_encode($plan['features']),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_attendance_records');
        Schema::dropIfExists('staff_schedules');

        Schema::table('branches', function (Blueprint $table): void {
            $columns = [
                Schema::hasColumn('branches', 'attendance_radius_meters') ? 'attendance_radius_meters' : null,
                Schema::hasColumn('branches', 'attendance_longitude') ? 'attendance_longitude' : null,
                Schema::hasColumn('branches', 'attendance_latitude') ? 'attendance_latitude' : null,
            ];

            $columns = array_values(array_filter($columns));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
