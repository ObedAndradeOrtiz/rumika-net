<?php

use App\Support\CompanyPlanCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (CompanyPlanCatalog::plans() as $plan) {
            $payload = [
                'name' => $plan['name'],
                'description' => $plan['description'],
                'monthly_price' => $plan['monthly_price'],
                'currency' => $plan['currency'],
                'sort_order' => $plan['sort_order'],
                'features' => json_encode($plan['features']),
                'is_active' => true,
                'updated_at' => now(),
            ];

            if (DB::table('company_plans')->where('slug', $plan['slug'])->exists()) {
                DB::table('company_plans')->where('slug', $plan['slug'])->update($payload);
            } else {
                DB::table('company_plans')->insert($payload + [
                    'slug' => $plan['slug'],
                    'created_at' => now(),
                ]);
            }
        }
    }
};
