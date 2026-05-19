<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarWorkoutRequest;
use App\Models\CustomExercise;
use App\Models\PredefinedExercise;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutExercise;
use App\Notifications\WorkoutAsignadoNotification;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class WorkoutController extends Controller
{
    public function store(GuardarWorkoutRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $trainer   = $request->user();

        $workout = DB::transaction(function () use ($validated, $trainer) {
            $workout = Workout::create([
                'creator_user_id' => $trainer->id,
                'club_id'         => in_array($validated['target_scope'], ['club', 'grupo'], true)
                    ? $trainer->club_id
                    : null,
                'title'        => $validated['title'],
                'workout_date' => $validated['is_template'] ? null : $validated['workout_date'],
                'target_scope' => $validated['target_scope'],
                'is_template'  => (bool) $validated['is_template'],
                'is_public'    => (bool) ($validated['is_template'] ? $validated['is_public'] : false),
            ]);

            $this->syncWorkoutExercises($workout, $validated['exercises'], $trainer->id);

            if ($validated['target_scope'] === 'grupo') {
                $workout->assignedUsers()->sync($validated['assigned_user_ids'] ?? []);
            }

            return $workout;
        });

        $successMessage = $this->buildSuccessMessage($workout, $validated, 'created');

        if (! $workout->is_template) {
            $this->dispatchNotifications($workout, $validated, $trainer->id);
        }

        return redirect()
            ->route('exercises.library')
            ->with('success', $successMessage);
    }

    public function update(GuardarWorkoutRequest $request, Workout $workout): RedirectResponse
    {
        Gate::authorize('update', $workout);

        $validated = $request->validated();
        $trainer   = $request->user();

        DB::transaction(function () use ($validated, $trainer, $workout) {
            $workout->update([
                'club_id'      => in_array($validated['target_scope'], ['club', 'grupo'], true)
                    ? $trainer->club_id
                    : null,
                'title'        => $validated['title'],
                'workout_date' => $validated['is_template'] ? null : $validated['workout_date'],
                'target_scope' => $validated['target_scope'],
                'is_template'  => (bool) $validated['is_template'],
                'is_public'    => (bool) ($validated['is_template'] ? $validated['is_public'] : false),
            ]);

            $workout->exercises()->delete();
            $this->syncWorkoutExercises($workout, $validated['exercises'], $trainer->id);

            // Sync group assignments (clear if no longer a grupo workout)
            if ($validated['target_scope'] === 'grupo') {
                $workout->assignedUsers()->sync($validated['assigned_user_ids'] ?? []);
            } else {
                $workout->assignedUsers()->detach();
            }
        });

        $fresh          = $workout->fresh();
        $successMessage = $this->buildSuccessMessage($fresh, $validated, 'updated');

        if (! $fresh->is_template) {
            $this->dispatchNotifications($fresh, $validated, $trainer->id);
        }

        return redirect()
            ->route('exercises.library')
            ->with('success', $successMessage);
    }

    public function duplicate(Request $request, Workout $workout): RedirectResponse
    {
        Gate::authorize('duplicate', $workout);

        $trainer = $request->user();

        $duplicatedWorkout = DB::transaction(function () use ($workout, $trainer) {
            $workout->loadMissing(['exercises', 'assignedUsers']);

            $copy = Workout::create([
                'creator_user_id' => $trainer->id,
                'club_id'         => in_array($workout->target_scope, ['club', 'grupo'], true)
                    ? $trainer->club_id
                    : null,
                'title'        => $workout->title,
                'workout_date' => $workout->workout_date,
                'target_scope' => $workout->target_scope,
                'is_template'  => (bool) $workout->is_template,
                'is_public'    => false,
            ]);

            foreach ($workout->exercises as $line) {
                WorkoutExercise::create([
                    'workout_id'           => $copy->id,
                    'predefined_exercise_id' => $line->predefined_exercise_id,
                    'custom_exercise_id'   => $line->custom_exercise_id,
                    'sort_order'           => $line->sort_order,
                    'sets'                 => $line->sets,
                    'reps'                 => null,
                    'meters'               => $line->meters,
                    'rest_seconds'         => $line->rest_seconds,
                ]);
            }

            if ($workout->target_scope === 'grupo' && $workout->assignedUsers->isNotEmpty()) {
                $copy->assignedUsers()->sync($workout->assignedUsers->pluck('id')->all());
            }

            return $copy;
        });

        return redirect()
            ->route('exercises.library', ['edit_workout_id' => $duplicatedWorkout->id])
            ->with('success', 'Entrenamiento duplicado correctamente. Ya puedes ajustar la copia.');
    }
    public function reschedule(Request $request, Workout $workout): \Illuminate\Http\JsonResponse
    {
        Gate::authorize('update', $workout);

        $request->validate([
            'workout_date' => ['required', 'date_format:Y-m-d'],
        ]);

        $workout->update(['workout_date' => $request->input('workout_date')]);

        return response()->json([
            'message' => 'Entrenamiento reprogramado con éxito',
            'workout' => [
                'id' => $workout->id,
                'workout_date' => $workout->workout_date?->format('Y-m-d'),
            ],
        ], 200);
    }

    public function destroy(Request $request, Workout $workout): RedirectResponse
    {
        Gate::authorize('delete', $workout);

        $workout->delete();

        return redirect()
            ->route('exercises.library')
            ->with('success', 'Entrenamiento eliminado correctamente.');
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    private function buildSuccessMessage(Workout $workout, array $validated, string $action): string
    {
        if ($workout->is_template) {
            return $action === 'created'
                ? 'Plantilla guardada correctamente.'
                : 'Plantilla actualizada correctamente.';
        }

        if ($workout->target_scope === 'grupo') {
            $count = count($validated['assigned_user_ids'] ?? []);
            $noun  = $count === 1 ? 'atleta' : 'atletas';
            return "Sesión asignada correctamente a {$count} {$noun}.";
        }

        return $action === 'created'
            ? 'Entrenamiento creado correctamente para el '.$workout->workout_date?->format('d/m/Y').'.'
            : 'Entrenamiento actualizado correctamente.';
    }

    private function dispatchNotifications(Workout $workout, array $validated, int $trainerId): void
    {
        match ($workout->target_scope) {
            'club'  => $this->notifyClubMembers($workout, $trainerId),
            'grupo' => $this->notifyGroupMembers($workout, $validated['assigned_user_ids'] ?? [], $trainerId),
            default => null,
        };
    }

    private function notifyClubMembers(Workout $workout, int $excludeUserId): void
    {
        $members = User::where('club_id', $workout->club_id)
            ->where('id', '!=', $excludeUserId)
            ->get();

        if ($members->isNotEmpty()) {
            Notification::send($members, new WorkoutAsignadoNotification($workout));
        }
    }

    private function notifyGroupMembers(Workout $workout, array $userIds, int $excludeUserId): void
    {
        if (empty($userIds)) {
            return;
        }

        $members = User::whereIn('id', $userIds)
            ->where('id', '!=', $excludeUserId)
            ->get();

        if ($members->isNotEmpty()) {
            Notification::send($members, new WorkoutAsignadoNotification($workout));
        }
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
                'workout_id'             => $workout->id,
                'predefined_exercise_id' => $predefinedId,
                'custom_exercise_id'     => $customId,
                'sort_order'             => $index,
                'sets'                   => $exerciseInput['sets'],
                'reps'                   => null,
                'meters'                 => $exerciseInput['meters'] ?? null,
                'rest_seconds'           => $exerciseInput['rest_seconds'] ?? null,
            ]);
        }
    }
}
