<?php

namespace Tests\Feature\Onboarding;

use App\Livewire\Onboarding\CompanySetup;
use App\Models\Branch;
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

    public function test_repeated_submit_creates_only_one_branch(): void
    {
        [$company, $businessType] = $this->prepareRegistration();

        $component = Livewire::test(CompanySetup::class)
            ->set('companyName', '  Mi empresa  ')
            ->set('branchName', '  Central  ')
            ->set('businessTypeId', (string) $businessType->id);

        $component->call('save')->assertHasNoErrors()->assertRedirect('/dashboard');
        $component->call('save')->assertHasNoErrors()->assertRedirect('/dashboard');

        $this->assertSame(1, $company->branches()->count());
        $this->assertSame('Mi empresa', $company->fresh()->name);
        $this->assertSame('Central', $company->branches()->first()->name);
        $this->assertDatabaseHas('branch_user', ['user_id' => auth()->id(), 'branch_id' => $company->branches()->first()->id]);
    }

    public function test_failed_save_keeps_form_and_rolls_back_company_and_branch(): void
    {
        [$company, $businessType] = $this->prepareRegistration();
        $originalName = $company->name;

        $fail = true;
        Branch::created(function () use (&$fail) {
            if ($fail) {
                throw new \RuntimeException('Simulated branch assignment failure');
            }
        });

        $component = Livewire::test(CompanySetup::class)
            ->set('companyName', 'Mi empresa')
            ->set('branchName', 'Central')
            ->set('businessTypeId', (string) $businessType->id)
            ->call('save')
            ->assertHasErrors('registration')
            ->assertSet('companyName', 'Mi empresa')
            ->assertSet('businessTypeId', (string) $businessType->id)
            ->assertNoRedirect();

        $this->assertNull($company->fresh()->onboarding_completed_at);
        $this->assertSame($originalName, $company->fresh()->name);
        $this->assertSame(0, $company->branches()->count());

        $fail = false;
        $component->call('save')->assertHasNoErrors()->assertRedirect('/dashboard');
        $this->assertSame(1, $company->branches()->count());
    }

    public function test_completed_company_without_a_branch_can_finish_registration(): void
    {
        [$company, $businessType] = $this->prepareRegistration();
        $company->update(['onboarding_completed_at' => now()]);

        $this->get('/dashboard')->assertRedirect('/configuracion-inicial');
        Livewire::test(CompanySetup::class)
            ->assertNoRedirect()
            ->set('companyName', 'Mi empresa')
            ->set('branchName', 'Central')
            ->set('businessTypeId', (string) $businessType->id)
            ->call('save')->assertHasNoErrors()->assertRedirect('/dashboard');
        $this->assertSame(1, $company->branches()->count());
    }

    public function test_invalid_business_type_does_not_lose_entered_fields(): void
    {
        [$company, $businessType] = $this->prepareRegistration();
        $businessType->update(['is_active' => false]);

        Livewire::test(CompanySetup::class)
            ->set('companyName', 'Mi empresa')
            ->set('branchName', 'Central')
            ->set('businessTypeId', (string) $businessType->id)
            ->call('save')
            ->assertHasErrors('businessTypeId')
            ->assertSet('companyName', 'Mi empresa')
            ->assertSet('branchName', 'Central');

        $this->assertNull($company->fresh()->onboarding_completed_at);
        $this->assertSame(0, $company->branches()->count());
    }

    public function test_partial_branch_is_completed_instead_of_duplicated(): void
    {
        [$company, $businessType] = $this->prepareRegistration();
        $company->update(['onboarding_completed_at' => now()]);
        $branch = $company->branches()->create(['name' => 'Central', 'slug' => 'central']);

        $this->get('/dashboard')->assertRedirect('/configuracion-inicial');
        Livewire::test(CompanySetup::class)
            ->assertNoRedirect()
            ->assertSet('branchName', 'Central')
            ->set('businessTypeId', (string) $businessType->id)
            ->set('companyName', 'Mi empresa')
            ->call('save')->assertHasNoErrors()->assertRedirect('/dashboard');

        $this->assertSame(1, $company->branches()->count());
        $this->assertSame($businessType->id, $branch->fresh()->business_type_id);
    }

    public function test_staff_cannot_change_company_setup(): void
    {
        [$company, $businessType] = $this->prepareRegistration();
        $company->users()->updateExistingPivot(auth()->id(), ['role' => 'staff']);

        Livewire::test(CompanySetup::class)
            ->set('companyName', 'Empresa modificada')
            ->set('branchName', 'Central')
            ->set('businessTypeId', (string) $businessType->id)
            ->call('save')->assertForbidden();

        $this->assertSame('Empresa de Test', $company->fresh()->name);
        $this->assertSame(0, $company->branches()->count());
    }

    private function prepareRegistration(): array
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $company = Company::create(['name' => 'Empresa de Test', 'slug' => 'empresa-test', 'status' => 'trial']);
        $company->users()->attach($user->id, ['role' => 'owner', 'joined_at' => now()]);
        $businessType = BusinessType::create(['name' => 'Clinica', 'slug' => 'clinica', 'is_active' => true]);
        $this->actingAs($user);

        return [$company, $businessType];
    }
}
