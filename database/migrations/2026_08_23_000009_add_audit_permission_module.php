<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('roles')
            ->whereIn('slug', ['administrador'])
            ->orderBy('id')
            ->each(function (object $role) {
                $permissions = json_decode((string) $role->permissions, true);

                if (! is_array($permissions)) {
                    $permissions = [];
                }

                $permissions['bitacora'] = ['view'];

                DB::table('roles')
                    ->where('id', $role->id)
                    ->update(['permissions' => json_encode($permissions)]);
            });
    }

    public function down(): void
    {
        DB::table('roles')->orderBy('id')->each(function (object $role) {
            $permissions = json_decode((string) $role->permissions, true);

            if (! is_array($permissions)) {
                return;
            }

            unset($permissions['bitacora']);

            DB::table('roles')
                ->where('id', $role->id)
                ->update(['permissions' => json_encode($permissions)]);
        });
    }
};
