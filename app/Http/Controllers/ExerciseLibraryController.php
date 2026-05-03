<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarEjercicioPersonalizadoRequest;
use App\Models\CustomExercise;
use App\Models\PredefinedExercise;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExerciseLibraryController extends Controller
{
    public function index(Request $request): Response
    {
        $predefinedExercises = PredefinedExercise::query()
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->map(fn (PredefinedExercise $exercise) => [
                'id' => 'predefined-' . $exercise->id,
                'custom_exercise_id' => null,
                'name' => $exercise->name,
                'category' => $exercise->category,
                'technical_description' => $exercise->technical_description,
                'materials' => $exercise->materials ?? [],
                'video_url' => null,
                'source' => 'predefined',
                'can_edit' => false,
                'can_delete' => false,
            ])
            ->values();

        $customExercises = CustomExercise::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (CustomExercise $exercise) => [
                'id' => 'custom-' . $exercise->id,
                'custom_exercise_id' => $exercise->id,
                'name' => $exercise->name,
                'category' => 'personalizado',
                'technical_description' => $exercise->description,
                'materials' => $this->parseMaterials($exercise->materials),
                'video_url' => $exercise->video_url,
                'source' => 'custom',
                'can_edit' => true,
                'can_delete' => true,
            ])
            ->values();

        $exercises = $customExercises
            ->concat($predefinedExercises->all())
            ->values();

        $categories = $exercises
            ->pluck('category')
            ->unique()
            ->sort()
            ->values();

        return Inertia::render('Ejercicios/Entrenos', [
            'exercises' => $exercises,
            'categories' => $categories,
        ]);
    }

    public function storeCustom(GuardarEjercicioPersonalizadoRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        CustomExercise::create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'description' => $validated['description'],
            'materials' => $validated['materials'] ?? null,
            'video_url' => $validated['video_url'] ?? null,
        ]);

        return redirect()
            ->route('exercises.library')
            ->with('success', 'Ejercicio personalizado creado correctamente.');
    }

    public function updateCustom(GuardarEjercicioPersonalizadoRequest $request, CustomExercise $customExercise): RedirectResponse
    {
        Gate::authorize('update', $customExercise);

        $validated = $request->validated();

        $customExercise->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'materials' => $validated['materials'] ?? null,
            'video_url' => $validated['video_url'] ?? null,
        ]);

        return redirect()
            ->route('exercises.library')
            ->with('success', 'Ejercicio personalizado actualizado correctamente.');
    }

    public function destroyCustom(Request $request, CustomExercise $customExercise): RedirectResponse
    {
        Gate::authorize('delete', $customExercise);

        if ($this->isUsedInWorkout($customExercise->id)) {
            return redirect()
                ->route('exercises.library')
                ->with('error', 'No se puede eliminar: este ejercicio esta vinculado a un entrenamiento existente.');
        }

        $customExercise->delete();

        return redirect()
            ->route('exercises.library')
            ->with('success', 'Ejercicio personalizado eliminado correctamente.');
    }

    private function parseMaterials(?string $materials): array
    {
        if (! $materials) {
            return [];
        }

        return collect(preg_split('/[,\n]+/', $materials) ?: [])
            ->map(fn (string $item) => trim($item))
            ->filter()
            ->values()
            ->all();
    }

    private function isUsedInWorkout(int $customExerciseId): bool
    {
        if (! Schema::hasTable('workout_exercises')) {
            return false;
        }

        if (Schema::hasColumn('workout_exercises', 'custom_exercise_id')) {
            return DB::table('workout_exercises')
                ->where('custom_exercise_id', $customExerciseId)
                ->exists();
        }

        return false;
    }
}

