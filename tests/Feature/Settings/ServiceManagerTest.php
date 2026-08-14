<?php

namespace Tests\Feature\Settings;

use App\Livewire\Settings\ServiceManager;
use App\Models\Branch;
use App\Models\BusinessType;
use App\Models\Company;
use App\Models\Service;
use App\Models\ServicePackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ServiceManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_services_and_packages_are_scoped_to_the_current_company(): void
    {
        [$admin, $company, $branch] = $this->companyContext('rumika-demo');
        [$otherAdmin, $otherCompany, $otherBranch] = $this->companyContext('otra-empresa');

        Service::create([
            'company_id' => $otherCompany->id,
            'branch_id' => $otherBranch->id,
            'name' => 'Servicio ajeno',
            'price' => 99,
        ]);

        $this->actingAs($admin);

        Livewire::test(ServiceManager::class)
            ->assertDontSee('Servicio ajeno')
            ->set('serviceName', 'Limpieza facial')
            ->set('servicePrice', '120')
            ->set('serviceDuration', '45')
            ->set('serviceBranchId', $branch->id)
            ->set('serviceStatus', 'available')
            ->call('saveService')
            ->assertHasNoErrors();

        $service = Service::where('company_id', $company->id)->where('name', 'Limpieza facial')->firstOrFail();

        Livewire::test(ServiceManager::class)
            ->set('packageName', 'Paquete facial')
            ->set('packagePrice', '200')
            ->set('packageStatus', 'available')
            ->set('startsAt', '2026-08-01')
            ->set('expiresAt', '2026-08-31')
            ->set('packageServiceIds', [(string) $service->id])
            ->call('savePackage')
            ->assertHasNoErrors();

        $package = ServicePackage::where('company_id', $company->id)->where('name', 'Paquete facial')->firstOrFail();

        $this->assertDatabaseHas('service_package_items', [
            'service_package_id' => $package->id,
            'service_id' => $service->id,
        ]);

        $this->actingAs($otherAdmin);

        Livewire::test(ServiceManager::class)
            ->assertSee('Servicio ajeno')
            ->assertDontSee('Limpieza facial')
            ->assertDontSee('Paquete facial');
    }

    private function companyContext(string $slug): array
    {
        $user = User::factory()->create(['email' => "{$slug}@rumika.test"]);
        $company = Company::create([
            'name' => str($slug)->headline()->toString(),
            'slug' => $slug,
        ]);
        $businessType = BusinessType::create([
            'name' => "Clinica {$slug}",
            'slug' => "clinica-{$slug}",
            'enabled_modules' => ['agenda', 'clientes', 'historial'],
        ]);
        $branch = Branch::create([
            'company_id' => $company->id,
            'business_type_id' => $businessType->id,
            'name' => 'Sucursal Centro',
            'slug' => 'sucursal-centro',
            'status' => 'active',
        ]);

        $company->users()->attach($user->id, [
            'role' => 'owner',
            'joined_at' => now(),
        ]);
        $branch->users()->attach($user->id, [
            'assigned_at' => now(),
        ]);

        return [$user, $company, $branch];
    }
}
