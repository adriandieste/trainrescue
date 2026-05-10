<?php
namespace App\Http\Controllers;
use App\Http\Requests\GuardarWorkoutRequest;
use App\Models\CustomExercise;
use App\Models\PredefinedExercise;
use App\Models\Workout;
use App\Models\WorkoutExercise;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
class WorkoutController extends Controller
{
    public function store(GuardarWorkoutRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $trainer = $request->user();
        $isTemplate = (bool) ($validated['is_template'] ?? false);
        $workout = DB::transaction(function () use ($validated, $trainer, $isTemplate) {
            $workout = Workout::create([
                'creator_user_id' => $trainer->id,
                'club_id'         => $validated['target_scope'] === 'club' ? $trainer->club_id : null,
                'title'           => $validated['title'],
                'workout_date'    => $validated['workout_date'] ?? null,
                'target_scope'    => $validated['target_scope'],
                'is_template'     => $isTemplate,
            ]);
            $this->syncWorkoutExercises($workout, $validated['exercises'], $trainer->id);
            return $workout;
        });
        if ($isTemplate) {
            return redirect()
                ->route('exercises.library')
                ->with('success', 'Plantilla "'.$workout->title.'" guardada correctamente en Mis Plantillas.');
        }
        return redirect()
            ->route('exercises.library')
            ->with('success', 'Entrenamiento creado correctamente para el '.$workout->workout_date->format('d/m/Y').'.');
    }
    public function update(GuardarWorkoutRequest $request, Workout $workout): RedirectResponse
    {
        Gate::authorize('update', $workout);
        $validated = $request->validated();
        $trainer = $request->user();
        $isTemplate = (bool) ($validated['is_template'] ?? false);
        DB::transaction(function () use ($validated, $trainer, $workout, $isTemplate) {
            $workout->update([
                'club_id'      => $validated['target_scope'] === 'club' ? $trainer->club_id : null,
                'title'        => $validated['title'],
                'workout_date' => $validated['workout_date'] ?? null,
                'target_scope' => $validated['target_scope'],
                'is_template'  => $isTemplate,
            ]);
            $workout->exercises()->delete();
            $this->syncWorkoutExercises($workout, $validated['exercises'], $trainer->id);
        });
        if ($isTemplate) {
            return redirect()
                ->route('exercises.library')
                ->with('success', 'Plantilla actualizada correctamente.');
        }
        return redirect()
            ->route('exercises.library')
            ->with('success', 'Entrenamiento actualizado correctamente.');
    }
    private function syncWorkoutExercises(Workout $workout, array $exerciseInputs, int $trainerId): void
    {
        foreach ($exerciseInputs as $index => $exerciseInput) {
            $source     = $exerciseInput['source'];
            $exerciseId = (int) $exerciseInput['exercise_id'];
            $predefinedId = null;
            $customId     = null;
            if ($source === 'predefined') {
                $exists = PredefinedExercise::query()
                    ->where('id', $exerciseId)
                    ->where('is_active', true)
                    ->exists();
                if (! $exists) {
                    throw ValidationException::withMessages([
                        'exercises' => 'Se detecto un ejercicio RFESS invalido en la sesion.',
                    ]);
                }
                $predefinedId = $exerciseId;
            }
            if ($source === 'custom') {
                $exists = CustomExercise::query()
                    ->where('id', $exerciseId)
                    ->where('user_id', $trainerId)
                    ->exists();
                if (! $exists) {
                    throw ValidationException::withMessages([
                        'exercises' => 'Se detecto un ejercicio personalizado invalido en la sesion.',
                    ]);
                }
                $customId = $exerciseId;
            }
            WorkoutExercise::create([
                'workout_id'            => $workout->id,
                'predefined_exercise_id' => $predefinedId,
                'custom_exercise_id'     => $customId,
                'sort_order'            => $index,
                'sets'                  => $exerciseInput['sets'],
                'reps'                  => null,
                'meters'               => $exerciseInput['meters'] ?? null,
                'rest_seconds'          => $exerciseInput['rest_seconds'] ?? null,
            ]);
        }
    }
}
