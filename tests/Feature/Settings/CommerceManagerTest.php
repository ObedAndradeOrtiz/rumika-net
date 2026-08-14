<?php

namespace Tests\Feature\Settings;

use App\Livewire\Settings\CommerceManager;
use App\Models\Branch;
use App\Models\BusinessType;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CommerceManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_edit_and_delete_a_commerce_branch(): void
    {
        $user = User::factory()->create();
        $company = Company::create([
            'name' => 'Rumika Demo',
            'slug' => 'rumika-demo',
        ]);
        $clinic = BusinessType::create([
            'name' => 'Clinica',
            'slug' => 'clinica',
            'enabled_modules' => ['agenda', 'clientes', 'historial'],
        ]);
        $barbershop = BusinessType::create([
            'name' => 'Barberia',
            'slug' => 'barberia',
            'enabled_modules' => ['agenda', 'clientes'],
        ]);

        $company->users()->attach($user->id, [
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        $existingBranch = Branch::create([
            'company_id' => $company->id,
            'business_type_id' => $clinic->id,
            'name' => 'Sucursal Centro',
            'slug' => 'sucursal-centro',
            'status' => 'active',
        ]);

        $this->actingAs($user);

        Livewire::test(CommerceManager::class)
            ->set('name', 'Barberia Norte')
            ->set('businessTypeId', $barbershop->id)
            ->set('phone', '70000000')
            ->set('address', 'Zona Norte')
            ->set('status', 'active')
            ->call('save')
            ->assertHasNoErrors();

        $createdBranch = Branch::where('name', 'Barberia Norte')->firstOrFail();

        $this->assertDatabaseHas('branches', [
            'name' => 'Barberia Norte',
            'business_type_id' => $barbershop->id,
        ]);
        $this->assertDatabaseHas('branch_user', [
            'branch_id' => $createdBranch->id,
            'user_id' => $user->id,
        ]);

        Livewire::test(CommerceManager::class)
            ->call('edit', $createdBranch->id)
            ->set('name', 'Barberia Norte Editada')
            ->set('businessTypeId', $clinic->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('branches', [
            'id' => $createdBranch->id,
            'name' => 'Barberia Norte Editada',
            'business_type_id' => $clinic->id,
        ]);

        Livewire::test(CommerceManager::class)
            ->call('delete', $createdBranch->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('branches', [
            'id' => $createdBranch->id,
        ]);
        $this->assertDatabaseHas('branches', [
            'id' => $existingBranch->id,
        ]);
    }

    public function test_user_can_upload_and_replace_branch_logo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $company = Company::create([
            'name' => 'Rumika Demo',
            'slug' => 'rumika-demo',
        ]);
        $clinic = BusinessType::create([
            'name' => 'Clinica',
            'slug' => 'clinica',
            'enabled_modules' => ['agenda', 'clientes', 'historial'],
        ]);

        $company->users()->attach($user->id, [
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        $branch = Branch::create([
            'company_id' => $company->id,
            'business_type_id' => $clinic->id,
            'name' => 'Central Bethel',
            'slug' => 'central-bethel',
            'status' => 'active',
        ]);

        $this->actingAs($user);

        Livewire::test(CommerceManager::class)
            ->call('edit', $branch->id)
            ->set('logo', UploadedFile::fake()->image('central.png', 200, 200))
            ->call('save')
            ->assertHasNoErrors();

        $branch->refresh();

        $this->assertNotNull($branch->logo_path);
        Storage::disk('public')->assertExists($branch->logo_path);

        $firstLogoPath = $branch->logo_path;

        Livewire::test(CommerceManager::class)
            ->call('edit', $branch->id)
            ->assertSet('currentLogoPath', $firstLogoPath)
            ->set('logo', UploadedFile::fake()->image('central-new.png', 200, 200))
            ->call('save')
            ->assertHasNoErrors();

        $branch->refresh();

        $this->assertNotSame($firstLogoPath, $branch->logo_path);
        Storage::disk('public')->assertMissing($firstLogoPath);
        Storage::disk('public')->assertExists($branch->logo_path);
    }
}
