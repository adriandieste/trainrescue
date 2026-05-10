<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\PredefinedExercise;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EntrenamientosIndexTest extends TestCase
{
    use RefreshDatabase;

    private function makeClubContext(): array
    {
        $trainer = User::factory()->create(['rol' => 'entrenador']);
        $club = Club::create([
            'name' => 'Club Entrenamientos',
            'admin_user_id' => $trainer->id,
        ]);
        $trainer->update(['club_id' => $club->id]);

        $athlete = User::factory()->create([
            'rol' => 'socorrista',
            'club_id' => $club->id,
        ]);

        $predefined = PredefinedExercise::create([
            'name' => 'Ejercicio para entrenamientos',
            'category' => 'resistencia',
            'technical_description' => 'Test entrenamientos index',
            'materials' => ['tabla'],
            'is_active' => true,
        ]);

        return compact('trainer', 'club', 'athlete', 'predefined');
    }

    public function test_athlete_can_access_entrenamientos_index(): void
    {
        ['athlete' => $athlete] = $this->makeClubContext();

        $response = $this->actingAs($athlete)->get(route('entrenamientos.index'));
        $response->assertOk();
        $response->assertViewIs('Ejercicios/EntrenosSocorrista');
    }

    public function test_athlete_sees_all_workouts_separated_by_date(): void
    {
        ['trainer' => $trainer, 'club' => $club, 'athlete' => $athlete, 'predefined' => $predefined] = $this->makeClubContext();

        // Create past workout
        $pastWorkout = Workout::create([
            'creator_user_id' => $trainer->id,
            'club_id' => $club->id,
            'title' => 'Pasado',
            'workout_date' => Carbon::yesterday()->format('Y-m-d'),
            'target_scope' => 'club',
            'is_template' => false,
        ]);

        $pastWorkout->exercises()->create([
            'predefined_exercise_id' => $predefined->id,
            'sort_order' => 0,
            'sets' => 3,
            'meters' => 100,
            'rest_seconds' => 45,
        ]);

        // Create future workout
        $futureWorkout = Workout::create([
            'creator_user_id' => $trainer->id,
            'club_id' => $club->id,
            'title' => 'Futuro',
            'workout_date' => Carbon::tomorrow()->addDay()->format('Y-m-d'),
            'target_scope' => 'club',
            'is_template' => false,
        ]);

        $futureWorkout->exercises()->create([
            'predefined_exercise_id' => $predefined->id,
            'sort_order' => 0,
            'sets' => 2,
            'meters' => 200,
            'rest_seconds' => 60,
        ]);

        $response = $this->actingAs($athlete)->get(route('entrenamientos.index'));
        $response->assertOk();

        $entrenamientos = collect($response->original?->getData()['page']['props']['entrenamientos'] ?? []);

        $this->assertCount(2, $entrenamientos);
        $this->assertTrue($entrenamientos->contains('id', $pastWorkout->id));
        $this->assertTrue($entrenamientos->contains('id', $futureWorkout->id));
    }

    public function test_entrenamientos_page_shows_exercise_details_with_technical_parameters(): void
    {
        ['trainer' => $trainer, 'club' => $club, 'athlete' => $athlete, 'predefined' => $predefined] = $this->makeClubContext();

        $workout = Workout::create([
            'creator_user_id' => $trainer->id,
            'club_id' => $club->id,
            'title' => 'Entrenamiento con detalles',
            'workout_date' => Carbon::tomorrow()->format('Y-m-d'),
            'target_scope' => 'club',
            'is_template' => false,
        ]);

        $workout->exercises()->create([
            'predefined_exercise_id' => $predefined->id,
            'sort_order' => 0,
            'sets' => 4,
            'meters' => 250,
            'rest_seconds' => 90,
        ]);

        $response = $this->actingAs($athlete)->get(route('entrenamientos.index'));
        $response->assertOk();

        $entrenamientos = collect($response->original?->getData()['page']['props']['entrenamientos'] ?? []);
        $workoutData = $entrenamientos->firstWhere('id', $workout->id);

        $this->assertNotNull($workoutData);
        $this->assertCount(1, $workoutData['exercises']);

        $exercise = $workoutData['exercises'][0];
        $this->assertEquals('Ejercicio para entrenamientos', $exercise['name']);
        $this->assertEquals(4, $exercise['sets']);
        $this->assertEquals(250, $exercise['meters']);
        $this->assertEquals(90, $exercise['rest_seconds']);
        $this->assertStringContainsString('4 x', $exercise['load_label']);
        $this->assertStringContainsString('250m', $exercise['load_label']);
        $this->assertStringContainsString('90s', $exercise['load_label']);
    }

    public function test_athlete_can_mark_past_workout_as_completed_from_entrenamientos(): void
    {
        ['trainer' => $trainer, 'club' => $club, 'athlete' => $athlete, 'predefined' => $predefined] = $this->makeClubContext();

        $workout = Workout::create([
            'creator_user_id' => $trainer->id,
            'club_id' => $club->id,
            'title' => 'Entrenamiento para marcar',
            'workout_date' => Carbon::yesterday()->format('Y-m-d'),
            'target_scope' => 'club',
            'is_template' => false,
        ]);

        $workout->exercises()->create([
            'predefined_exercise_id' => $predefined->id,
            'sort_order' => 0,
            'sets' => 3,
            'meters' => 150,
            'rest_seconds' => 45,
        ]);

        // Mark workout as completed
        $this->actingAs($athlete)
            ->patch(route('workouts.complete', $workout))
            ->assertRedirect(route('dashboard'));

        // Verify it's marked as completed in the database
        $this->assertDatabaseHas('workout_completions', [
            'workout_id' => $workout->id,
            'user_id' => $athlete->id,
        ]);

        // Verify the status is 'completed' when viewing the list
        $response = $this->actingAs($athlete)->get(route('entrenamientos.index'));
        $entrenamientos = collect($response->original?->getData()['page']['props']['entrenamientos'] ?? []);
        $workoutData = $entrenamientos->firstWhere('id', $workout->id);

        $this->assertEquals('completed', $workoutData['completion_status']);
    }

    public function test_trainer_cannot_access_entrenamientos_index(): void
    {
        $trainer = User::factory()->create(['rol' => 'entrenador']);

        $response = $this->actingAs($trainer)->get(route('entrenamientos.index'));
        $response->assertForbidden();
    }

    public function test_athlete_without_club_sees_empty_entrenamientos(): void
    {
        $athlete = User::factory()->create(['rol' => 'socorrista', 'club_id' => null]);

        $response = $this->actingAs($athlete)->get(route('entrenamientos.index'));
        $response->assertOk();

        $entrenamientos = collect($response->original?->getData()['page']['props']['entrenamientos'] ?? []);
        $this->assertCount(0, $entrenamientos);
    }
}

