<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    // ─── mustSelectRole prop ────────────────────────────────────────────────

    public function test_user_with_null_rol_receives_must_select_role_prop(): void
    {
        $user = User::factory()->create(['rol' => null]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk()
            ->assertInertia(fn ($page) => $page->where('mustSelectRole', true));
    }

    public function test_user_with_rol_does_not_receive_must_select_role_prop(): void
    {
        $user = User::factory()->create(['rol' => 'socorrista']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk()
            ->assertInertia(fn ($page) => $page->where('mustSelectRole', false));
    }

    public function test_guest_does_not_receive_must_select_role_prop_as_true(): void
    {
        // El prop sólo puede ser true si hay usuario autenticado con rol null
        $response = $this->get('/login');

        $response->assertOk()
            ->assertInertia(fn ($page) => $page->where('mustSelectRole', false));
    }

    // ─── POST /onboarding/update-rol ────────────────────────────────────────

    public function test_user_can_select_socorrista_rol(): void
    {
        $user = User::factory()->create(['rol' => null]);

        $response = $this->actingAs($user)
            ->post('/onboarding/update-rol', ['rol' => 'socorrista']);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/dashboard');

        $this->assertSame('socorrista', $user->fresh()->rol);
    }

    public function test_user_can_select_entrenador_rol(): void
    {
        $user = User::factory()->create(['rol' => null]);

        $response = $this->actingAs($user)
            ->post('/onboarding/update-rol', ['rol' => 'entrenador']);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/dashboard');

        $this->assertSame('entrenador', $user->fresh()->rol);
    }

    public function test_update_rol_rejects_invalid_value(): void
    {
        $user = User::factory()->create(['rol' => null]);

        $response = $this->actingAs($user)
            ->post('/onboarding/update-rol', ['rol' => 'admin']);

        $response->assertSessionHasErrors('rol');
        $this->assertNull($user->fresh()->rol);
    }

    public function test_update_rol_requires_a_value(): void
    {
        $user = User::factory()->create(['rol' => null]);

        $response = $this->actingAs($user)
            ->post('/onboarding/update-rol', []);

        $response->assertSessionHasErrors('rol');
        $this->assertNull($user->fresh()->rol);
    }

    public function test_guest_cannot_post_update_rol(): void
    {
        $response = $this->post('/onboarding/update-rol', ['rol' => 'socorrista']);

        $response->assertRedirect('/login');
    }

    // ─── Registro sin rol ───────────────────────────────────────────────────

    public function test_registration_creates_user_with_null_rol(): void
    {
        $response = $this->post('/register', [
            'name'                  => 'Nuevo Usuario',
            'email'                 => 'nuevo@example.com',
            'password'              => 'Password1!',
            'password_confirmation' => 'Password1!',
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'nuevo@example.com',
            'rol'   => null,
        ]);
    }

    public function test_registration_does_not_accept_rol_field(): void
    {
        // Aunque se envíe el rol, el backend lo ignora y crea el usuario con null
        $response = $this->post('/register', [
            'name'                  => 'Hacker User',
            'email'                 => 'hacker@example.com',
            'password'              => 'Password1!',
            'password_confirmation' => 'Password1!',
            'rol'                   => 'entrenador',
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'hacker@example.com',
            'rol'   => null,
        ]);
    }
}

