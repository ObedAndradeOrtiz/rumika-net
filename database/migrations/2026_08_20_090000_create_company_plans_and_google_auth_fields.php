<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->decimal('monthly_price', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        DB::table('company_plans')->insert([
            [
                'name' => 'Free',
                'slug' => 'free',
                'description' => 'Plan inicial para probar Rumika.',
                'monthly_price' => 0,
                'currency' => 'USD',
                'features' => json_encode(['Acceso basico', 'Una empresa', 'Configuracion inicial']),
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Basico',
                'slug' => 'basico',
                'description' => 'Operaciones esenciales para negocios pequenos.',
                'monthly_price' => 30,
                'currency' => 'USD',
                'features' => json_encode(['Agenda', 'Clientes', 'Servicios']),
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Plus',
                'slug' => 'plus',
                'description' => 'Gestion completa para equipos en crecimiento.',
                'monthly_price' => 60,
                'currency' => 'USD',
                'features' => json_encode(['Agenda', 'Clientes', 'Inventario', 'Caja']),
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Empresa',
                'slug' => 'empresa',
                'description' => 'Control avanzado para varias sucursales.',
                'monthly_price' => 90,
                'currency' => 'USD',
                'features' => json_encode(['Sucursales', 'Roles', 'Finanzas', 'Registros']),
                'is_active' => true,
                'sort_order' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        Schema::table('companies', function (Blueprint $table) {
            $table->foreignId('company_plan_id')
                ->nullable()
                ->after('logo_path')
                ->constrained('company_plans')
                ->nullOnDelete();
        });

        $freePlanId = DB::table('company_plans')->where('slug', 'free')->value('id');
        DB::table('companies')->whereNull('company_plan_id')->update(['company_plan_id' => $freePlanId]);

        Schema::table('users', function (Blueprint $table) {
            $table->string('firebase_uid')->nullable()->unique()->after('email_verified_at');
            $table->string('auth_provider')->nullable()->after('firebase_uid');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['firebase_uid']);
            $table->dropColumn(['firebase_uid', 'auth_provider']);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_plan_id');
        });

        Schema::dropIfExists('company_plans');
    }
};
