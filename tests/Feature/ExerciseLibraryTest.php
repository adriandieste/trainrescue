<?php

namespace Tests\Feature;

use App\Models\PredefinedExercise;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}


