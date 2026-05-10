<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class GoogleAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_redirect_endpoint_redirects_user_to_provider(): void
    {
        Socialite::shouldReceive('driver')->once()->with('google')->andReturnSelf();
        Socialite::shouldReceive('redirect')->once()->andReturn(redirect('https://accounts.google.com/o/oauth2/auth'));

        $response = $this->get(route('auth.google.redirect'));

        $response->assertRedirect('https://accounts.google.com/o/oauth2/auth');
    }

    public function test_google_callback_creates_new_user_and_logs_in(): void
    {
        Socialite::shouldReceive('driver')->once()->with('google')->andReturnSelf();
        Socialite::shouldReceive('user')->once()->andReturn($this->fakeGoogleUser(
            id: 'google-100',
            name: 'Usuario Nuevo',
            email: 'nuevo.google@example.com',
            avatar: 'https://avatar.example/new.jpg',
        ));

        $response = $this->get(route('auth.google.callback'));

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $this->assertDatabaseHas('users', [
            'email' => 'nuevo.google@example.com',
            'name' => 'Usuario Nuevo',
            'google_id' => 'google-100',
            'google_avatar' => 'https://avatar.example/new.jpg',
        ]);
    }

    public function test_google_callback_links_existing_user_by_email(): void
    {
        $existing = User::factory()->create([
            'email' => 'existente@example.com',
            'google_id' => null,
        ]);

        Socialite::shouldReceive('driver')->once()->with('google')->andReturnSelf();
        Socialite::shouldReceive('user')->once()->andReturn($this->fakeGoogleUser(
            id: 'google-200',
            name: 'Usuario Existente',
            email: 'existente@example.com',
            avatar: 'https://avatar.example/existing.jpg',
        ));

        $response = $this->get(route('auth.google.callback'));

        $this->assertAuthenticatedAs($existing->fresh());
        $response->assertRedirect(route('dashboard', absolute: false));

        $this->assertDatabaseHas('users', [
            'id' => $existing->id,
            'email' => 'existente@example.com',
            'google_id' => 'google-200',
            'google_avatar' => 'https://avatar.example/existing.jpg',
        ]);
    }

    public function test_google_callback_handles_provider_failure(): void
    {
        Socialite::shouldReceive('driver')->once()->with('google')->andReturnSelf();
        Socialite::shouldReceive('user')->once()->andThrow(new \Exception('OAuth failed'));

        $response = $this->get(route('auth.google.callback'));

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
    }

    private function fakeGoogleUser(string $id, string $name, string $email, ?string $avatar = null): SocialiteUser
    {
        $user = new SocialiteUser();
        $user->id = $id;
        $user->name = $name;
        $user->email = $email;
        $user->avatar = $avatar;
        $user->user = ['email_verified' => true];

        return $user;
    }
}

