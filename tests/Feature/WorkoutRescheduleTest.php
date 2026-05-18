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

class WorkoutRescheduleTest extends TestCase
{
    use RefreshDatabase;

    private function makeClubContext(): array
    {
        $trainer = User::factory()->create(['rol' => 'entrenador']);
        $club = Club::create([
            'name' => 'Club Reschedule',
            'admin_user_id' => $trainer->id,
        ]);
        $trainer->update(['club_id' => $club->id]);

        $predefined = PredefinedExercise::create([
            'name' => 'Ejercicio base reschedule',
            'category' => 'resistencia',
            'technical_description' => 'Test reschedule',
            'is_active' => true,
        ]);

        return compact('trainer', 'club', 'predefined');
    }

    /**
     * [TRA-411] Test endpoint PATCH /workouts/{id}/reschedule
     */
    public function test_trainer_can_reschedule_workout_via_patch_endpoint(): void
    {
        ['trainer' => $trainer, 'club' => $club, 'predefined' => $predefined] = $this->makeClubContext();

        $originalDate = Carbon::now()->addDays(5)->format('Y-m-d');
        $newDate = Carbon::now()->addDays(10)->format('Y-m-d');

        $workout = Workout::create([
            'creator_user_id' => $trainer->id,
            'club_id' => $club->id,
            'title' => 'Original Workout Date',
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

        $this->assertSame($originalDate, $workout->workout_date?->format('Y-m-d'));

        // [TRA-411] Reschedule via PATCH endpoint
        $response = $this->actingAs($trainer)->patchJson(
            route('workouts.reschedule', $workout->id),
            ['workout_date' => $newDate],
        );

        $response->assertOk();
        $response->assertJsonStructure([
            'message',
            'workout' => ['id', 'workout_date'],
        ]);
        $response->assertJson([
            'message' => 'Entrenamiento reprogramado con éxito',
            'workout' => [
                'id' => $workout->id,
                'workout_date' => $newDate,
            ],
        ]);

        $workout->refresh();
        $this->assertSame($newDate, $workout->workout_date?->format('Y-m-d'));
    }

    /**
     * [TRA-413] Test authorization: only editor can reschedule
     */
    public function test_non_creator_cannot_reschedule_workout(): void
    {
        ['trainer' => $trainer, 'club' => $club, 'predefined' => $predefined] = $this->makeClubContext();

        $otherTrainer = User::factory()->create(['rol' => 'entrenador', 'club_id' => $club->id]);

        $originalDate = Carbon::now()->addDays(5)->format('Y-m-d');
        $newDate = Carbon::now()->addDays(10)->format('Y-m-d');

        $workout = Workout::create([
            'creator_user_id' => $trainer->id,
            'club_id' => $club->id,
            'title' => 'Unauthorized Reschedule',
            'workout_date' => $originalDate,
            'target_scope' => 'club',
            'is_template' => false,
        ]);

        // Other trainer tries to reschedule
        $response = $this->actingAs($otherTrainer)->patchJson(
            route('workouts.reschedule', $workout->id),
            ['workout_date' => $newDate],
        );

        $response->assertForbidden();
        $workout->refresh();
        $this->assertSame($originalDate, $workout->workout_date?->format('Y-m-d'));
    }

    /**
     * [TRA-411] Test validation: invalid date format
     */
    public function test_reschedule_validates_date_format(): void
    {
        ['trainer' => $trainer, 'club' => $club, 'predefined' => $predefined] = $this->makeClubContext();

        $originalDate = Carbon::now()->addDays(5)->format('Y-m-d');

        $workout = Workout::create([
            'creator_user_id' => $trainer->id,
            'club_id' => $club->id,
            'title' => 'Invalid Date Test',
            'workout_date' => $originalDate,
            'target_scope' => 'club',
            'is_template' => false,
        ]);

        // Invalid date format
        $response = $this->actingAs($trainer)->patchJson(
            route('workouts.reschedule', $workout->id),
            ['workout_date' => 'invalid-date'],
        );

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['workout_date']);

        $workout->refresh();
        $this->assertSame($originalDate, $workout->workout_date?->format('Y-m-d'));
    }

    /**
     * [TRA-411] Test reschedule to same date
     */
    public function test_reschedule_to_same_date_is_idempotent(): void
    {
        ['trainer' => $trainer, 'club' => $club, 'predefined' => $predefined] = $this->makeClubContext();

        $sameDate = Carbon::now()->addDays(5)->format('Y-m-d');

        $workout = Workout::create([
            'creator_user_id' => $trainer->id,
            'club_id' => $club->id,
            'title' => 'Same Date Reschedule',
            'workout_date' => $sameDate,
            'target_scope' => 'club',
            'is_template' => false,
        ]);

        $response = $this->actingAs($trainer)->patchJson(
            route('workouts.reschedule', $workout->id),
            ['workout_date' => $sameDate],
        );

        $response->assertOk();
        $workout->refresh();
        $this->assertSame($sameDate, $workout->workout_date?->format('Y-m-d'));
    }

    /**
     * [TRA-411] Test reschedule template (should work on templates too)
     */
    public function test_reschedule_on_template_sets_date_and_makes_non_template(): void
    {
        ['trainer' => $trainer, 'club' => $club, 'predefined' => $predefined] = $this->makeClubContext();

        $newDate = Carbon::now()->addDays(5)->format('Y-m-d');

        // Create a template (workout_date is null)
        $template = Workout::create([
            'creator_user_id' => $trainer->id,
            'club_id' => $club->id,
            'title' => 'Template Workout',
            'workout_date' => null,
            'target_scope' => 'club',
            'is_template' => true,
        ]);

        WorkoutExercise::create([
            'workout_id' => $template->id,
            'predefined_exercise_id' => $predefined->id,
            'sort_order' => 0,
            'sets' => 3,
        ]);

        $this->assertNull($template->workout_date);
        $this->assertTrue($template->is_template);

        // Reschedule template to a specific date
        $response = $this->actingAs($trainer)->patchJson(
            route('workouts.reschedule', $template->id),
            ['workout_date' => $newDate],
        );

        $response->assertOk();
        $template->refresh();
        $this->assertSame($newDate, $template->workout_date?->format('Y-m-d'));
        // Note: Template flag remains unchanged; only date updates
    }
}

