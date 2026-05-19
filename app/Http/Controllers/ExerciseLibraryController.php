<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarEjercicioPersonalizadoRequest;
use App\Models\CustomExercise;
use App\Models\PredefinedExercise;
use App\Models\User;
use App\Models\Workout;
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
                'exercise_id' => $exercise->id,
                'custom_exercise_id' => null,
                'name' => $exercise->name,
                'category' => $exercise->category,
                'technical_description' => $exercise->technical_description,
                'materials' => $exercise->materials ?? [],
                'video_url' => null,
                'source' => 'predefined',
                'default_sets' => 3,
                'default_meters' => null,
                'default_rest_seconds' => 45,
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
                'exercise_id' => $exercise->id,
                'custom_exercise_id' => $exercise->id,
                'name' => $exercise->name,
                'category' => 'personalizado',
                'technical_description' => $exercise->description,
                'materials' => $this->parseMaterials($exercise->materials),
                'video_url' => $exercise->video_url,
                'source' => 'custom',
                'default_sets' => $exercise->default_sets ?? 3,
                'default_meters' => $exercise->default_meters,
                'default_rest_seconds' => $exercise->default_rest_seconds ?? 45,
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

        $workouts = collect();
        $templates = collect();
        $globalTemplates = collect();
        $calendarEvents = collect();
        $editWorkoutId = null;

        $user        = $request->user();
        $clubMembers = collect();
        $groups      = collect();
        if ($user->club_id) {
            $clubMembers = User::where('club_id', $user->club_id)
                ->where('id', '!=', $user->id)
                ->orderBy('name')
                ->get()
                ->map(fn (User $member) => [
                    'id'         => $member->id,
                    'name'       => $member->name,
                    'role_label' => $member->rol === 'entrenador' ? 'Entrenador' : 'Socorrista',
                ])
                ->values();

            $groups = \App\Models\Group::where('club_id', $user->club_id)
                ->with('users')
                ->get()
                ->map(function ($group) {
                    return [
                        'id'         => $group->id,
                        'name'       => $group->name,
                        'user_ids'   => $group->users->pluck('id')->toArray(),
                        'member_count' => $group->users->count(),
                    ];
                })
                ->values();
        }

        if (Schema::hasTable('workouts') && Schema::hasTable('workout_exercises')) {
            $supportsAssignments = Schema::hasTable('workout_assignments');
            $supportsTemplates = Schema::hasColumn('workouts', 'is_template');
            $supportsTemplateVisibility = Schema::hasColumn('workouts', 'is_public');

            $baseQuery = Workout::query()
                ->with([
                    'exercises.predefinedExercise',
                    'exercises.customExercise',
                    ...($supportsAssignments ? ['assignedUsers'] : []),
                ])
                ->where(function ($query) use ($request) {
                    $query->where('creator_user_id', $request->user()->id);

                    if ($request->user()->club_id) {
                        $query->orWhere('club_id', $request->user()->club_id);
                    }
                });

            $workouts = (clone $baseQuery)
                ->when($supportsTemplates, fn ($query) => $query->where('is_template', false))
                ->orderBy('workout_date')
                ->orderByDesc('created_at')
                ->limit(20)
                ->get()
                ->map(fn (Workout $workout) => $this->mapWorkoutForBuilder($workout, $request))
                ->values();

            if ($supportsTemplates) {
                $templateBaseQuery = Workout::query()
                    ->with([
                        'creator',
                        'exercises.predefinedExercise',
                        'exercises.customExercise',
                        ...($supportsAssignments ? ['assignedUsers'] : []),
                    ])
                    ->where('is_template', true);

                $templates = (clone $templateBaseQuery)
                    ->where('creator_user_id', $request->user()->id)
                    ->orderByDesc('updated_at')
                    ->limit(20)
                    ->get()
                    ->map(fn (Workout $workout) => $this->mapWorkoutForBuilder($workout, $request))
                    ->values();

                if ($supportsTemplateVisibility) {
                    $globalTemplates = (clone $templateBaseQuery)
                        ->where('is_public', true)
                        ->where('creator_user_id', '!=', $request->user()->id)
                        ->orderByDesc('updated_at')
                        ->limit(20)
                        ->get()
                        ->map(fn (Workout $workout) => $this->mapWorkoutForBuilder($workout, $request))
                        ->values();
                }
            }

            $calendarEvents = (clone $baseQuery)
                ->whereNotNull('workout_date')
                ->when($supportsTemplates, fn ($query) => $query->where('is_template', false))
                ->orderBy('workout_date')
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (Workout $workout) => $this->mapWorkoutForCalendar($workout, $request))
                ->values();

            $requestedEditId = $request->integer('edit_workout_id');
            if ($requestedEditId) {
                $workoutToEdit = $workouts
                    ->concat($templates)
                    ->first(fn (array $item) => (int) $item['id'] === $requestedEditId && $item['can_edit']);

                if (! $workoutToEdit) {
                    $directWorkout = Workout::query()
                        ->with([
                            'exercises.predefinedExercise',
                            'exercises.customExercise',
                            ...($supportsAssignments ? ['assignedUsers'] : []),
                        ])
                        ->whereKey($requestedEditId)
                        ->where(function ($query) use ($request) {
                            $query->where('creator_user_id', $request->user()->id);

                            if ($request->user()->club_id) {
                                $query->orWhere('club_id', $request->user()->club_id);
                            }
                        })
                        ->first();

                    if ($directWorkout && Gate::forUser($request->user())->allows('update', $directWorkout)) {
                        $mappedWorkout = $this->mapWorkoutForBuilder($directWorkout, $request);
                        $workouts = collect([$mappedWorkout])
                            ->concat($workouts)
                            ->unique('id')
                            ->values();
                        $workoutToEdit = $mappedWorkout;
                    }
                }

                $editWorkoutId = $workoutToEdit['id'] ?? null;
            }
        }

        return Inertia::render('Ejercicios/Entrenos', [
            'exercises'       => $exercises,
            'categories'      => $categories,
            'entrenamientos'  => $workouts,
            'plantillas'      => $templates,
            'plantillasGlobales' => $globalTemplates,
            'calendarEvents'  => $calendarEvents,
            'hasClub'         => (bool) $request->user()->club_id,
            'clubMembers'     => $clubMembers,
            'groups'          => $groups,
            'editWorkoutId'   => $editWorkoutId,
        ]);
    }

    public function calendar(Request $request): Response
    {
        $user = $request->user();

        $clubMembers = collect();
        $groups = collect();
        if ($user->club_id) {
            $clubMembers = User::where('club_id', $user->club_id)
                ->where('id', '!=', $user->id)
                ->orderBy('name')
                ->get()
                ->map(fn (User $member) => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'role_label' => $member->rol === 'entrenador' ? 'Entrenador' : 'Socorrista',
                ])
                ->values();

            $groups = \App\Models\Group::where('club_id', $user->club_id)
                ->with('users')
                ->get()
                ->map(fn ($group) => [
                    'id' => $group->id,
                    'name' => $group->name,
                    'user_ids' => $group->users->pluck('id')->toArray(),
                    'member_count' => $group->users->count(),
                ])
                ->values();
        }

        $calendarEvents = collect();
        if (Schema::hasTable('workouts')) {
            $supportsAssignments = Schema::hasTable('workout_assignments');
            $supportsTemplates = Schema::hasColumn('workouts', 'is_template');

            $calendarEvents = Workout::query()
                ->with([
                    ...($supportsAssignments ? ['assignedUsers'] : []),
                ])
                ->whereNotNull('workout_date')
                ->where(function ($query) use ($user) {
                    $query->where('creator_user_id', $user->id);

                    if ($user->club_id) {
                        $query->orWhere('club_id', $user->club_id);
                    }
                })
                ->when($supportsTemplates, fn ($query) => $query->where('is_template', false))
                ->orderBy('workout_date')
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (Workout $workout) => $this->mapWorkoutForCalendar($workout, $request))
                ->values();
        }

        return Inertia::render('Ejercicios/Calendario', [
            'calendarEvents' => $calendarEvents,
            'hasClub' => (bool) $user->club_id,
            'clubMembers' => $clubMembers,
            'groups' => $groups,
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
            'default_sets' => $validated['default_sets'] ?? 3,
            'default_meters' => $validated['default_meters'] ?? null,
            'default_rest_seconds' => $validated['default_rest_seconds'] ?? 45,
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
            'default_sets' => $validated['default_sets'] ?? 3,
            'default_meters' => $validated['default_meters'] ?? null,
            'default_rest_seconds' => $validated['default_rest_seconds'] ?? 45,
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
                ->with('error', 'No se puede eliminar: este ejercicio esta vinculado a una sesion de entrenamiento existente.');
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

    private function mapWorkoutForBuilder(Workout $workout, Request $request): array
    {
        $lines = $workout->exercises->map(function ($line) {
            $isCustom = (bool) $line->custom_exercise_id;
            $exerciseModel = $isCustom ? $line->customExercise : $line->predefinedExercise;

            if (! $exerciseModel) {
                return null;
            }

            return [
                'source' => $isCustom ? 'custom' : 'predefined',
                'exercise_id' => $isCustom ? $line->custom_exercise_id : $line->predefined_exercise_id,
                'name' => $exerciseModel->name,
                'sets' => $line->sets,
                'meters' => $line->meters,
                'rest_seconds' => $line->rest_seconds,
            ];
        })
            ->filter()
            ->values();

        return [
            'id'              => $workout->id,
            'title'           => $workout->title,
            'workout_date'    => $workout->workout_date?->format('Y-m-d'),
            'target_scope'    => $workout->target_scope,
            'is_template'     => (bool) $workout->is_template,
            'is_public'       => (bool) ($workout->is_public ?? false),
            'creator_name'    => $workout->relationLoaded('creator') ? $workout->creator?->name : null,
            'can_edit'        => Gate::forUser($request->user())->allows('update', $workout),
            'assigned_user_ids' => $workout->relationLoaded('assignedUsers')
                ? $workout->assignedUsers->pluck('id')->values()->all()
                : [],
            'exercises'       => $lines,
        ];
    }

    private function mapWorkoutForCalendar(Workout $workout, Request $request): array
    {
        return [
            'id'                => $workout->id,
            'title'             => $workout->title,
            'workout_date'      => $workout->workout_date?->format('Y-m-d'),
            'target_scope'      => $workout->target_scope,
            'can_edit'          => Gate::forUser($request->user())->allows('update', $workout),
            'assigned_user_ids' => $workout->relationLoaded('assignedUsers')
                ? $workout->assignedUsers->pluck('id')->values()->all()
                : [],
        ];
    }
}

