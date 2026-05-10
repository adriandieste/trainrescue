<?php

namespace App\Http\Middleware;

use App\Models\ClubInvitation;
use App\Notifications\WorkoutAsignadoNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => fn () => $request->user()?->load('club'),
            ],
            'mustSelectRole' => fn () => $request->user() !== null && $request->user()->rol === null,
            'notifications' => fn () => $this->resolveNotifications($request),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
            ],
        ];
    }

    /**
     * @return array{clubInvitations: array, workoutNotifications: array, totalCount: int}
     */
    private function resolveNotifications(Request $request): array
    {
        $user = $request->user();

        if (! $user || ! in_array($user->rol, ['socorrista', 'atleta'], true)) {
            return [
                'clubInvitations'      => [],
                'clubInvitationsCount' => 0,
                'workoutNotifications' => [],
                'totalCount'           => 0,
            ];
        }

        $clubInvitations = ClubInvitation::with(['club', 'inviter'])
            ->where('invited_user_id', $user->id)
            ->where('status', 'pending')
            ->latest()
            ->get()
            ->map(fn (ClubInvitation $invitation) => [
                'id' => $invitation->id,
                'club' => [
                    'id'      => $invitation->club->id,
                    'name'    => $invitation->club->name,
                    'logo_url' => $invitation->club->logo_path ? asset('storage/' . $invitation->club->logo_path) : null,
                ],
                'trainer' => [
                    'id'    => $invitation->inviter->id,
                    'name'  => $invitation->inviter->name,
                    'email' => $invitation->inviter->email,
                ],
                'created_at' => $invitation->created_at->diffForHumans(),
            ])
            ->values()
            ->all();

        $workoutNotifications = [];
        if (Schema::hasTable('notifications')) {
            $workoutNotifications = $user->unreadNotifications()
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

        $totalCount = count($clubInvitations) + count($workoutNotifications);

        return [
            'clubInvitations'      => $clubInvitations,
            'clubInvitationsCount' => count($clubInvitations),
            'workoutNotifications' => $workoutNotifications,
            'totalCount'           => $totalCount,
        ];
    }
}
