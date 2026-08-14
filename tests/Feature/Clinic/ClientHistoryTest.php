<?php

namespace Tests\Feature\Clinic;

use App\Livewire\Clinic\ClientHistory;
use App\Models\Branch;
use App\Models\BusinessType;
use App\Models\Client;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClientHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_module_creates_edits_searches_and_inactivates_clients(): void
    {
        [$user, $company, $branch] = $this->companyContext();

        $this->actingAs($user);
        session(['active_branch_id' => $branch->id]);

        Livewire::test(ClientHistory::class)
            ->call('createClient')
            ->assertSet('showClientModal', true)
            ->set('fullName', 'Bethel Antezana')
            ->set('identityNumber', '1234567')
            ->set('phone', '70000000')
            ->set('email', 'bethel@rumika.test')
            ->set('birthDate', '1992-05-10')
            ->set('clinicalNotes', 'Alergia a lidocaina.')
            ->call('saveClient')
            ->assertHasNoErrors()
            ->assertSet('showClientModal', false);

        $client = Client::where('full_name', 'Bethel Antezana')->firstOrFail();

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'identity_number' => '1234567',
            'status' => 'active',
        ]);

        Livewire::test(ClientHistory::class)
            ->call('editClient', $client->id)
            ->assertSet('fullName', 'Bethel Antezana')
            ->set('fullName', 'Bethel Antenzana')
            ->set('phone', '71111111')
            ->call('saveClient')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'full_name' => 'Bethel Antenzana',
            'phone' => '71111111',
        ]);

        Livewire::test(ClientHistory::class)
            ->set('search', '71111111')
            ->assertSee('Bethel Antenzana')
            ->call('confirmInactivateClient', $client->id)
            ->assertSet('confirmingInactiveClientId', $client->id)
            ->call('inactivateClient', $client->id)
            ->assertSet('confirmingInactiveClientId', null)
            ->set('statusFilter', 'active')
            ->assertDontSee('Bethel Antenzana')
            ->set('statusFilter', 'inactive')
            ->assertSee('Bethel Antenzana');

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'status' => 'inactive',
        ]);
    }

    public function test_client_list_uses_compact_pagination_for_many_clients(): void
    {
        [$user, $company, $branch] = $this->companyContext();

        foreach (range(1, 16) as $index) {
            Client::create([
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'full_name' => sprintf('Cliente %02d', $index),
                'identity_number' => (string) (7700000 + $index),
                'phone' => (string) (70000000 + $index),
                'status' => 'active',
            ]);
        }

        $this->actingAs($user);
        session(['active_branch_id' => $branch->id]);

        Livewire::test(ClientHistory::class)
            ->assertSee('Pagina 1 de 2')
            ->assertSee('1-15 de 16')
            ->assertSee('Siguiente')
            ->assertDontSee('Showing 1 to 15 of 16 results');
    }

    private function companyContext(): array
    {
        $user = User::factory()->create();
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
