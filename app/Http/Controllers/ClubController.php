<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClubRequest;
use App\Http\Requests\JoinClubRequest;
use App\Models\Club;
use App\Models\ClubJoinRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ClubController extends Controller
{
    /**
     * Show the form for creating a new club.
     */
    public function create(): Response
    {
        return Inertia::render('Clubs/Create');
    }

    /**
     * Store a newly created club in storage.
     */
    public function store(StoreClubRequest $request): RedirectResponse
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
     * Get available clubs for joining (sin club admin).
     */
    public function getAvailableClubs(): JsonResponse
    {
        $clubs = Club::all()->map(function ($club) {
            return [
                'id' => $club->id,
                'name' => $club->name,
                'description' => $club->description,
                'logo_path' => $club->logo_path ? "/storage/{$club->logo_path}" : null,
            ];
        });

        return response()->json($clubs);
    }

    /**
     * Store a join request for a club.
     */
    public function joinRequest(JoinClubRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $existingRequest = ClubJoinRequest::where('user_id', $request->user()->id)
            ->where('club_id', $validated['club_id'])
            ->where('status', 'pending')
            ->first();

        if ($existingRequest) {
            return back()->with('error', 'Ya tienes una solicitud pendiente para este club.');
        }

        $joinRequest = ClubJoinRequest::create([
            'user_id' => $request->user()->id,
            'club_id' => $validated['club_id'],
            'message' => $validated['message'] ?? null,
        ]);

        $club = Club::with('admin')->find($validated['club_id']);
        $admin = $club?->admin;

        if ($admin) {
            $admin->notify(new \App\Notifications\ClubJoinRequestNotification($joinRequest));
        }

        return redirect()->route('dashboard')->with('success', 'Solicitud de unirse al club enviada. El administrador recibirá un correo para aceptarla o rechazarla.');
    }

    /**
     * Mostrar solicitudes pendientes del club del admin.
     */
    public function joinRequests(): Response|RedirectResponse
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

        DB::transaction(function () use ($joinRequest) {
            $joinRequest->update(['status' => 'accepted']);
            $joinRequest->user->update(['club_id' => $joinRequest->club_id]);
        });

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
}
