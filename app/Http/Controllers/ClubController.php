<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarClubRequest;
use App\Http\Requests\SolicitudUnionClubRequest;
use App\Http\Requests\ActualizarClubRequest;
use App\Models\Club;
use App\Models\ClubInvitation;
use App\Models\ClubJoinRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ClubController extends Controller
{
    /**
     * Show the form for creating a new club.
     */
    public function crear(): Response
    {
        return Inertia::render('Clubes/Crear');
    }

    /**
     * Show the form for editing the specified club.
     */
    public function editar(Club $club): Response
    {
        $this->autorizarAdministracionClub($club);

        return Inertia::render('Clubes/Editar', [
            'club' => [
                'id' => $club->id,
                'name' => $club->name,
                'admin_user_id' => $club->admin_user_id,
                'description' => $club->description,
                'logo_path' => $club->logo_path,
                'logo_url' => $club->logo_path ? asset('storage/' . $club->logo_path) : null,
            ],
        ]);
    }

    /**
     * Store a newly created club in storage.
     */
    public function guardar(GuardarClubRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['admin_user_id'] = $request->user()->id;

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('clubs/logos', 'public');
            $validated['logo_path'] = $logoPath;
        }

        $club = Club::create($validated);

        $request->user()->update(['club_id' => $club->id]);

        return redirect()->route('dashboard')->with('success', 'Club creado exitosamente.');
    }

    /**
     * Update the specified club in storage.
     */
    public function actualizar(ActualizarClubRequest $request, Club $club): RedirectResponse
    {
        $validated = $request->safe()->only(['name', 'description']);
        $oldLogoPath = $club->logo_path;
        $newLogoPath = null;

        if ($request->hasFile('logo')) {
            $newLogoPath = $request->file('logo')->store('clubs/logos', 'public');
            $validated['logo_path'] = $newLogoPath;
        }

        $club->update($validated);

        if ($newLogoPath && $oldLogoPath) {
            Storage::disk('public')->delete($oldLogoPath);
        }

        return redirect()->route('dashboard')->with('success', 'Club actualizado exitosamente.');
    }

    /**
     * Get available clubs for joining (sin club admin).
     */
    public function obtenerClubesDisponibles(): JsonResponse
    {
        $clubs = Club::all()->map(function ($club) {
            return [
                'id' => $club->id,
                'name' => $club->name,
                'admin_user_id' => $club->admin_user_id,
                'description' => $club->description,
                'logo_path' => $club->logo_path ? "/storage/{$club->logo_path}" : null,
            ];
        });

        return response()->json($clubs);
    }

    /**
     * Store a join request for a club.
     */
    public function solicitarUnion(SolicitudUnionClubRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $solicitudExistente = ClubJoinRequest::where('user_id', $request->user()->id)
            ->where('club_id', $validated['club_id'])
            ->first();

        if ($solicitudExistente) {
            if ($solicitudExistente->status === 'pending') {
                return back()->with('error', 'Ya tienes una solicitud pendiente para este club.');
            }
            if ($solicitudExistente->status === 'accepted') {
                return back()->with('error', 'Ya eres miembro de este club.');
            }
            $solicitudExistente->update([
                'status'  => 'pending',
                'message' => $validated['message'] ?? null,
            ]);
            $solicitudUnion = $solicitudExistente;
        } else {
            $solicitudUnion = ClubJoinRequest::create([
                'user_id' => $request->user()->id,
                'club_id' => $validated['club_id'],
                'message' => $validated['message'] ?? null,
            ]);
        }

        $club = Club::with('administrador')->find($validated['club_id']);
        $admin = $club?->administrador;

        if ($admin) {
            $admin->notify(new \App\Notifications\ClubJoinRequestNotification($solicitudUnion));
        }

        return redirect()->route('dashboard')->with('success', 'Solicitud de unirse al club enviada. El administrador recibirá un correo para aceptarla o rechazarla.');
    }

    /**
     * Mostrar solicitudes pendientes del club del admin.
     */
    {
        $user = auth()->user();

        if (! $user->club_id) {
            return redirect()->route('dashboard');
        }

        $club = Club::find($user->club_id);

        if (! $club || $club->admin_user_id !== $user->id) {
            abort(403, 'No tienes permiso para ver las solicitudes de este club.');
        }

        $pendingRequests = ClubJoinRequest::with('user')
            ->where('club_id', $club->id)
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($req) => [
                'id'         => $req->id,
                'user'       => [
                    'id'     => $req->user->id,
                    'name'   => $req->user->name,
                    'email'  => $req->user->email,
                    'avatar' => $req->user->avatar ? asset('storage/' . $req->user->avatar) : null,
                ],
                'message'    => $req->message,
                'created_at' => $req->created_at->diffForHumans(),
            ]);

        return Inertia::render('Clubs/JoinRequests', [
            'club'     => ['id' => $club->id, 'name' => $club->name],
            'requests' => $pendingRequests,
        ]);
    }

    /**
     * Listado paginado de miembros y buscador de socorristas para invitar.
     */
    public function members(Request $request): Response
    {
        $club = $this->getManagedClub();
        $search = trim((string) $request->query('search', ''));

        $members = $club->users()
            ->orderBy('name')
            ->paginate(10)
            ->through(fn (User $member) => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'avatar_url' => $member->avatar ? asset('storage/' . $member->avatar) : null,
                'role' => $member->rol,
                'role_label' => $this->formatRoleLabel($member->rol),
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

        $searchResults = collect();

        if ($search !== '') {
            $searchResults = User::query()
                ->where('rol', 'atleta')
                ->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })
                ->orderBy('name')
                ->limit(10)
                ->get()
                ->map(function (User $candidate) use ($club, $pendingInvitationUserIds, $pendingJoinRequestUserIds) {
                    $statusLabel = 'Disponible';
                    $canInvite = true;

                    if ($candidate->club_id === $club->id) {
                        $statusLabel = 'Ya pertenece a tu club';
                        $canInvite = false;
                    } elseif ($candidate->club_id !== null) {
                        $statusLabel = 'Ya pertenece a otro club';
                        $canInvite = false;
                    } elseif (in_array($candidate->id, $pendingInvitationUserIds, true)) {
                        $statusLabel = 'Invitación pendiente';
                        $canInvite = false;
                    } elseif (in_array($candidate->id, $pendingJoinRequestUserIds, true)) {
                        $statusLabel = 'Solicitud pendiente';
                        $canInvite = false;
                    }

                    return [
                        'id' => $candidate->id,
                        'name' => $candidate->name,
                        'email' => $candidate->email,
                        'avatar_url' => $candidate->avatar ? asset('storage/' . $candidate->avatar) : null,
                        'can_invite' => $canInvite,
                        'status_label' => $statusLabel,
                    ];
                })
                ->values();
        }

        $pendingInvitations = ClubInvitation::with('invitedUser')
            ->where('club_id', $club->id)
            ->where('status', 'pending')
            ->latest()
            ->get()
            ->map(fn (ClubInvitation $invitation) => [
                'id' => $invitation->id,
                'invited_user' => [
                    'id' => $invitation->invitedUser->id,
                    'name' => $invitation->invitedUser->name,
                    'email' => $invitation->invitedUser->email,
                    'avatar_url' => $invitation->invitedUser->avatar ? asset('storage/' . $invitation->invitedUser->avatar) : null,
                ],
                'status' => $invitation->status,
                'created_at' => $invitation->created_at->diffForHumans(),
            ]);

        return Inertia::render('Clubs/Members', [
            'club' => [
                'id' => $club->id,
                'name' => $club->name,
                'admin_user_id' => $club->admin_user_id,
            ],
            'filters' => [
                'search' => $search,
            ],
            'searchResults' => $searchResults,
            'pendingInvitations' => $pendingInvitations,
            'members' => $members,
        ]);
    }

    /**
     * Aceptar solicitud de unión (firmado para enlaces de email + autenticado para UI).
     */
    public function acceptJoinRequest(ClubJoinRequest $joinRequest): RedirectResponse
    {
        if (! request()->hasValidSignature()) {
            $this->authorizeJoinRequestAction($joinRequest);
        }

        if ($joinRequest->status !== 'pending') {
            $msg = 'Esta solicitud ya fue procesada anteriormente.';
            return auth()->check()
                ? redirect()->route('dashboard')->with('error', $msg)
                : redirect()->route('login')->with('error', $msg);
        }

        $club = $joinRequest->club()->with('admin')->first();

        if (! $club instanceof Club) {
            abort(404, 'No se encontró el club asociado a la solicitud.');
        }

        DB::transaction(function () use ($joinRequest) {
            $joinRequest->update(['status' => 'accepted']);
            $joinRequest->user->update(['club_id' => $joinRequest->club_id]);
        });

        $clubForNotification = $club;
        $joinRequest->user->notify(new \App\Notifications\ClubWelcomeNotification($clubForNotification));

        $msg = '¡Solicitud aceptada! ' . $joinRequest->user->name . ' ahora es miembro del club.';
        return auth()->check()
            ? redirect()->route('dashboard')->with('success', $msg)
            : redirect()->route('login')->with('status', $msg);
    }

    /**
     * Rechazar solicitud de unión.
     */
    public function rejectJoinRequest(ClubJoinRequest $joinRequest): RedirectResponse
    {
        if (! request()->hasValidSignature()) {
            $this->authorizeJoinRequestAction($joinRequest);
        }

        if ($joinRequest->status !== 'pending') {
            $msg = 'Esta solicitud ya fue procesada anteriormente.';
            return auth()->check()
                ? redirect()->route('dashboard')->with('error', $msg)
                : redirect()->route('login')->with('error', $msg);
        }

        $joinRequest->update(['status' => 'rejected']);

        $msg = 'Solicitud de ' . $joinRequest->user->name . ' rechazada.';
        return auth()->check()
            ? redirect()->route('clubs.join-requests.index')->with('success', $msg)
            : redirect()->route('login')->with('status', $msg);
    }

    /**
     * Verifica que el usuario autenticado es admin del club de la solicitud.
     */
    private function authorizeJoinRequestAction(ClubJoinRequest $joinRequest): void
    {
        $user = auth()->user();
        $club = $joinRequest->club;

        if (! $user || ! $club || $club->admin_user_id !== $user->id) {
            abort(403, 'No tienes permiso para gestionar esta solicitud.');
        }
    }


    /**
     * Disuelve el club: desvincula usuarios, elimina el logo y borra el registro.
     */
    public function destroy(Request $request, Club $club): RedirectResponse
    {
        Gate::authorize('eliminar', $club);

        $request->validate([
            'confirm_name' => ['required', 'string', Rule::in([$club->name])],
        ], [
            'confirm_name.in' => 'Debes escribir el nombre exacto del club para confirmar.',
        ]);

        $logoPath = $club->logo_path;

        if ($logoPath) {
            Storage::disk('public')->delete($logoPath);
        }

        DB::transaction(function () use ($club) {
            User::where('club_id', $club->id)->update(['club_id' => null]);
            $club->joinRequests()->delete();
            $club->delete();
        });

        return redirect()->route('dashboard')
            ->with('success', 'El club ha sido disuelto correctamente. Los socorristas han quedado desvinculados.');
    }
    /**
     * Verifica que el usuario autenticado puede administrar el club indicado.
     */
    private function authorizeClubAdministration(Club $club): void
    {
        $user = auth()->user();

        if (! $user || $user->rol !== 'entrenador' || $user->club_id !== $club->id || $club->admin_user_id !== $user->id) {
            abort(403, 'No tienes permiso para administrar este club.');
        }
    }

    private function getManagedClub(): Club
    {
        $user = auth()->user();

        if (! $user || ! $user->club_id) {
            abort(403, 'No tienes un club para administrar.');
        }

        $club = Club::find($user->club_id);

        if (! $club || $club->admin_user_id !== $user->id) {
            abort(403, 'No tienes permiso para administrar este club.');
        }

        return $club;
    }


    public function removeMember(Request $request, User $user): RedirectResponse
    {
        $club = $this->getManagedClub();

        if ($user->club_id !== $club->id) {
            abort(403, 'Este usuario no pertenece a tu club.');
        }

        if ($user->id === $club->admin_user_id) {
            return redirect()->route('dashboard')
                ->with('error', 'No puedes expulsar al administrador del club.');
        }

        $user->club_id = null;
        $user->save();

        return redirect()->route('dashboard')
            ->with('success', "{$user->name} ha sido expulsado del club correctamente.");
    }

    private function formatRoleLabel(string $role): string
    {
        return $role === 'entrenador' ? 'Entrenador' : 'Socorrista';
    }
}
