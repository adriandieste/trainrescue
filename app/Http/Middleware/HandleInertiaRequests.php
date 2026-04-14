<?php

namespace App\Http\Middleware;

use App\Models\ClubInvitation;
use Illuminate\Http\Request;
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
            'notifications' => fn () => $this->resolveNotifications($request),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
            ],
        ];
    }

    /**
     * @return array{clubInvitations: array<int, array<string, mixed>>, clubInvitationsCount: int}
     */
    private function resolveNotifications(Request $request): array
    {
        $user = $request->user();

        if (! $user || $user->rol !== 'atleta') {
            return [
                'clubInvitations' => [],
                'clubInvitationsCount' => 0,
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
            ])
            ->values()
            ->all();

        return [
            'clubInvitations' => $clubInvitations,
            'clubInvitationsCount' => count($clubInvitations),
        ];
    }
}
