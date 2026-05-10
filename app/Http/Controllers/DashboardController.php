<?php

namespace App\Http\Controllers;

use App\Models\ClubInvitation;
use App\Models\ClubJoinRequest;
use App\Models\User;
use App\Models\Workout;
use App\Notifications\WorkoutAsignadoNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user()->load('club');

        if ($user->rol === 'entrenador') {
            $pendingCount  = 0;
            $members       = null;
            $pendingInvitations = [];
            $searchResults = [];
            $search        = '';

            if ($user->club_id && $user->club?->admin_user_id === $user->id) {
                $club = $user->club;

                $pendingCount = ClubJoinRequest::where('club_id', $user->club_id)
                    ->where('status', 'pending')
                    ->count();

                $search = trim((string) $request->query('search', ''));

                $members = $club->users()
                    ->orderBy('name')
                    ->paginate(10)
                    ->through(fn (User $member) => [
                        'id'         => $member->id,
                        'name'       => $member->name,
                        'email'      => $member->email,
                        'avatar_url' => $member->avatar ? asset('storage/' . $member->avatar) : null,
                        'role'       => $member->rol,
                        'role_label' => $member->rol === 'entrenador' ? 'Entrenador' : 'Socorrista',
                    ])
                    ->withQueryString();

                $pendingInvitationUserIds = ClubInvitation::where('club_id', $club->id)
                    ->where('status', 'pending')
                    ->pluck('invited_user_id')
                    ->all();

                $pendingJoinRequestUserIds = ClubJoinRequest::where('club_id', $club->id)
                    ->where('status', 'pending')
                    ->pluck('user_id')
                    ->all();

                if ($search !== '') {
                    $searchResults = User::query()
                        ->whereIn('rol', ['socorrista', 'atleta'])
                        ->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"))
                        ->orderBy('name')
                        ->limit(10)
                        ->get()
                        ->map(function (User $candidate) use ($club, $pendingInvitationUserIds, $pendingJoinRequestUserIds) {
                            $statusLabel = 'Disponible';
                            $canInvite   = true;

                            if ($candidate->club_id === $club->id) {
                                $statusLabel = 'Ya pertenece a tu club';
                                $canInvite   = false;
                            } elseif ($candidate->club_id !== null) {
                                $statusLabel = 'Ya pertenece a otro club';
                                $canInvite   = false;
                            } elseif (in_array($candidate->id, $pendingInvitationUserIds, true)) {
                                $statusLabel = 'Invitación pendiente';
                                $canInvite   = false;
                            } elseif (in_array($candidate->id, $pendingJoinRequestUserIds, true)) {
                                $statusLabel = 'Solicitud pendiente';
                                $canInvite   = false;
                            }

                            return [
                                'id'           => $candidate->id,
                                'name'         => $candidate->name,
                                'email'        => $candidate->email,
                                'avatar_url'   => $candidate->avatar ? asset('storage/' . $candidate->avatar) : null,
                                'can_invite'   => $canInvite,
                                'status_label' => $statusLabel,
                            ];
                        })
                        ->values()
                        ->all();
                }

                $pendingInvitations = ClubInvitation::with('invitedUser')
                    ->where('club_id', $club->id)
                    ->where('status', 'pending')
                    ->latest()
                    ->get()
                    ->map(fn (ClubInvitation $invitation) => [
                        'id'           => $invitation->id,
                        'invited_user' => [
                            'id'         => $invitation->invitedUser->id,
                            'name'       => $invitation->invitedUser->name,
                            'email'      => $invitation->invitedUser->email,
                            'avatar_url' => $invitation->invitedUser->avatar
                                ? asset('storage/' . $invitation->invitedUser->avatar)
                                : null,
                        ],
                        'status'     => $invitation->status,
                        'created_at' => $invitation->created_at->diffForHumans(),
                    ])
                    ->all();
            }

            return Inertia::render('Dashboard', [
                'pendingRequestsCount' => $pendingCount,
                'members'             => $members,
                'pendingInvitations'  => $pendingInvitations,
                'searchResults'       => $searchResults,
                'filters'             => ['search' => $search],
            ]);
        }

        if (in_array($user->rol, ['socorrista', 'atleta'], true)) {
            $pendingInvitations = ClubInvitation::with(['club', 'inviter'])
                ->where('invited_user_id', $user->id)
                ->where('status', 'pending')
                ->latest()
                ->get()
                ->map(fn (ClubInvitation $invitation) => [
                    'id' => $invitation->id,
                    'club' => [
                        'id' => $invitation->club->id,
                        'name' => $invitation->club->name,
                        'logo_url' => $invitation->club->logo_path ? asset('storage/' . $invitation->club->logo_path) : null,
                    ],
                    'trainer' => [
                        'id' => $invitation->inviter->id,
                        'name' => $invitation->inviter->name,
                        'email' => $invitation->inviter->email,
                    ],
                    'created_at' => $invitation->created_at->diffForHumans(),
                ]);

            $clubmates = [];
            if ($user->club_id) {
                $clubmates = $user->club->users()
                    ->orderBy('name')
                    ->get()
                    ->map(fn (User $member) => [
                        'id' => $member->id,
                        'name' => $member->name,
                        'email' => $member->email,
                        'avatar_url' => $member->avatar ? asset('storage/' . $member->avatar) : null,
                        'role' => $member->rol,
                        'role_label' => $member->rol === 'entrenador' ? 'Entrenador' : 'Socorrista',
                    ])
                    ->all();
            }

            $entrenamientos = [];
            $entrenamientoHoy = null;
            if ($user->club_id && Schema::hasTable('workouts') && Schema::hasTable('workout_exercises')) {
                $supportsTemplates   = Schema::hasColumn('workouts', 'is_template');
                $supportsAssignments = Schema::hasTable('workout_assignments');
                $supportsCompletions = Schema::hasTable('workout_completions');

                $completedWorkoutIds = $supportsCompletions
                    ? DB::table('workout_completions')
                        ->where('user_id', $user->id)
                        ->pluck('workout_id')
                        ->map(fn ($id) => (int) $id)
                        ->all()
                    : [];

                $visibleWorkoutsQuery = Workout::with(['exercises.predefinedExercise', 'exercises.customExercise'])
                    ->where(function ($query) use ($user, $supportsAssignments) {
                        // Workouts de todo el club
                        $query->where(function ($q) use ($user) {
                            $q->where('club_id', $user->club_id)
                              ->where('target_scope', 'club');
                        });
                        // Workouts de grupo donde este atleta está asignado específicamente
                        if ($supportsAssignments) {
                            $query->orWhere(function ($q) use ($user) {
                                $q->where('target_scope', 'grupo')
                                  ->whereHas('assignedUsers', fn ($rel) => $rel->where('users.id', $user->id));
                            });
                        }
                    })
                    ->when($supportsTemplates, fn ($query) => $query->where('is_template', false));

                $entrenamientos = (clone $visibleWorkoutsQuery)
                    ->orderBy('workout_date', 'asc')
                    ->get()
                    ->map(fn (Workout $workout) => $this->mapWorkoutForAtletaDashboard($workout, $completedWorkoutIds))
                    ->all();

                $entrenamientoHoyModel = (clone $visibleWorkoutsQuery)
                    ->whereDate('workout_date', Carbon::today())
                    ->orderByDesc('created_at')
                    ->first();

                $entrenamientoHoy = $entrenamientoHoyModel
                    ? $this->mapWorkoutForAtletaDashboard($entrenamientoHoyModel, $completedWorkoutIds)
                    : null;
            }
            return Inertia::render('DashboardAtleta', [
                'invitationsTitle'   => 'Invitaciones',
                'pendingInvitations' => $pendingInvitations,
                'clubmates'          => $clubmates,
                'entrenamientos'     => $entrenamientos,
                'entrenamientoHoy'   => $entrenamientoHoy,
                'notificaciones'     => $this->buildNotificaciones($user),
            ]);
        }
        abort(403, 'Acceso denegado: Rol no reconocido.');
    }

    public function markWorkoutCompleted(Request $request, Workout $workout): RedirectResponse
    {
        $user = $request->user();

        if (! in_array($user->rol, ['socorrista', 'atleta'], true)) {
            abort(403, 'Solo atletas o socorristas pueden marcar entrenamientos como completados.');
        }

        if (! $user->club_id) {
            return redirect()->route('dashboard')->with('error', 'No perteneces a un club.');
        }

        if (! Schema::hasTable('workout_completions')) {
            return redirect()->route('dashboard')->with('error', 'El seguimiento de cumplimiento no está disponible todavía.');
        }

        $supportsTemplates = Schema::hasColumn('workouts', 'is_template');
        $supportsAssignments = Schema::hasTable('workout_assignments');

        $visibleWorkout = Workout::query()
            ->whereKey($workout->id)
            ->whereDate('workout_date', '<=', Carbon::today())
            ->where(function ($query) use ($user, $supportsAssignments) {
                $query->where(function ($q) use ($user) {
                    $q->where('club_id', $user->club_id)
                      ->where('target_scope', 'club');
                });

                if ($supportsAssignments) {
                    $query->orWhere(function ($q) use ($user) {
                        $q->where('target_scope', 'grupo')
                          ->whereHas('assignedUsers', fn ($rel) => $rel->where('users.id', $user->id));
                    });
                }
            })
            ->when($supportsTemplates, fn ($query) => $query->where('is_template', false))
            ->first();

        if (! $visibleWorkout) {
            return redirect()->route('dashboard')->with('error', 'No puedes marcar este entrenamiento como completado.');
        }

        DB::table('workout_completions')->updateOrInsert(
            [
                'user_id' => $user->id,
                'workout_id' => $visibleWorkout->id,
            ],
            [
                'completed_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return redirect()->route('dashboard')->with('success', 'Entrenamiento marcado como completado.');
    }

    private function mapWorkoutForAtletaDashboard(Workout $workout, array $completedWorkoutIds = []): array
    {
        $workoutDate = $workout->workout_date?->format('Y-m-d');
        $today = Carbon::today()->format('Y-m-d');
        $isCompleted = in_array((int) $workout->id, $completedWorkoutIds, true);

        $completionStatus = 'future';
        if ($isCompleted && $workoutDate && $workoutDate <= $today) {
            $completionStatus = 'completed';
        } elseif ($workoutDate && $workoutDate < $today) {
            $completionStatus = 'pending';
        }

        return [
            'id'                     => $workout->id,
            'title'                  => $workout->title,
            'workout_date'           => $workoutDate,
            'workout_date_formatted' => $workout->workout_date?->format('d/m/Y'),
            'is_completed'           => $isCompleted,
            'completion_status'      => $completionStatus,
            'exercises'              => $workout->exercises->map(function ($line) {
                $name = $line->predefinedExercise?->name
                    ?? $line->customExercise?->name
                    ?? 'Ejercicio';
                $label = $line->sets . ' x';
                if ($line->meters) {
                    $label .= ' ' . $line->meters . 'm';
                } else {
                    $label .= ' series';
                }
                if ($line->rest_seconds !== null) {
                    $label .= ' — Descanso: ' . $line->rest_seconds . 's';
                }
                return [
                    'name'         => $name,
                    'sets'         => $line->sets,
                    'meters'       => $line->meters,
                    'rest_seconds' => $line->rest_seconds,
                    'load_label'   => $label,
                ];
            })->values()->all(),
        ];
    }

    private function buildNotificaciones(User $user): array
    {
        if (! Schema::hasTable('notifications')) {
            return [];
        }

        return $user->unreadNotifications()
            ->where('type', WorkoutAsignadoNotification::class)
            ->latest()
            ->take(10)
            ->get()
            ->map(fn ($n) => [
                'id'                     => $n->id,
                'workout_id'             => $n->data['workout_id'],
                'workout_title'          => $n->data['workout_title'],
                'workout_date_formatted' => $n->data['workout_date_formatted'] ?? null,
                'created_at'             => $n->created_at->diffForHumans(),
            ])
            ->values()
            ->all();
    }

    public function calendarioAtleta(Request $request): Response
    {
        $user = $request->user()->load('club');

        if (! in_array($user->rol, ['socorrista', 'atleta'], true)) {
            abort(403, 'Solo atletas o socorristas pueden acceder al calendario.');
        }

        if (! $user->club_id) {
            return Inertia::render('CalendarioAtleta', ['entrenamientos' => []]);
        }

        $entrenamientos = [];
        if (Schema::hasTable('workouts') && Schema::hasTable('workout_exercises')) {
            $supportsTemplates   = Schema::hasColumn('workouts', 'is_template');
            $supportsAssignments = Schema::hasTable('workout_assignments');
            $supportsCompletions = Schema::hasTable('workout_completions');

            $completedWorkoutIds = $supportsCompletions
                ? DB::table('workout_completions')
                    ->where('user_id', $user->id)
                    ->pluck('workout_id')
                    ->map(fn ($id) => (int) $id)
                    ->all()
                : [];

            $visibleWorkoutsQuery = Workout::with(['exercises.predefinedExercise', 'exercises.customExercise'])
                ->where(function ($query) use ($user, $supportsAssignments) {
                    // Workouts de todo el club
                    $query->where(function ($q) use ($user) {
                        $q->where('club_id', $user->club_id)
                          ->where('target_scope', 'club');
                    });
                    // Workouts de grupo donde este atleta está asignado específicamente
                    if ($supportsAssignments) {
                        $query->orWhere(function ($q) use ($user) {
                            $q->where('target_scope', 'grupo')
                              ->whereHas('assignedUsers', fn ($rel) => $rel->where('users.id', $user->id));
                        });
                    }
                })
                ->when($supportsTemplates, fn ($query) => $query->where('is_template', false));

            $entrenamientos = (clone $visibleWorkoutsQuery)
                ->orderBy('workout_date', 'asc')
                ->get()
                ->map(fn (Workout $workout) => $this->mapWorkoutForAtletaDashboard($workout, $completedWorkoutIds))
                ->all();
        }

        return Inertia::render('CalendarioAtleta', ['entrenamientos' => $entrenamientos]);
    }
}
