<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use DatabaseMigrations;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'RegistroSeguro1!',
            'password_confirmation' => 'RegistroSeguro1!',
            'rol' => 'atleta',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }
}
