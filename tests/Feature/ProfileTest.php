<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\CustomExercise;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutExercise;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_when_accessing_profile_page(): void
    {
        $response = $this->get('/profile');

        $response->assertRedirect('/login');
    }

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_profile_view_displays_name_email_role_and_club_for_authenticated_user(): void
    {
        $club = Club::create(['name' => 'Club de Salvamento Madrid']);

        $user = User::factory()->create([
            'name' => 'Adrian Prueba',
            'email' => 'adrian@example.com',
            'rol' => 'entrenador',
            'club_id' => $club->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response
            ->assertOk()
            ->assertSee('Adrian Prueba')
            ->assertSee('adrian@example.com')
            ->assertSee('entrenador')
            ->assertSee('Club de Salvamento Madrid');
    }

    public function test_profile_view_does_not_expose_other_user_data(): void
    {
        $clubA = Club::create(['name' => 'Club A']);
        $clubB = Club::create(['name' => 'Club B']);

        $owner = User::factory()->create([
            'name' => 'Usuario Propio',
            'email' => 'propio@example.com',
            'rol' => 'socorrista',
            'club_id' => $clubA->id,
        ]);

        $other = User::factory()->create([
            'name' => 'Usuario Ajeno',
            'email' => 'ajeno@example.com',
            'rol' => 'entrenador',
            'club_id' => $clubB->id,
        ]);

        $response = $this
            ->actingAs($owner)
            ->get('/profile');

        $response
            ->assertOk()
            ->assertSee('Usuario Propio')
            ->assertSee('propio@example.com')
            ->assertSee('socorrista')
            ->assertSee('Club A')
            ->assertDontSee($other->name)
            ->assertDontSee($other->email)
            ->assertDontSee('Club B');
    }

    public function test_profile_normalizes_legacy_atleta_role_to_socorrista(): void
    {
        $user = User::factory()->create([
            'name' => 'Usuario Legacy',
            'email' => 'legacy@example.com',
            'rol' => 'atleta',
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response
            ->assertOk()
            ->assertSee('Usuario Legacy')
            ->assertSee('legacy@example.com')
            ->assertSee('socorrista')
            ->assertDontSee('Perfil de Atleta');
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }

    public function test_user_can_delete_their_account_when_custom_exercises_are_used_in_other_workouts(): void
    {
        $owner = User::factory()->create();
        $otherTrainer = User::factory()->create();

        $customExercise = CustomExercise::create([
            'user_id' => $owner->id,
            'name' => 'Remada con aletas',
            'description' => 'Serie técnica de remada',
            'default_sets' => 3,
            'default_rest_seconds' => 45,
        ]);

        $workout = Workout::create([
            'creator_user_id' => $otherTrainer->id,
            'title' => 'Entreno mixto',
            'workout_date' => now()->toDateString(),
            'target_scope' => 'club',
        ]);

        WorkoutExercise::create([
            'workout_id' => $workout->id,
            'custom_exercise_id' => $customExercise->id,
            'sort_order' => 0,
            'sets' => 4,
            'meters' => 100,
            'rest_seconds' => 30,
        ]);

        $response = $this
            ->actingAs($owner)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($owner->fresh());

        $customExercise->refresh();
        $this->assertNull($customExercise->user_id);
    }
}
