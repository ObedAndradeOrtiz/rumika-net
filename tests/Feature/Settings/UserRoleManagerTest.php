<?php

namespace Tests\Feature\Settings;

use App\Livewire\Settings\UserRoleManager;
use App\Models\Branch;
use App\Models\BusinessType;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class UserRoleManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_user_and_custom_role_with_permissions(): void
    {
        [$admin, $company, $branch] = $this->companyContext();

        $this->actingAs($admin);

        Livewire::test(UserRoleManager::class)
            ->set('roleName', 'Coordinador')
            ->set('roleDescription', 'Controla agenda y clientes')
            ->set('rolePermissions', [
                'inicio' => ['view'],
                'agenda' => ['view', 'create', 'edit'],
                'clientes' => ['view', 'create'],
            ])
            ->call('saveRole')
            ->assertHasNoErrors();

        $role = Role::where('company_id', $company->id)
            ->where('slug', 'coordinador')
            ->firstOrFail();

        $this->assertSame(['view', 'create', 'edit'], $role->permissions['agenda']);

        Livewire::test(UserRoleManager::class)
            ->set('userName', 'Nuevo Usuario')
            ->set('userEmail', 'nuevo@rumika.test')
            ->set('userPassword', 'password')
            ->set('userRoleId', $role->id)
            ->set('userBranchIds', [(string) $branch->id])
            ->call('saveUser')
            ->assertHasNoErrors();

        $user = User::where('email', 'nuevo@rumika.test')->firstOrFail();

        $this->assertDatabaseHas('company_user', [
            'company_id' => $company->id,
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('branch_user', [
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'role_id' => $role->id,
        ]);
    }

    public function test_base_roles_can_be_edited_but_not_deleted(): void
    {
        [$admin] = $this->companyContext();

        $this->actingAs($admin);

        $component = Livewire::test(UserRoleManager::class);
        $role = Role::where('slug', 'recepcion')->firstOrFail();

        $component
            ->call('editRole', $role->id)
            ->set('roleDescription', 'Atencion y agenda actualizada')
            ->call('saveRole')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'description' => 'Atencion y agenda actualizada',
            'is_system' => true,
        ]);

        Livewire::test(UserRoleManager::class)
            ->call('deleteRole', $role->id)
            ->assertHasErrors('roleDelete');

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
        ]);
    }

    public function test_default_reception_and_professional_roles_do_not_receive_extra_views(): void
    {
        [$admin] = $this->companyContext();

        $this->actingAs($admin);

        Livewire::test(UserRoleManager::class)
            ->assertHasNoErrors();

        $reception = Role::where('slug', 'recepcion')->firstOrFail();
        $professional = Role::where('slug', 'profesional')->firstOrFail();

        $this->assertArrayHasKey('agenda', $reception->permissions);
        $this->assertArrayHasKey('clientes', $reception->permissions);
        $this->assertArrayNotHasKey('inventario', $reception->permissions);
        $this->assertArrayNotHasKey('caja', $reception->permissions);
        $this->assertArrayNotHasKey('resumen_financiero', $professional->permissions);
        $this->assertArrayNotHasKey('gastos', $professional->permissions);
    }

    public function test_role_permissions_ignore_unexpected_boolean_values(): void
    {
        [$admin, $company] = $this->companyContext();

        $this->actingAs($admin);

        Livewire::test(UserRoleManager::class)
            ->set('roleName', 'Rol Temporal')
            ->set('rolePermissions', [
                'agenda' => ['view', 'edit'],
                'clientes' => true,
                'caja' => false,
            ])
            ->call('saveRole')
            ->assertHasNoErrors();

        $role = Role::where('company_id', $company->id)
            ->where('slug', 'rol-temporal')
            ->firstOrFail();

        $this->assertSame(['view', 'edit'], $role->permissions['agenda']);
        $this->assertArrayNotHasKey('clientes', $role->permissions);
        $this->assertArrayNotHasKey('caja', $role->permissions);
    }

    public function test_users_can_be_filtered_and_delete_marks_inactive(): void
    {
        [$admin, $company, $branch] = $this->companyContext();
        $role = Role::create([
            'company_id' => $company->id,
            'name' => 'Recepcion',
            'slug' => 'recepcion',
            'scope' => 'company',
            'permissions' => ['agenda' => ['view']],
            'is_system' => true,
        ]);
        $otherBranch = Branch::create([
            'company_id' => $company->id,
            'business_type_id' => $branch->business_type_id,
            'name' => 'Sucursal Norte',
            'slug' => 'sucursal-norte',
            'status' => 'active',
        ]);
        $user = User::factory()->create([
            'name' => 'Ana Norte',
            'email' => 'ana.norte@rumika.test',
            'status' => 'active',
        ]);
        $company->users()->attach($user->id, [
            'role' => 'member',
            'joined_at' => now(),
        ]);
        $otherBranch->users()->attach($user->id, [
            'role_id' => $role->id,
            'assigned_at' => now(),
        ]);

        $this->actingAs($admin);

        Livewire::test(UserRoleManager::class)
            ->set('userSearch', 'ana.norte')
            ->assertSee('Ana Norte')
            ->set('userBranchFilter', (string) $branch->id)
            ->assertDontSee('Ana Norte')
            ->set('userBranchFilter', (string) $otherBranch->id)
            ->assertSee('Ana Norte')
            ->set('userRoleFilter', (string) $role->id)
            ->assertSee('Ana Norte')
            ->call('confirmDeleteUser', $user->id)
            ->call('deleteUser', $user->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'status' => 'inactive',
        ]);
        $this->assertDatabaseHas('branch_user', [
            'branch_id' => $otherBranch->id,
            'user_id' => $user->id,
            'role_id' => $role->id,
        ]);
    }

    public function test_admin_can_reset_user_password_and_reactivate_access(): void
    {
        [$admin, $company, $branch] = $this->companyContext();
        $role = Role::create([
            'company_id' => $company->id,
            'name' => 'Recepcion',
            'slug' => 'recepcion',
            'scope' => 'company',
            'permissions' => ['agenda' => ['view']],
            'is_system' => true,
        ]);
        $user = User::factory()->create([
            'name' => 'Usuario Bloqueado',
            'email' => 'bloqueado@rumika.test',
            'status' => 'inactive',
        ]);
        $company->users()->attach($user->id, [
            'role' => 'member',
            'joined_at' => now(),
        ]);
        $branch->users()->attach($user->id, [
            'role_id' => $role->id,
            'assigned_at' => now(),
        ]);

        $this->actingAs($admin);

        Livewire::test(UserRoleManager::class)
            ->call('editUser', $user->id)
            ->set('userStatus', 'active')
            ->set('userPassword', 'nueva-clave-123')
            ->call('saveUser')
            ->assertHasNoErrors();

        $user->refresh();

        $this->assertSame('active', $user->status);
        $this->assertTrue(Hash::check('nueva-clave-123', $user->password));
    }


    private function companyContext(): array
    {
        $admin = User::factory()->create(['email' => 'admin@rumika.test']);
        $company = Company::create([
            'name' => 'Rumika Demo',
            'slug' => 'rumika-demo',
        ]);
        $businessType = BusinessType::create([
            'name' => 'Clinica',
            'slug' => 'clinica',
            'enabled_modules' => ['agenda', 'clientes', 'historial'],
        ]);
        $branch = Branch::create([
            'company_id' => $company->id,
            'business_type_id' => $businessType->id,
            'name' => 'Sucursal Centro',
            'slug' => 'sucursal-centro',
            'status' => 'active',
        ]);

        $company->users()->attach($admin->id, [
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        return [$admin, $company, $branch];
    }
}
