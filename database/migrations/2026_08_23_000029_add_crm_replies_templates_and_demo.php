<?php

use App\Models\CompanyPlan;
use App\Support\CompanyPlanCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_conversations', function (Blueprint $table) {
            $table->boolean('is_demo')->default(false)->after('status');
            $table->index(['company_id', 'is_demo']);
        });

        Schema::create('crm_quick_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'is_active']);
        });

        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('whatsapp_channel_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('category')->default('utility');
            $table->string('language', 12)->default('es');
            $table->text('body');
            $table->string('status')->default('draft');
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->unique(['company_id', 'name', 'language']);
        });

        foreach (CompanyPlanCatalog::plans() as $plan) {
            CompanyPlan::query()->updateOrCreate(
                ['slug' => $plan['slug']],
                [
                    'name' => $plan['name'],
                    'description' => $plan['description'],
                    'monthly_price' => $plan['monthly_price'],
                    'currency' => $plan['currency'],
                    'features' => $plan['features'],
                    'sort_order' => $plan['sort_order'],
                    'is_active' => true,
                ],
            );
        }

        DB::table('companies')->orderBy('id')->chunkById(100, function ($companies) {
            foreach ($companies as $company) {
                DB::table('crm_quick_replies')->insert([
                    [
                        'company_id' => $company->id,
                        'title' => 'Confirmar cita',
                        'body' => 'Hola, te escribimos para confirmar tu cita. Por favor indicanos si podras asistir.',
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'company_id' => $company->id,
                        'title' => 'Enviar ubicacion',
                        'body' => 'Te compartimos la ubicacion de nuestra sucursal. Si necesitas ayuda para llegar, escribenos por este medio.',
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'company_id' => $company->id,
                        'title' => 'Reprogramar',
                        'body' => 'Podemos ayudarte a reprogramar tu cita. Indicanos que fecha y horario te queda mejor.',
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_templates');
        Schema::dropIfExists('crm_quick_replies');

        Schema::table('crm_conversations', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'is_demo']);
            $table->dropColumn('is_demo');
        });
    }
};
