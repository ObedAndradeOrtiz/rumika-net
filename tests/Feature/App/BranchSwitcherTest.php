<?php

namespace Tests\Feature\App;

use App\Livewire\App\BranchSwitcher;
use App\Models\Branch;
use App\Models\BusinessType;
use App\Models\Company;
use App\Models\User;
use App\Support\ActiveBranch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BranchSwitcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_only_switch_to_assigned_branches(): void
    {
        $user = User::factory()->create();
        $company = Company::create(['name' => 'Rumika Demo', 'slug' => 'rumika-demo']);
        $type = BusinessType::create(['name' => 'Clinica', 'slug' => 'clinica']);

        $allowed = Branch::create([
            'company_id' => $company->id,
            'business_type_id' => $type->id,
            'name' => 'Sucursal Permitida',
            'slug' => 'permitida',
        ]);
        $blocked = Branch::create([
            'company_id' => $company->id,
            'business_type_id' => $type->id,
            'name' => 'Sucursal Bloqueada',
            'slug' => 'bloqueada',
        ]);

        $company->users()->attach($user->id, ['role' => 'member', 'joined_at' => now()]);
        $allowed->users()->attach($user->id, ['assigned_at' => now()]);

        $this->actingAs($user);

        Livewire::test(BranchSwitcher::class)
            ->assertSee('Sucursal Permitida')
            ->assertDontSee('Sucursal Bloqueada')
            ->call('select', $blocked->id);

        $this->assertNotSame($blocked->id, session('active_branch_id'));

        Livewire::test(BranchSwitcher::class)->call('select', $allowed->id);

        $this->assertSame($allowed->id, session('active_branch_id'));
    }

    public function test_switching_branch_refreshes_current_page_and_remembers_selection(): void
    {
        $user = User::factory()->create();
        $company = Company::create(['name' => 'Rumika Demo', 'slug' => 'rumika-demo']);
        $type = BusinessType::create(['name' => 'Clinica', 'slug' => 'clinica']);
        $first = Branch::create([
            'company_id' => $company->id,
            'business_type_id' => $type->id,
            'name' => 'Sucursal Primera',
            'slug' => 'primera',
        ]);
        $second = Branch::create([
            'company_id' => $company->id,
            'business_type_id' => $type->id,
            'name' => 'Sucursal Segunda',
            'slug' => 'segunda',
        ]);

        $company->users()->attach($user->id, ['role' => 'owner', 'joined_at' => now()]);
        $first->users()->attach($user->id, ['assigned_at' => now()]);
        $second->users()->attach($user->id, ['assigned_at' => now()]);

        $this->actingAs($user);

        Livewire::test(BranchSwitcher::class)
            ->call('select', $second->id)
            ->assertDispatched('branch-switched');

        $this->assertSame($second->id, session('active_branch_id'));

        session()->forget('active_branch_id');

        request()->cookies->set(ActiveBranch::cookieName($user), (string) $second->id);

        $resolvedBranch = ActiveBranch::resolve($user, collect([$first, $second]));

        $this->assertSame($second->id, $resolvedBranch->id);
        $this->assertSame($second->id, session('active_branch_id'));
    }

    public function test_user_can_update_company_display_name_from_switcher(): void
    {
        $user = User::factory()->create(['name' => 'Obed Andrade']);
        $company = Company::create(['name' => 'Rumika Demo', 'slug' => 'rumika-demo']);
        $type = BusinessType::create(['name' => 'Clinica', 'slug' => 'clinica']);
        $branch = Branch::create([
            'company_id' => $company->id,
            'business_type_id' => $type->id,
            'name' => 'Central Bethel',
            'slug' => 'central-bethel',
        ]);

        $company->users()->attach($user->id, ['role' => 'owner', 'joined_at' => now()]);
        $branch->users()->attach($user->id, ['assigned_at' => now()]);

        $this->actingAs($user);

        Livewire::test(BranchSwitcher::class)
            ->assertSee('Rumika Demo')
            ->call('editCompanyName')
            ->set('companyName', 'Bethel Centro Medico')
            ->call('saveCompanyName')
            ->assertHasNoErrors()
            ->assertSee('Bethel Centro Medico');

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => 'Bethel Centro Medico',
        ]);
    }
}
