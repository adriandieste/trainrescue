<?php

namespace App\Http\Controllers;

use App\Support\ClubTimeViewData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClubTimeController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user()->loadMissing('club.groups');

        if (! $user->club) {
            return redirect()->route('dashboard')->with('error', 'Necesitas pertenecer a un club para consultar el panel de tiempos.');
        }

        return Inertia::render('Clubes/' . 'PanelTiempos', ClubTimeViewData::forClub($user->club));
    }
}


