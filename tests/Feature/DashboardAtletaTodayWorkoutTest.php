<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\PredefinedExercise;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardAtletaTodayWorkoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_includes_today_workout_when_assigned(): void
    {
        $trainer = User::factory()->create(['rol' => 'entrenador']);
        $club = Club::create([
            'name' => 'Club Dashboard Hoy',
            'admin_user_id' => $trainer->id,
        ]);
        $trainer->update(['club_id' => $club->id]);

        $athlete = User::factory()->create([
            'rol' => 'socorrista',
            'club_id' => $club->id,
        ]);

        $predefined = PredefinedExercise::create([
            'name' => 'Bloque hoy',
            'category' => 'resistencia',
            'technical_description' => 'Sesion del dia actual.',
            'materials' => ['aletas'],
            'is_active' => true,
        ]);

        $workout = Workout::create([
            'creator_user_id' => $trainer->id,
            'club_id' => $club->id,
            'title' => 'Entreno de hoy',
            'workout_date' => Carbon::today()->format('Y-m-d'),
            'target_scope' => 'club',
            'is_template' => false,
        ]);

        $workout->exercises()->create([
            'predefined_exercise_id' => $predefined->id,
            'sort_order' => 0,
            'sets' => 3,
            'meters' => 100,
            'rest_seconds' => 45,
        ]);

        $response = $this->actingAs($athlete)->get(route('dashboard'));
        $response->assertOk();

        $props = $response->original?->getData()['page']['props'] ?? [];

        $this->assertArrayHasKey('entrenamientoHoy', $props);
        $this->assertNotNull($props['entrenamientoHoy']);
        $this->assertSame($workout->id, $props['entrenamientoHoy']['id']);
        $this->assertSame(Carbon::today()->format('Y-m-d'), $props['entrenamientoHoy']['workout_date']);
        $this->assertSame('Entreno de hoy', $props['entrenamientoHoy']['title']);
    }

    public function test_dashboard_returns_rest_day_when_only_future_workouts_exist(): void
    {
        $trainer = User::factory()->create(['rol' => 'entrenador']);
        $club = Club::create([
            'name' => 'Club Dashboard Descanso',
            'admin_user_id' => $trainer->id,
        ]);
        $trainer->update(['club_id' => $club->id]);

        $athlete = User::factory()->create([
            'rol' => 'socorrista',
            'club_id' => $club->id,
        ]);

        Workout::create([
            'creator_user_id' => $trainer->id,
            'club_id' => $club->id,
            'title' => 'Entreno de manana',
            'workout_date' => Carbon::tomorrow()->format('Y-m-d'),
            'target_scope' => 'club',
            'is_template' => false,
        ]);

        $response = $this->actingAs($athlete)->get(route('dashboard'));
        $response->assertOk();

        $props = $response->original?->getData()['page']['props'] ?? [];

        $this->assertArrayHasKey('entrenamientoHoy', $props);
        $this->assertNull($props['entrenamientoHoy']);
    }
}

