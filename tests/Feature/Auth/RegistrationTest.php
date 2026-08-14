<?php

namespace Tests\Feature\Auth;

use App\Models\BusinessType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response
            ->assertOk()
            ->assertSeeVolt('pages.auth.register');
    }

    public function test_new_users_can_register(): void
    {
        $businessType = BusinessType::create([
            'name' => 'Clinica',
            'slug' => 'clinica',
            'enabled_modules' => ['agenda', 'clientes', 'historial'],
        ]);

        $component = Volt::test('pages.auth.register')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('company_name', 'Clinica Central')
            ->set('branch_name', 'Sucursal Centro')
            ->set('business_type_id', (string) $businessType->id)
            ->set('password', 'password')
            ->set('password_confirmation', 'password');

        $component->call('register');

        $component->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('companies', ['name' => 'Clinica Central']);
        $this->assertDatabaseHas('branches', ['name' => 'Sucursal Centro']);
        $this->assertDatabaseHas('company_user', ['role' => 'owner']);
    }
}
