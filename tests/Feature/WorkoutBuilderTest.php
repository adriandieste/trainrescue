<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\CustomExercise;
use App\Models\PredefinedExercise;
use App\Models\User;
use App\Models\Workout;
use App\Notifications\WorkoutAsignadoNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class WorkoutBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_trainer_can_create_personal_workout_with_multiple_exercises(): void
    {
        $trainer = User::factory()->create(['rol' => 'entrenador']);

        $predefined = PredefinedExercise::create([
            'name' => 'Arrastre tecnico 25m',
            'category' => 'rescate',
            'technical_description' => 'Remolque con control de via aerea.',
            'materials' => ['maniqui'],
            'is_active' => true,
        ]);

        $custom = CustomExercise::create([
            'user_id' => $trainer->id,
            'name' => 'Bloque apnea progresiva',
            'description' => 'Trabajo tecnico de apnea y recuperacion activa.',
            'materials' => 'aletas',
        ]);

        $response = $this
            ->actingAs($trainer)
            ->post(route('workouts.store'), [
                'title' => 'Entrenamiento de resistencia',
                'workout_date' => '2026-05-10',
                'target_scope' => 'personal',
                'exercises' => [
                    [
                        'source' => 'predefined',
                        'exercise_id' => $predefined->id,
                        'sets' => 4,
                        'meters' => null,
                        'rest_seconds' => 30,
                    ],
                    [
                        'source' => 'custom',
                        'exercise_id' => $custom->id,
                        'sets' => 3,
                        'meters' => 100,
                        'rest_seconds' => 45,
                    ],
                ],
            ]);

        $response
            ->assertRedirect(route('exercises.library'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('workouts', [
            'creator_user_id' => $trainer->id,
            'title' => 'Entrenamiento de resistencia',
            'workout_date' => '2026-05-10',
            'target_scope' => 'personal',
            'club_id' => null,
        ]);

        $this->assertDatabaseCount('workout_exercises', 2);
    }

    public function test_trainer_can_create_club_workout_when_member_of_club(): void
    {
        $trainer = User::factory()->create(['rol' => 'entrenador']);
        $club = Club::create([
            'name' => 'Club Rescate Norte',
            'description' => 'Club de pruebas',
            'admin_user_id' => $trainer->id,
        ]);

        $trainer->update(['club_id' => $club->id]);

        $predefined = PredefinedExercise::create([
            'name' => 'Remolque largo',
            'category' => 'rescate',
            'technical_description' => 'Control tecnico en distancia larga.',
            'materials' => ['maniqui'],
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($trainer)
            ->post(route('workouts.store'), [
                'title' => 'Sesion club',
                'workout_date' => '2026-05-11',
                'target_scope' => 'club',
                'exercises' => [[
                    'source' => 'predefined',
                    'exercise_id' => $predefined->id,
                    'sets' => 2,
                    'meters' => null,
                    'rest_seconds' => 60,
                ]],
            ]);

        $response->assertRedirect(route('exercises.library'));

        $this->assertDatabaseHas('workouts', [
            'creator_user_id' => $trainer->id,
            'club_id' => $club->id,
            'target_scope' => 'club',
            'title' => 'Sesion club',
        ]);
    }

    public function test_workout_requires_at_least_one_exercise(): void
    {
        $trainer = User::factory()->create(['rol' => 'entrenador']);

        $response = $this
            ->actingAs($trainer)
            ->from(route('exercises.library'))
            ->post(route('workouts.store'), [
                'title' => 'Sin ejercicios',
                'workout_date' => '2026-05-12',
                'target_scope' => 'personal',
                'exercises' => [],
            ]);

        $response
            ->assertRedirect(route('exercises.library'))
            ->assertSessionHasErrors(['exercises']);
    }

    public function test_trainer_can_save_template_without_workout_date(): void
    {
        $trainer = User::factory()->create(['rol' => 'entrenador']);

        $predefined = PredefinedExercise::create([
            'name' => 'Plantilla 50m tecnica',
            'category' => 'tecnica',
            'technical_description' => 'Bloque base reutilizable.',
            'materials' => ['tabla'],
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($trainer)
            ->post(route('workouts.store'), [
                'title' => 'Plantilla de velocidad',
                'is_template' => true,
                'target_scope' => 'personal',
                'exercises' => [[
                    'source' => 'predefined',
                    'exercise_id' => $predefined->id,
                    'sets' => 4,
                    'meters' => 50,
                    'rest_seconds' => 30,
                ]],
            ]);

        $response
            ->assertRedirect(route('exercises.library'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('workouts', [
            'creator_user_id' => $trainer->id,
            'title' => 'Plantilla de velocidad',
            'is_template' => true,
            'workout_date' => null,
        ]);
    }

    public function test_non_template_workout_requires_workout_date(): void
    {
        $trainer = User::factory()->create(['rol' => 'entrenador']);

        $predefined = PredefinedExercise::create([
            'name' => 'Resistencia controlada',
            'category' => 'resistencia',
            'technical_description' => 'Bloque de carga continua.',
            'materials' => ['aletas'],
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($trainer)
            ->from(route('exercises.library'))
            ->post(route('workouts.store'), [
                'title' => 'Sesion sin fecha',
                'is_template' => false,
                'target_scope' => 'personal',
                'exercises' => [[
                    'source' => 'predefined',
                    'exercise_id' => $predefined->id,
                    'sets' => 3,
                    'meters' => 100,
                    'rest_seconds' => 40,
                ]],
            ]);

        $response
            ->assertRedirect(route('exercises.library'))
            ->assertSessionHasErrors(['workout_date']);
    }

    public function test_template_visibility_defaults_to_private_when_not_sent(): void
    {
        $trainer = User::factory()->create(['rol' => 'entrenador']);

        $predefined = PredefinedExercise::create([
            'name' => 'Plantilla privada por defecto',
            'category' => 'tecnica',
            'technical_description' => 'Base tecnica.',
            'materials' => ['tabla'],
            'is_active' => true,
        ]);

        $this
            ->actingAs($trainer)
            ->post(route('workouts.store'), [
                'title' => 'Plantilla sin visibilidad explicita',
                'is_template' => true,
                'target_scope' => 'personal',
                'exercises' => [[
                    'source' => 'predefined',
                    'exercise_id' => $predefined->id,
                    'sets' => 3,
                    'meters' => null,
                    'rest_seconds' => 30,
                ]],
            ])
            ->assertRedirect(route('exercises.library'));

        $this->assertDatabaseHas('workouts', [
            'creator_user_id' => $trainer->id,
            'title' => 'Plantilla sin visibilidad explicita',
            'is_template' => true,
            'is_public' => false,
        ]);
    }

    public function test_other_trainer_can_duplicate_public_template_but_cannot_update_it(): void
    {
        $owner = User::factory()->create(['rol' => 'entrenador']);
        $intruder = User::factory()->create(['rol' => 'entrenador']);

        $predefined = PredefinedExercise::create([
            'name' => 'Plantilla comunitaria',
            'category' => 'resistencia',
            'technical_description' => 'Compartida para entrenadores.',
            'materials' => ['aletas'],
            'is_active' => true,
        ]);

        $template = Workout::create([
            'creator_user_id' => $owner->id,
            'club_id' => null,
            'title' => 'Plantilla publica editable solo por autor',
            'workout_date' => null,
            'target_scope' => 'personal',
            'is_template' => true,
            'is_public' => true,
        ]);

        $template->exercises()->create([
            'predefined_exercise_id' => $predefined->id,
            'sort_order' => 0,
            'sets' => 3,
            'reps' => null,
            'meters' => 50,
            'rest_seconds' => 40,
        ]);

        $this
            ->actingAs($intruder)
            ->post(route('workouts.duplicate', $template))
            ->assertRedirect();

        $this->assertDatabaseHas('workouts', [
            'creator_user_id' => $intruder->id,
            'title' => 'Plantilla publica editable solo por autor',
            'is_template' => true,
            'is_public' => false,
        ]);

        $this
            ->actingAs($intruder)
            ->patch(route('workouts.update', $template), [
                'title' => 'Intento edicion no autorizado',
                'is_template' => true,
                'is_public' => true,
                'target_scope' => 'personal',
                'exercises' => [[
                    'source' => 'predefined',
                    'exercise_id' => $predefined->id,
                    'sets' => 4,
                    'meters' => 75,
                    'rest_seconds' => 30,
                ]],
            ])
            ->assertForbidden();
    }

    public function test_other_trainer_cannot_delete_public_template_they_do_not_own(): void
    {
        $owner = User::factory()->create(['rol' => 'entrenador']);
        $intruder = User::factory()->create(['rol' => 'entrenador']);

        $template = Workout::create([
            'creator_user_id' => $owner->id,
            'club_id' => null,
            'title' => 'Plantilla publica protegida',
            'workout_date' => null,
            'target_scope' => 'personal',
            'is_template' => true,
            'is_public' => true,
        ]);

        $this
            ->actingAs($intruder)
            ->delete(route('workouts.destroy', $template))
            ->assertForbidden();

        $this->assertDatabaseHas('workouts', [
            'id' => $template->id,
            'title' => 'Plantilla publica protegida',
        ]);
    }

    public function test_trainer_cannot_create_club_workout_without_club(): void
    {
        $trainer = User::factory()->create(['rol' => 'entrenador', 'club_id' => null]);

        $predefined = PredefinedExercise::create([
            'name' => 'Resistencia 200m',
            'category' => 'resistencia',
            'technical_description' => 'Trabajo en zonas aeróbicas.',
            'materials' => ['aletas'],
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($trainer)
            ->from(route('exercises.library'))
            ->post(route('workouts.store'), [
                'title' => 'Intento club',
                'workout_date' => '2026-05-13',
                'target_scope' => 'club',
                'exercises' => [[
                    'source' => 'predefined',
                    'exercise_id' => $predefined->id,
                    'sets' => 3,
                    'meters' => null,
                    'rest_seconds' => 30,
                ]],
            ]);

        $response
            ->assertRedirect(route('exercises.library'))
            ->assertSessionHasErrors(['target_scope']);
    }

    public function test_trainer_can_update_own_workout_and_replace_lines(): void
    {
        $trainer = User::factory()->create(['rol' => 'entrenador']);

        $predefinedA = PredefinedExercise::create([
            'name' => 'Tecnica base 25m',
            'category' => 'tecnica',
            'technical_description' => 'Bloque tecnico inicial.',
            'materials' => ['tabla'],
            'is_active' => true,
        ]);

        $predefinedB = PredefinedExercise::create([
            'name' => 'Resistencia 100m',
            'category' => 'resistencia',
            'technical_description' => 'Trabajo de ritmo sostenido.',
            'materials' => ['aletas'],
            'is_active' => true,
        ]);

        $workout = Workout::create([
            'creator_user_id' => $trainer->id,
            'club_id' => null,
            'title' => 'Sesion original',
            'workout_date' => '2026-05-14',
            'target_scope' => 'personal',
        ]);

        $workout->exercises()->create([
            'predefined_exercise_id' => $predefinedA->id,
            'sort_order' => 0,
            'sets' => 3,
            'reps' => 3,
            'meters' => null,
            'rest_seconds' => 30,
        ]);

        $response = $this
            ->actingAs($trainer)
            ->patch(route('workouts.update', $workout), [
                'title' => 'Sesion corregida',
                'workout_date' => '2026-05-20',
                'target_scope' => 'personal',
                'exercises' => [[
                    'source' => 'predefined',
                    'exercise_id' => $predefinedB->id,
                    'sets' => 5,
                    'meters' => 200,
                    'rest_seconds' => 50,
                ]],
            ]);

        $response
            ->assertRedirect(route('exercises.library'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('workouts', [
            'id' => $workout->id,
            'title' => 'Sesion corregida',
            'workout_date' => '2026-05-20',
        ]);

        $this->assertDatabaseHas('workout_exercises', [
            'workout_id' => $workout->id,
            'predefined_exercise_id' => $predefinedB->id,
            'sets' => 5,
            'meters' => 200,
        ]);

        $this->assertDatabaseMissing('workout_exercises', [
            'workout_id' => $workout->id,
            'predefined_exercise_id' => $predefinedA->id,
        ]);
    }

    public function test_club_admin_can_update_workout_created_by_another_trainer(): void
    {
        $admin = User::factory()->create(['rol' => 'entrenador']);
        $club = Club::create([
            'name' => 'Club Centro',
            'description' => 'Club de pruebas',
            'admin_user_id' => $admin->id,
        ]);

        $creator = User::factory()->create([
            'rol' => 'entrenador',
            'club_id' => $club->id,
        ]);

        $admin->update(['club_id' => $club->id]);

        $predefined = PredefinedExercise::create([
            'name' => 'Bloque club',
            'category' => 'rescate',
            'technical_description' => 'Trabajo para equipo.',
            'materials' => ['maniqui'],
            'is_active' => true,
        ]);

        $workout = Workout::create([
            'creator_user_id' => $creator->id,
            'club_id' => $club->id,
            'title' => 'Sesion club original',
            'workout_date' => '2026-05-15',
            'target_scope' => 'club',
        ]);

        $response = $this
            ->actingAs($admin)
            ->patch(route('workouts.update', $workout), [
                'title' => 'Sesion club ajustada',
                'workout_date' => '2026-05-21',
                'target_scope' => 'club',
                'exercises' => [[
                    'source' => 'predefined',
                    'exercise_id' => $predefined->id,
                    'sets' => 4,
                    'meters' => null,
                    'rest_seconds' => 45,
                ]],
            ]);

        $response->assertRedirect(route('exercises.library'));

        $this->assertDatabaseHas('workouts', [
            'id' => $workout->id,
            'title' => 'Sesion club ajustada',
            'target_scope' => 'club',
            'club_id' => $club->id,
        ]);
    }

    public function test_trainer_can_duplicate_workout_with_deep_copy_and_edit_it_independently(): void
    {
        $trainer = User::factory()->create(['rol' => 'entrenador']);

        $predefined = PredefinedExercise::create([
            'name' => 'Arrastre tecnico',
            'category' => 'rescate',
            'technical_description' => 'Bloque tecnico de arrastre.',
            'materials' => ['maniqui'],
            'is_active' => true,
        ]);

        $custom = CustomExercise::create([
            'user_id' => $trainer->id,
            'name' => 'Apnea controlada',
            'description' => 'Trabajo de apnea por tramos.',
            'materials' => 'tabla',
        ]);

        $original = Workout::create([
            'creator_user_id' => $trainer->id,
            'club_id' => null,
            'title' => 'Sesion base',
            'workout_date' => '2026-05-18',
            'target_scope' => 'personal',
            'is_template' => false,
        ]);

        $original->exercises()->create([
            'predefined_exercise_id' => $predefined->id,
            'custom_exercise_id' => null,
            'sort_order' => 0,
            'sets' => 4,
            'reps' => null,
            'meters' => 50,
            'rest_seconds' => 30,
        ]);

        $original->exercises()->create([
            'predefined_exercise_id' => null,
            'custom_exercise_id' => $custom->id,
            'sort_order' => 1,
            'sets' => 3,
            'reps' => null,
            'meters' => 75,
            'rest_seconds' => 45,
        ]);

        $response = $this
            ->actingAs($trainer)
            ->post(route('workouts.duplicate', $original));

        $duplicate = Workout::query()
            ->where('creator_user_id', $trainer->id)
            ->whereKeyNot($original->id)
            ->first();

        $this->assertNotNull($duplicate);

        $response
            ->assertRedirect(route('exercises.library', ['edit_workout_id' => $duplicate->id]))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('workouts', [
            'id' => $duplicate->id,
            'title' => $original->title,
            'workout_date' => '2026-05-18',
            'target_scope' => $original->target_scope,
            'is_template' => false,
        ]);

        $originalLines = $original->exercises()->orderBy('sort_order')->get();
        $duplicateLines = $duplicate->exercises()->orderBy('sort_order')->get();

        $this->assertCount(2, $originalLines);
        $this->assertCount(2, $duplicateLines);

        $this->assertSame($originalLines[0]->predefined_exercise_id, $duplicateLines[0]->predefined_exercise_id);
        $this->assertSame($originalLines[0]->sets, $duplicateLines[0]->sets);
        $this->assertSame($originalLines[0]->meters, $duplicateLines[0]->meters);
        $this->assertSame($originalLines[0]->rest_seconds, $duplicateLines[0]->rest_seconds);

        $this->assertSame($originalLines[1]->custom_exercise_id, $duplicateLines[1]->custom_exercise_id);
        $this->assertSame($originalLines[1]->sets, $duplicateLines[1]->sets);

        $this
            ->actingAs($trainer)
            ->patch(route('workouts.update', $duplicate), [
                'title' => 'Sesion duplicada editada',
                'workout_date' => '2026-05-25',
                'target_scope' => 'personal',
                'is_template' => false,
                'exercises' => [[
                    'source' => 'predefined',
                    'exercise_id' => $predefined->id,
                    'sets' => 6,
                    'meters' => 100,
                    'rest_seconds' => 60,
                ]],
            ])
            ->assertRedirect(route('exercises.library'));

        $this->assertDatabaseHas('workouts', [
            'id' => $original->id,
            'title' => 'Sesion base',
            'workout_date' => '2026-05-18',
        ]);

        $this->assertDatabaseHas('workouts', [
            'id' => $duplicate->id,
            'title' => 'Sesion duplicada editada',
            'workout_date' => '2026-05-25',
        ]);

        $this->assertDatabaseCount('workout_exercises', 3);
        $this->assertDatabaseHas('workout_exercises', [
            'workout_id' => $original->id,
            'sort_order' => 1,
            'custom_exercise_id' => $custom->id,
            'sets' => 3,
        ]);
    }

    public function test_trainer_cannot_duplicate_workout_without_permission(): void
    {
        $owner = User::factory()->create(['rol' => 'entrenador']);
        $intruder = User::factory()->create(['rol' => 'entrenador']);

        $workout = Workout::create([
            'creator_user_id' => $owner->id,
            'club_id' => null,
            'title' => 'Sesion privada',
            'workout_date' => '2026-05-19',
            'target_scope' => 'personal',
            'is_template' => false,
        ]);

        $response = $this
            ->actingAs($intruder)
            ->post(route('workouts.duplicate', $workout));

        $response->assertForbidden();

        $this->assertDatabaseCount('workouts', 1);
    }

    public function test_non_owner_non_admin_cannot_update_workout(): void
    {
        $admin = User::factory()->create(['rol' => 'entrenador']);
        $club = Club::create([
            'name' => 'Club Sur',
            'description' => 'Club de pruebas',
            'admin_user_id' => $admin->id,
        ]);

        $creator = User::factory()->create([
            'rol' => 'entrenador',
            'club_id' => $club->id,
        ]);

        $intruder = User::factory()->create([
            'rol' => 'entrenador',
            'club_id' => $club->id,
        ]);

        $predefined = PredefinedExercise::create([
            'name' => 'Linea control',
            'category' => 'resistencia',
            'technical_description' => 'Control ritmo.',
            'materials' => ['aletas'],
            'is_active' => true,
        ]);

        $workout = Workout::create([
            'creator_user_id' => $creator->id,
            'club_id' => $club->id,
            'title' => 'Sesion protegida',
            'workout_date' => '2026-05-16',
            'target_scope' => 'club',
        ]);

        $response = $this
            ->actingAs($intruder)
            ->patch(route('workouts.update', $workout), [
                'title' => 'Intento no autorizado',
                'workout_date' => '2026-05-22',
                'target_scope' => 'club',
                'exercises' => [[
                    'source' => 'predefined',
                    'exercise_id' => $predefined->id,
                    'sets' => 3,
                    'meters' => null,
                    'rest_seconds' => 40,
                ]],
            ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('workouts', [
            'id' => $workout->id,
            'title' => 'Sesion protegida',
        ]);
    }

    public function test_creating_club_workout_notifies_all_club_members(): void
    {
        Notification::fake();

        $trainer = User::factory()->create(['rol' => 'entrenador']);
        $club = Club::create([
            'name' => 'Club Notificaciones',
            'description' => 'Test',
            'admin_user_id' => $trainer->id,
        ]);
        $trainer->update(['club_id' => $club->id]);

        $socorrista1 = User::factory()->create(['rol' => 'socorrista', 'club_id' => $club->id]);
        $socorrista2 = User::factory()->create(['rol' => 'socorrista', 'club_id' => $club->id]);

        $predefined = PredefinedExercise::create([
            'name' => 'Circuito club',
            'category' => 'tecnica',
            'technical_description' => 'Sesion de club.',
            'materials' => ['tubo'],
            'is_active' => true,
        ]);

        $this
            ->actingAs($trainer)
            ->post(route('workouts.store'), [
                'title' => 'Sesion de club asignada',
                'workout_date' => '2026-05-20',
                'target_scope' => 'club',
                'exercises' => [[
                    'source' => 'predefined',
                    'exercise_id' => $predefined->id,
                    'sets' => 3,
                    'meters' => 50,
                    'rest_seconds' => 30,
                ]],
            ])
            ->assertRedirect(route('exercises.library'))
            ->assertSessionHas('success');

        Notification::assertSentTo($socorrista1, WorkoutAsignadoNotification::class);
        Notification::assertSentTo($socorrista2, WorkoutAsignadoNotification::class);
        Notification::assertNotSentTo($trainer, WorkoutAsignadoNotification::class);
    }

    public function test_creating_personal_workout_does_not_notify(): void
    {
        Notification::fake();

        $trainer = User::factory()->create(['rol' => 'entrenador']);

        $predefined = PredefinedExercise::create([
            'name' => 'Ejercicio personal',
            'category' => 'tecnica',
            'technical_description' => 'Solo personal.',
            'materials' => ['tabla'],
            'is_active' => true,
        ]);

        $this
            ->actingAs($trainer)
            ->post(route('workouts.store'), [
                'title' => 'Sesion personal',
                'workout_date' => '2026-05-21',
                'target_scope' => 'personal',
                'exercises' => [[
                    'source' => 'predefined',
                    'exercise_id' => $predefined->id,
                    'sets' => 3,
                    'meters' => null,
                    'rest_seconds' => null,
                ]],
            ]);

        Notification::assertNothingSent();
    }

    public function test_updating_club_workout_notifies_members_again(): void
    {
        Notification::fake();

        $trainer = User::factory()->create(['rol' => 'entrenador']);
        $club = Club::create([
            'name' => 'Club Update Test',
            'description' => 'Test',
            'admin_user_id' => $trainer->id,
        ]);
        $trainer->update(['club_id' => $club->id]);

        $socorrista = User::factory()->create(['rol' => 'socorrista', 'club_id' => $club->id]);

        $predefined = PredefinedExercise::create([
            'name' => 'Tecnica update',
            'category' => 'rescate',
            'technical_description' => 'Update test.',
            'materials' => ['maniqui'],
            'is_active' => true,
        ]);

        $workout = Workout::create([
            'creator_user_id' => $trainer->id,
            'club_id' => $club->id,
            'title' => 'Sesion original club',
            'workout_date' => '2026-05-22',
            'target_scope' => 'club',
            'is_template' => false,
        ]);

        $this
            ->actingAs($trainer)
            ->patch(route('workouts.update', $workout), [
                'title' => 'Sesion club actualizada',
                'workout_date' => '2026-05-29',
                'target_scope' => 'club',
                'exercises' => [[
                    'source' => 'predefined',
                    'exercise_id' => $predefined->id,
                    'sets' => 4,
                    'meters' => 75,
                    'rest_seconds' => 45,
                ]],
            ])
            ->assertRedirect(route('exercises.library'));

        Notification::assertSentTo($socorrista, WorkoutAsignadoNotification::class);
        Notification::assertNotSentTo($trainer, WorkoutAsignadoNotification::class);
    }

    // ─── HU-39: Asignación a grupos específicos del club ─────────────────────

    private function makeClubWithTrainerAndMembers(): array
    {
        $trainer = User::factory()->create(['rol' => 'entrenador']);
        $club    = Club::create([
            'name'          => 'Club HU-39',
            'admin_user_id' => $trainer->id,
        ]);
        $trainer->update(['club_id' => $club->id]);

        $member1 = User::factory()->create(['rol' => 'socorrista', 'club_id' => $club->id]);
        $member2 = User::factory()->create(['rol' => 'socorrista', 'club_id' => $club->id]);
        $member3 = User::factory()->create(['rol' => 'socorrista', 'club_id' => $club->id]);

        $predefined = PredefinedExercise::create([
            'name'                  => 'Ejercicio grupo',
            'category'              => 'resistencia',
            'technical_description' => 'Descripcion',
            'materials'             => [],
            'is_active'             => true,
        ]);

        return compact('trainer', 'club', 'member1', 'member2', 'member3', 'predefined');
    }

    public function test_trainer_can_create_grupo_workout_assigned_to_specific_members(): void
    {
        Notification::fake();
        ['trainer' => $trainer, 'member1' => $member1, 'member2' => $member2,
         'member3' => $member3, 'predefined' => $predefined] = $this->makeClubWithTrainerAndMembers();

        $response = $this->actingAs($trainer)->post(route('workouts.store'), [
            'title'             => 'Sesion Grupo A',
            'workout_date'      => '2026-06-15',
            'target_scope'      => 'grupo',
            'assigned_user_ids' => [$member1->id, $member2->id],
            'exercises'         => [[
                'source'      => 'predefined',
                'exercise_id' => $predefined->id,
                'sets'        => 3,
                'meters'      => 100,
                'rest_seconds'=> 30,
            ]],
        ]);

        $response->assertRedirect(route('exercises.library'));
        $flash = $response->baseResponse->getSession()->get('success');
        $this->assertStringContainsString('2 atletas', $flash);

        $workout = Workout::where('title', 'Sesion Grupo A')->first();
        $this->assertNotNull($workout);
        $this->assertSame('grupo', $workout->target_scope);

        $this->assertDatabaseHas('workout_assignments', ['workout_id' => $workout->id, 'user_id' => $member1->id]);
        $this->assertDatabaseHas('workout_assignments', ['workout_id' => $workout->id, 'user_id' => $member2->id]);
        $this->assertDatabaseMissing('workout_assignments', ['workout_id' => $workout->id, 'user_id' => $member3->id]);

        Notification::assertSentTo($member1, WorkoutAsignadoNotification::class);
        Notification::assertSentTo($member2, WorkoutAsignadoNotification::class);
        Notification::assertNotSentTo($member3, WorkoutAsignadoNotification::class);
        Notification::assertNotSentTo($trainer, WorkoutAsignadoNotification::class);
    }

    public function test_grupo_workout_only_visible_to_assigned_members_on_dashboard(): void
    {
        ['trainer' => $trainer, 'member1' => $member1, 'member2' => $member2,
         'member3' => $member3, 'predefined' => $predefined] = $this->makeClubWithTrainerAndMembers();

        // Crear workout de grupo asignado solo a member1
        $this->actingAs($trainer)->post(route('workouts.store'), [
            'title'             => 'Solo para Grupo A',
            'workout_date'      => '2026-06-20',
            'target_scope'      => 'grupo',
            'assigned_user_ids' => [$member1->id],
            'exercises'         => [[
                'source'      => 'predefined',
                'exercise_id' => $predefined->id,
                'sets'        => 4,
            ]],
        ]);

        $workout = Workout::where('title', 'Solo para Grupo A')->first();

        // member1 asignado: lo ve en su dashboard
        $dashboardAssigned = $this->actingAs($member1)->get(route('dashboard'));
        $dashboardAssigned->assertOk();
        $entrenamientos = collect($dashboardAssigned->original?->getData()['page']['props']['entrenamientos'] ?? []);
        $ids = collect($dashboardAssigned->original?->getData()['page']['props']['entrenamientos'] ?? [])
            ->pluck('id')->toArray();
        $this->assertContains($workout->id, $ids, 'El atleta asignado debería ver el workout de grupo.');

        // member3 NO asignado: NO lo ve en su dashboard
        $dashboardNotAssigned = $this->actingAs($member3)->get(route('dashboard'));
        $idsNotAssigned = collect($dashboardNotAssigned->original?->getData()['page']['props']['entrenamientos'] ?? [])
            ->pluck('id')->toArray();
        $this->assertNotContains($workout->id, $idsNotAssigned, 'El atleta no asignado NO debería ver el workout de grupo.');
    }

    public function test_grupo_workout_requires_at_least_one_assigned_user(): void
    {
        ['trainer' => $trainer, 'predefined' => $predefined] = $this->makeClubWithTrainerAndMembers();

        $response = $this->actingAs($trainer)->post(route('workouts.store'), [
            'title'             => 'Grupo sin atletas',
            'workout_date'      => '2026-06-25',
            'target_scope'      => 'grupo',
            'assigned_user_ids' => [],
            'exercises'         => [[
                'source'      => 'predefined',
                'exercise_id' => $predefined->id,
                'sets'        => 2,
            ]],
        ]);

        $response->assertSessionHasErrors('assigned_user_ids');
        $this->assertDatabaseMissing('workouts', ['title' => 'Grupo sin atletas']);
    }

    public function test_grupo_workout_rejects_users_outside_club(): void
    {
        ['trainer' => $trainer, 'predefined' => $predefined] = $this->makeClubWithTrainerAndMembers();

        $outsider = User::factory()->create(['rol' => 'socorrista', 'club_id' => null]);

        $response = $this->actingAs($trainer)->post(route('workouts.store'), [
            'title'             => 'Grupo con foraneo',
            'workout_date'      => '2026-06-30',
            'target_scope'      => 'grupo',
            'assigned_user_ids' => [$outsider->id],
            'exercises'         => [[
                'source'      => 'predefined',
                'exercise_id' => $predefined->id,
                'sets'        => 2,
            ]],
        ]);

        $response->assertSessionHasErrors('assigned_user_ids');
        $this->assertDatabaseMissing('workouts', ['title' => 'Grupo con foraneo']);
    }

    public function test_single_member_grupo_workout_shows_singular_in_success_message(): void
    {
        ['trainer' => $trainer, 'member1' => $member1,
         'predefined' => $predefined] = $this->makeClubWithTrainerAndMembers();

        $response = $this->actingAs($trainer)->post(route('workouts.store'), [
            'title'             => 'Sesion Individual',
            'workout_date'      => '2026-07-01',
            'target_scope'      => 'grupo',
            'assigned_user_ids' => [$member1->id],
            'exercises'         => [[
                'source'      => 'predefined',
                'exercise_id' => $predefined->id,
                'sets'        => 3,
            ]],
        ]);

        $flash = $response->baseResponse->getSession()->get('success');
        $this->assertStringContainsString('1 atleta', $flash);
        $this->assertStringNotContainsString('atletas', $flash);
    }

    public function test_exercise_library_includes_calendar_events_for_trainer(): void
    {
        ['trainer' => $trainer, 'member1' => $member1, 'predefined' => $predefined] = $this->makeClubWithTrainerAndMembers();

        $this->actingAs($trainer)->post(route('workouts.store'), [
            'title' => 'Sesion para calendario',
            'workout_date' => '2026-08-01',
            'target_scope' => 'grupo',
            'assigned_user_ids' => [$member1->id],
            'exercises' => [[
                'source' => 'predefined',
                'exercise_id' => $predefined->id,
                'sets' => 3,
            ]],
        ])->assertRedirect(route('exercises.library'));

        $response = $this->actingAs($trainer)->get(route('exercises.library'));
        $response->assertOk();

        $calendarEvents = collect($response->original?->getData()['page']['props']['calendarEvents'] ?? []);

        $event = $calendarEvents->firstWhere('title', 'Sesion para calendario');
        $this->assertNotNull($event);
        $this->assertSame('2026-08-01', $event['workout_date']);
        $this->assertSame('grupo', $event['target_scope']);
        $this->assertContains($member1->id, $event['assigned_user_ids']);
    }

    public function test_calendar_page_includes_calendar_events_for_trainer(): void
    {
        ['trainer' => $trainer, 'member1' => $member1, 'predefined' => $predefined] = $this->makeClubWithTrainerAndMembers();

        $this->actingAs($trainer)->post(route('workouts.store'), [
            'title' => 'Sesion vista calendario',
            'workout_date' => '2026-08-02',
            'target_scope' => 'grupo',
            'assigned_user_ids' => [$member1->id],
            'exercises' => [[
                'source' => 'predefined',
                'exercise_id' => $predefined->id,
                'sets' => 3,
            ]],
        ])->assertRedirect(route('exercises.library'));

        $response = $this->actingAs($trainer)->get(route('calendar.index'));
        $response->assertOk();

        $calendarEvents = collect($response->original?->getData()['page']['props']['calendarEvents'] ?? []);
        $event = $calendarEvents->firstWhere('title', 'Sesion vista calendario');

        $this->assertNotNull($event);
        $this->assertSame('2026-08-02', $event['workout_date']);
        $this->assertSame('grupo', $event['target_scope']);
        $this->assertContains($member1->id, $event['assigned_user_ids']);
    }

    public function test_updating_grupo_workout_syncs_assignments_and_notifies_new_members(): void
    {
        Notification::fake();
        ['trainer' => $trainer, 'member1' => $member1, 'member2' => $member2,
         'member3' => $member3, 'predefined' => $predefined] = $this->makeClubWithTrainerAndMembers();

        // Crear workout de grupo con member1 y member2
        $this->actingAs($trainer)->post(route('workouts.store'), [
            'title'             => 'Sesion grupal',
            'workout_date'      => '2026-07-10',
            'target_scope'      => 'grupo',
            'assigned_user_ids' => [$member1->id, $member2->id],
            'exercises'         => [['source' => 'predefined', 'exercise_id' => $predefined->id, 'sets' => 3]],
        ]);

        $workout = Workout::where('title', 'Sesion grupal')->first();
        Notification::fake();

        // Actualizar a solo member3
        $this->actingAs($trainer)->patch(route('workouts.update', $workout), [
            'title'             => 'Sesion grupal v2',
            'workout_date'      => '2026-07-10',
            'target_scope'      => 'grupo',
            'assigned_user_ids' => [$member3->id],
            'exercises'         => [['source' => 'predefined', 'exercise_id' => $predefined->id, 'sets' => 5]],
        ])->assertRedirect(route('exercises.library'));

        $this->assertDatabaseMissing('workout_assignments', ['workout_id' => $workout->id, 'user_id' => $member1->id]);
        $this->assertDatabaseMissing('workout_assignments', ['workout_id' => $workout->id, 'user_id' => $member2->id]);
        $this->assertDatabaseHas('workout_assignments', ['workout_id' => $workout->id, 'user_id' => $member3->id]);

        Notification::assertSentTo($member3, WorkoutAsignadoNotification::class);
        Notification::assertNotSentTo($member1, WorkoutAsignadoNotification::class);
        Notification::assertNotSentTo($member2, WorkoutAsignadoNotification::class);
    }
}


