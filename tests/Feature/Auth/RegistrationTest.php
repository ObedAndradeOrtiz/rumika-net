<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response
            ->assertOk()
            ->assertSeeVolt('pages.auth.register')
            ->assertSee('Continuar con Google')
            ->assertDontSee('Crear cuenta y empresa');
    }

    public function test_registration_only_allows_google_flow(): void
    {
        $this->post('/register', [
            'email' => 'test@example.com',
            'password' => 'password',
        ])->assertMethodNotAllowed();

        $this->assertGuest();
    }
}
