<?php

namespace Tests\Feature;

use App\Models\PredefinedExercise;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class ExerciseLibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_trainer_can_view_exercise_library(): void
    {
        $trainer = User::factory()->create(['rol' => 'entrenador']);

        PredefinedExercise::create([
            'name' => 'Arrastre tecnico 25 m',
            'category' => 'rescate',
            'technical_description' => 'Ejercicio de control de arrastre con foco en posicion del cuerpo.',
            'materials' => ['maniqui de rescate', 'cronometro'],
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($trainer)
            ->get(route('exercises.library'));

        $response
            ->assertOk()
            ->assertSee('Arrastre tecnico 25 m')
            ->assertSee('rescate')
            ->assertSee('maniqui de rescate');
    }

    public function test_non_trainer_cannot_view_exercise_library(): void
    {
        $socorrista = User::factory()->create(['rol' => 'socorrista']);

        $response = $this
            ->actingAs($socorrista)
            ->get(route('exercises.library'));

        $response->assertForbidden();
    }

    public function test_guest_is_redirected_when_accessing_exercise_library(): void
    {
        $response = $this->get(route('exercises.library'));

        $response->assertRedirect('/login');
    }

    public function test_library_shows_only_active_exercises(): void
    {
        $trainer = User::factory()->create(['rol' => 'entrenador']);

        PredefinedExercise::create([
            'name' => 'Activo',
            'category' => 'tecnica',
            'technical_description' => 'Visible en biblioteca.',
            'materials' => ['tabla'],
            'is_active' => true,
        ]);

        PredefinedExercise::create([
            'name' => 'Inactivo',
            'category' => 'tecnica',
            'technical_description' => 'No debe mostrarse.',
            'materials' => ['tabla'],
            'is_active' => false,
        ]);

        $response = $this
            ->actingAs($trainer)
            ->get(route('exercises.library'));

        $response
            ->assertOk()
            ->assertSee('Activo')
            ->assertDontSee('Inactivo');
    }

    public function test_trainer_can_create_custom_exercise(): void
    {
        $trainer = User::factory()->create(['rol' => 'entrenador']);

        $response = $this
            ->actingAs($trainer)
            ->post(route('exercises.custom.store'), [
                'name' => 'Rescate combinado entrenador',
                'description' => 'Trabajo tecnico de aproximacion y remolque.',
                'materials' => "maniqui\ntubo de rescate",
                'video_url' => 'https://example.com/video',
            ]);

        $response
            ->assertRedirect(route('exercises.library'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('custom_exercises', [
            'user_id' => $trainer->id,
            'name' => 'Rescate combinado entrenador',
            'description' => 'Trabajo tecnico de aproximacion y remolque.',
            'video_url' => 'https://example.com/video',
        ]);
    }

    public function test_custom_exercise_requires_name_and_description(): void
    {
        $trainer = User::factory()->create(['rol' => 'entrenador']);

        $response = $this
            ->actingAs($trainer)
            ->from(route('exercises.library'))
            ->post(route('exercises.custom.store'), [
                'name' => '',
                'description' => '',
                'materials' => 'tabla',
            ]);

        $response
            ->assertRedirect(route('exercises.library'))
            ->assertSessionHasErrors(['name', 'description']);
    }

    public function test_custom_exercise_is_private_per_trainer(): void
    {
        $trainerA = User::factory()->create(['rol' => 'entrenador']);
        $trainerB = User::factory()->create(['rol' => 'entrenador']);

        $this->actingAs($trainerA)->post(route('exercises.custom.store'), [
            'name' => 'Privado A',
            'description' => 'Solo A',
            'materials' => 'maniqui',
        ]);

        $response = $this
            ->actingAs($trainerB)
            ->get(route('exercises.library'));

        $response
            ->assertOk()
            ->assertDontSee('Privado A');
    }

    public function test_trainer_can_update_own_custom_exercise(): void
    {
        $trainer = User::factory()->create(['rol' => 'entrenador']);

        $this->actingAs($trainer)->post(route('exercises.custom.store'), [
            'name' => 'Ejercicio base',
            'description' => 'Descripcion base',
            'materials' => 'tabla',
        ]);

        $exerciseId = DB::table('custom_exercises')->where('user_id', $trainer->id)->value('id');

        $response = $this
            ->actingAs($trainer)
            ->patch(route('exercises.custom.update', $exerciseId), [
                'name' => 'Ejercicio actualizado',
                'description' => 'Descripcion actualizada',
                'materials' => 'aletas, tubo',
                'video_url' => 'https://example.com/editado',
            ]);

        $response
            ->assertRedirect(route('exercises.library'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('custom_exercises', [
            'id' => $exerciseId,
            'name' => 'Ejercicio actualizado',
            'description' => 'Descripcion actualizada',
            'video_url' => 'https://example.com/editado',
        ]);
    }

    public function test_trainer_cannot_update_another_trainers_exercise(): void
    {
        $owner = User::factory()->create(['rol' => 'entrenador']);
        $intruder = User::factory()->create(['rol' => 'entrenador']);

        $this->actingAs($owner)->post(route('exercises.custom.store'), [
            'name' => 'Privado propietario',
            'description' => 'No editable por terceros',
            'materials' => 'tabla',
        ]);

        $exerciseId = DB::table('custom_exercises')->where('user_id', $owner->id)->value('id');

        $response = $this
            ->actingAs($intruder)
            ->patch(route('exercises.custom.update', $exerciseId), [
                'name' => 'Intento intruso',
                'description' => 'Intento intruso',
                'materials' => 'x',
            ]);

        $response->assertForbidden();
    }

    public function test_trainer_can_soft_delete_own_custom_exercise(): void
    {
        $trainer = User::factory()->create(['rol' => 'entrenador']);

        $this->actingAs($trainer)->post(route('exercises.custom.store'), [
            'name' => 'Borrable',
            'description' => 'Se eliminara',
            'materials' => 'tabla',
        ]);

        $exerciseId = DB::table('custom_exercises')->where('user_id', $trainer->id)->value('id');

        $response = $this
            ->actingAs($trainer)
            ->delete(route('exercises.custom.destroy', $exerciseId));

        $response
            ->assertRedirect(route('exercises.library'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('custom_exercises', [
            'id' => $exerciseId,
        ]);
    }

    public function test_trainer_cannot_delete_another_trainers_exercise(): void
    {
        $owner = User::factory()->create(['rol' => 'entrenador']);
        $intruder = User::factory()->create(['rol' => 'entrenador']);

        $this->actingAs($owner)->post(route('exercises.custom.store'), [
            'name' => 'No borrable por terceros',
            'description' => 'Privado',
            'materials' => 'tabla',
        ]);

        $exerciseId = DB::table('custom_exercises')->where('user_id', $owner->id)->value('id');

        $response = $this
            ->actingAs($intruder)
            ->delete(route('exercises.custom.destroy', $exerciseId));

        $response->assertForbidden();
    }

    public function test_delete_warns_when_custom_exercise_is_used_in_workout(): void
    {
        $trainer = User::factory()->create(['rol' => 'entrenador']);

        $this->actingAs($trainer)->post(route('exercises.custom.store'), [
            'name' => 'En uso',
            'description' => 'Vinculado a entreno',
            'materials' => 'tabla',
        ]);

        $exerciseId = DB::table('custom_exercises')->where('user_id', $trainer->id)->value('id');

        Schema::create('workout_exercises', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('custom_exercise_id');
            $table->timestamps();
        });

        DB::table('workout_exercises')->insert([
            'custom_exercise_id' => $exerciseId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->actingAs($trainer)
            ->delete(route('exercises.custom.destroy', $exerciseId));

        $response
            ->assertRedirect(route('exercises.library'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('custom_exercises', [
            'id' => $exerciseId,
            'deleted_at' => null,
        ]);
    }
}


