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
}


