<?php

namespace Tests\Feature\Onboarding;

use App\Livewire\Onboarding\CompanySetup;
use App\Models\BusinessType;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CompanySetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_incomplete_google_company_cannot_open_dashboard(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $company = Company::create([
            'name' => 'Empresa de Test User',
            'slug' => 'empresa-test-user',
            'status' => 'trial',
        ]);
        $company->users()->attach($user->id, [
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('onboarding.company', absolute: false));
    }

    public function test_user_can_complete_company_setup_and_enter_dashboard(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $company = Company::create([
            'name' => 'Empresa de Test User',
            'slug' => 'empresa-test-user',
            'status' => 'trial',
        ]);
        $businessType = BusinessType::create([
            'name' => 'Clinica',
            'slug' => 'clinica',
            'is_active' => true,
        ]);
        $company->users()->attach($user->id, [
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        $this->actingAs($user);

        Livewire::test(CompanySetup::class)
            ->set('companyName', 'Clinica Central')
            ->set('branchName', 'Sucursal Centro')
            ->set('businessTypeId', (string) $businessType->id)
            ->set('phone', '70000000')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => 'Clinica Central',
        ]);
        $this->assertDatabaseHas('branches', [
            'company_id' => $company->id,
            'name' => 'Sucursal Centro',
            'business_type_id' => $businessType->id,
        ]);
        $this->assertNotNull($company->refresh()->onboarding_completed_at);
    }
}
