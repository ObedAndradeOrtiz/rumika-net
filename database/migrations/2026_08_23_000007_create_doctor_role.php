<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'inicio' => ['view'],
            'agenda' => ['view', 'edit'],
            'historia_clinica' => ['view', 'create'],
        ];

        DB::table('companies')
            ->select('id')
            ->orderBy('id')
            ->each(function (object $company) use ($permissions) {
                DB::table('roles')->updateOrInsert(
                    [
                        'company_id' => $company->id,
                        'slug' => 'doctor',
                    ],
                    [
                        'name' => 'Doctor',
                        'scope' => 'company',
                        'description' => 'Atiende citas asignadas y registra historia clinica solo de sus pacientes.',
                        'permissions' => json_encode($permissions),
                        'is_system' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ],
                );
            });
    }

    public function down(): void
    {
        DB::table('roles')
            ->where('slug', 'doctor')
            ->where('is_system', true)
            ->delete();
    }
};
