<?php

namespace App\Http\Controllers;

use App\Models\ClubInvitation;
use App\Models\ClubJoinRequest;
use App\Models\User;
use Illuminate\Http\Request;
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

            return Inertia::render('DashboardAtleta', [
                'invitationsTitle' => 'Invitaciones',
                'pendingInvitations' => $pendingInvitations,
            ]);
        }

        abort(403, 'Acceso denegado: Rol no reconocido.');
    }
}


