<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarInvitacionClubRequest;
use App\Models\ClubInvitation;
use App\Models\ClubJoinRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class ClubInvitationController extends Controller
{
    public function store(GuardarInvitacionClubRequest $request): RedirectResponse
    {
        $trainer = $request->user();

        ClubInvitation::create([
            'club_id'          => $trainer->club_id,
            'inviter_user_id'  => $trainer->id,
            'invited_user_id'  => $request->validated('invited_user_id'),
            'status'           => 'pending',
        ]);

        $source = $request->input('source', 'members');
        $search = trim((string) $request->input('search', ''));
        $params = array_filter(['search' => $search ?: null]);

        if ($source === 'dashboard') {
            return redirect()
                ->route('dashboard', $params)
                ->with('success', 'Invitación enviada correctamente.');
        }

        return redirect()
            ->route('clubs.members.index', $params)
            ->with('success', 'Invitación enviada correctamente.');
    }

    public function accept(ClubInvitation $clubInvitation): RedirectResponse
    {
        $user = auth()->user();

        if (! $user || $clubInvitation->invited_user_id !== $user->id) {
            abort(403, 'No tienes permiso para aceptar esta invitación.');
        }

        if ($user->club_id !== null) {
            ClubInvitation::where('invited_user_id', $user->id)->delete();

            return redirect()->route('dashboard')
                ->with('error', 'Ya perteneces a un club. Se han limpiado tus invitaciones pendientes.');
        }

        DB::transaction(function () use ($clubInvitation, $user) {
            User::whereKey($user->id)->update(['club_id' => $clubInvitation->club_id]);
            ClubInvitation::where('invited_user_id', $user->id)->delete();
            ClubJoinRequest::where('user_id', $user->id)->delete();
        });

        return redirect()->route('dashboard')
            ->with('success', 'Has aceptado la invitación y ya formas parte del club.');
    }

    public function reject(ClubInvitation $clubInvitation): RedirectResponse
    {
        $user = auth()->user();

        if (! $user || $clubInvitation->invited_user_id !== $user->id) {
            abort(403, 'No tienes permiso para rechazar esta invitación.');
        }

        $clubInvitation->delete();

        return redirect()->route('dashboard')
            ->with('success', 'Invitación rechazada correctamente.');
    }
}
