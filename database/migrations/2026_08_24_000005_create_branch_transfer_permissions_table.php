<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_transfer_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('to_branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('granted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'user_id', 'to_branch_id'], 'branch_transfer_permissions_user_target_unique');
            $table->index(['company_id', 'user_id']);
        });

        DB::table('roles')
            ->whereIn('slug', ['administrador', 'gerente'])
            ->orderBy('id')
            ->each(function (object $role) {
                $permissions = is_string($role->permissions)
                    ? json_decode($role->permissions, true)
                    : ($role->permissions ?? []);

                if (! is_array($permissions)) {
                    $permissions = [];
                }

                $current = $permissions['inventario_operaciones'] ?? [];
                $current = is_array($current) ? $current : [$current];
                $permissions['inventario_operaciones'] = array_values(array_unique([
                    ...$current,
                    'view',
                    'create',
                    'edit',
                    'transfer',
                    ...($role->slug === 'administrador' ? ['delete'] : []),
                ]));

                DB::table('roles')
                    ->where('id', $role->id)
                    ->update(['permissions' => json_encode($permissions)]);
            });
    }

    public function down(): void
    {
        DB::table('roles')
            ->whereIn('slug', ['administrador', 'gerente'])
            ->orderBy('id')
            ->each(function (object $role) {
                $permissions = is_string($role->permissions)
                    ? json_decode($role->permissions, true)
                    : ($role->permissions ?? []);

                if (! is_array($permissions)) {
                    return;
                }

                $permissions['inventario_operaciones'] = array_values(array_filter(
                    $permissions['inventario_operaciones'] ?? [],
                    fn (string $action) => $action !== 'transfer'
                ));

                DB::table('roles')
                    ->where('id', $role->id)
                    ->update(['permissions' => json_encode($permissions)]);
            });

        Schema::dropIfExists('branch_transfer_permissions');
    }
};
