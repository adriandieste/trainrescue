<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\PredefinedExercise;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutExercise;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DragDropIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * [TRA-410/TRA-412] Test: Only trainers can reschedule, athletes cannot
     * This verifies the route protection via middleware
     */
    public function test_athlete_cannot_reschedule_workout(): void
    {
        $trainer = User::factory()->create(['rol' => 'entrenador']);
        $athlete = User::factory()->create(['rol' => 'socorrista']);

        $club = Club::create([
            'name' => 'Club Drag Drop',
            'admin_user_id' => $trainer->id,
        ]);

        $trainer->update(['club_id' => $club->id]);
        $athlete->update(['club_id' => $club->id]);

        $predefined = PredefinedExercise::create([
            'name' => 'Ejercicio drag drop',
            'category' => 'resistencia',
            'technical_description' => 'Test drag drop',
            'is_active' => true,
        ]);

        $originalDate = Carbon::now()->addDays(5)->format('Y-m-d');
        $newDate = Carbon::now()->addDays(10)->format('Y-m-d');

        $workout = Workout::create([
            'creator_user_id' => $trainer->id,
            'club_id' => $club->id,
            'title' => 'Club Workout',
            'workout_date' => $originalDate,
            'target_scope' => 'club',
            'is_template' => false,
        ]);

        WorkoutExercise::create([
            'workout_id' => $workout->id,
            'predefined_exercise_id' => $predefined->id,
            'sort_order' => 0,
            'sets' => 3,
        ]);

        // Athlete tries to reschedule
        $response = $this->actingAs($athlete)->patchJson(
            route('workouts.reschedule', $workout->id),
            ['workout_date' => $newDate],
        );

        $response->assertForbidden();
        $workout->refresh();
        $this->assertSame($originalDate, $workout->workout_date?->format('Y-m-d'));
    }

    /**
     * [TRA-410/TRA-412] Test: Guest cannot reschedule
     */
    public function test_guest_cannot_reschedule_workout(): void
    {
        $trainer = User::factory()->create(['rol' => 'entrenador']);
        $club = Club::create([
            'name' => 'Club Guests',
            'admin_user_id' => $trainer->id,
        ]);
        $trainer->update(['club_id' => $club->id]);

        $predefined = PredefinedExercise::create([
            'name' => 'Guest test',
            'category' => 'resistencia',
            'technical_description' => 'Test',
            'is_active' => true,
        ]);

        $originalDate = Carbon::now()->addDays(5)->format('Y-m-d');
        $newDate = Carbon::now()->addDays(10)->format('Y-m-d');

        $workout = Workout::create([
            'creator_user_id' => $trainer->id,
            'club_id' => $club->id,
            'title' => 'Guest Test Workout',
            'workout_date' => $originalDate,
            'target_scope' => 'club',
            'is_template' => false,
        ]);

        WorkoutExercise::create([
            'workout_id' => $workout->id,
            'predefined_exercise_id' => $predefined->id,
            'sort_order' => 0,
            'sets' => 3,
        ]);

        // Guest tries to reschedule (JSON request gets 401, not 302)
        $response = $this->patchJson(
            route('workouts.reschedule', $workout->id),
            ['workout_date' => $newDate],
        );

        $response->assertUnauthorized();
        $workout->refresh();
        $this->assertSame($originalDate, $workout->workout_date?->format('Y-m-d'));
    }

    /**
     * [TRA-412] Test: Multiple reschedules in sequence work correctly
     */
    public function test_multiple_reschedules_in_sequence(): void
    {
        $trainer = User::factory()->create(['rol' => 'entrenador']);
        $club = Club::create([
            'name' => 'Club Sequential',
            'admin_user_id' => $trainer->id,
        ]);
        $trainer->update(['club_id' => $club->id]);

        $predefined = PredefinedExercise::create([
            'name' => 'Sequential test',
            'category' => 'resistencia',
            'technical_description' => 'Test',
            'is_active' => true,
        ]);

        $date1 = Carbon::now()->addDays(1)->format('Y-m-d');
        $date2 = Carbon::now()->addDays(2)->format('Y-m-d');
        $date3 = Carbon::now()->addDays(3)->format('Y-m-d');

        $workout = Workout::create([
            'creator_user_id' => $trainer->id,
            'club_id' => $club->id,
            'title' => 'Sequential Workout',
            'workout_date' => $date1,
            'target_scope' => 'club',
            'is_template' => false,
        ]);

        WorkoutExercise::create([
            'workout_id' => $workout->id,
            'predefined_exercise_id' => $predefined->id,
            'sort_order' => 0,
            'sets' => 3,
        ]);

        // First reschedule
        $response1 = $this->actingAs($trainer)->patchJson(
            route('workouts.reschedule', $workout->id),
            ['workout_date' => $date2],
        );
        $response1->assertOk();
        $workout->refresh();
        $this->assertSame($date2, $workout->workout_date?->format('Y-m-d'));

        // Second reschedule
        $response2 = $this->actingAs($trainer)->patchJson(
            route('workouts.reschedule', $workout->id),
            ['workout_date' => $date3],
        );
        $response2->assertOk();
        $workout->refresh();
        $this->assertSame($date3, $workout->workout_date?->format('Y-m-d'));
    }
}


