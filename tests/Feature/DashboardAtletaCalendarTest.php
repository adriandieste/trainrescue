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

class DashboardAtletaCalendarTest extends TestCase
{
    use RefreshDatabase;

    private function makeClubContext(): array
    {
        $trainer = User::factory()->create(['rol' => 'entrenador']);
        $club = Club::create([
            'name' => 'Club Calendario Atleta',
            'admin_user_id' => $trainer->id,
        ]);
        $trainer->update(['club_id' => $club->id]);

        $athlete = User::factory()->create([
            'rol' => 'socorrista',
            'club_id' => $club->id,
        ]);

        $predefined = PredefinedExercise::create([
            'name' => 'Ejercicio base calendario',
            'category' => 'resistencia',
            'technical_description' => 'Test calendario atleta',
            'materials' => ['tabla'],
            'is_active' => true,
        ]);

        return compact('trainer', 'club', 'athlete', 'predefined');
    }

    public function test_dashboard_marks_calendar_statuses_for_future_pending_and_completed_workouts(): void
    {
        ['trainer' => $trainer, 'club' => $club, 'athlete' => $athlete, 'predefined' => $predefined] = $this->makeClubContext();

        $pastPending = Workout::create([
            'creator_user_id' => $trainer->id,
            'club_id' => $club->id,
            'title' => 'Pasado pendiente',
            'workout_date' => Carbon::yesterday()->format('Y-m-d'),
            'target_scope' => 'club',
            'is_template' => false,
        ]);

        $pastCompleted = Workout::create([
            'creator_user_id' => $trainer->id,
            'club_id' => $club->id,
            'title' => 'Pasado completado',
            'workout_date' => Carbon::yesterday()->subDay()->format('Y-m-d'),
            'target_scope' => 'club',
            'is_template' => false,
        ]);

        $futureWorkout = Workout::create([
            'creator_user_id' => $trainer->id,
            'club_id' => $club->id,
            'title' => 'Futuro planificado',
            'workout_date' => Carbon::tomorrow()->format('Y-m-d'),
            'target_scope' => 'club',
            'is_template' => false,
        ]);

        foreach ([$pastPending, $pastCompleted, $futureWorkout] as $workout) {
            $workout->exercises()->create([
                'predefined_exercise_id' => $predefined->id,
                'sort_order' => 0,
                'sets' => 3,
                'meters' => 100,
                'rest_seconds' => 45,
            ]);
        }

        DB::table('workout_completions')->insert([
            'workout_id' => $pastCompleted->id,
            'user_id' => $athlete->id,
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($athlete)->get(route('dashboard'));
        $response->assertOk();

        $entrenamientos = collect($response->original?->getData()['page']['props']['entrenamientos'] ?? []);

        $this->assertSame('pending', $entrenamientos->firstWhere('id', $pastPending->id)['completion_status']);
        $this->assertSame('completed', $entrenamientos->firstWhere('id', $pastCompleted->id)['completion_status']);
        $this->assertSame('future', $entrenamientos->firstWhere('id', $futureWorkout->id)['completion_status']);
    }

    public function test_athlete_can_mark_past_workout_as_completed_from_dashboard(): void
    {
        ['trainer' => $trainer, 'club' => $club, 'athlete' => $athlete, 'predefined' => $predefined] = $this->makeClubContext();

        $workout = Workout::create([
            'creator_user_id' => $trainer->id,
            'club_id' => $club->id,
            'title' => 'Sesion para completar',
            'workout_date' => Carbon::yesterday()->format('Y-m-d'),
            'target_scope' => 'club',
            'is_template' => false,
        ]);

        $workout->exercises()->create([
            'predefined_exercise_id' => $predefined->id,
            'sort_order' => 0,
            'sets' => 3,
            'meters' => 75,
            'rest_seconds' => 30,
        ]);

        $this->actingAs($athlete)
            ->patch(route('workouts.complete', $workout))
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('workout_completions', [
            'workout_id' => $workout->id,
            'user_id' => $athlete->id,
        ]);
    }
}

