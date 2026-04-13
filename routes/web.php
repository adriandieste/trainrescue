<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ClubController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', function () {
        $user = auth()->user()->load('club');

        if ($user->rol === 'entrenador') {
            $pendingCount = 0;
            if ($user->club_id && $user->club?->admin_user_id === $user->id) {
                $pendingCount = \App\Models\ClubJoinRequest::where('club_id', $user->club_id)
                    ->where('status', 'pending')
                    ->count();
            }
            return Inertia::render('Dashboard', ['pendingRequestsCount' => $pendingCount]);
        }

        if ($user->rol === 'atleta') {
            return Inertia::render('DashboardAtleta');
        }

        abort(403, 'Acceso denegado: Rol no reconocido.');
    })->name('dashboard');

    Route::middleware('entrenador')->group(function () {
        Route::get('/clubs/create', [ClubController::class, 'create'])->name('clubs.create');
        Route::post('/clubs', [ClubController::class, 'store'])->name('clubs.store');
        Route::get('/clubs/{club}/edit', [ClubController::class, 'edit'])->name('clubs.edit');
        Route::patch('/clubs/{club}', [ClubController::class, 'update'])->name('clubs.update');
        Route::delete('/clubs/{club}', [ClubController::class, 'destroy'])->name('clubs.destroy');

        Route::get('/clubs/available', [ClubController::class, 'getAvailableClubs'])->name('clubs.available');
        Route::post('/clubs/join-request', [ClubController::class, 'joinRequest'])->name('clubs.join-request');

        Route::get('/clubs/join-requests', [ClubController::class, 'joinRequests'])->name('clubs.join-requests.index');
        Route::post('/clubs/join-requests/{joinRequest}/accept', [ClubController::class, 'acceptJoinRequest'])->name('clubs.join-requests.accept');
        Route::post('/clubs/join-requests/{joinRequest}/reject', [ClubController::class, 'rejectJoinRequest'])->name('clubs.join-requests.reject');
    });

});

Route::get('/clubs/requests/{joinRequest}/accept', [ClubController::class, 'acceptJoinRequest'])
    ->name('clubs.requests.accept')
    ->middleware('signed');
Route::get('/clubs/requests/{joinRequest}/reject', [ClubController::class, 'rejectJoinRequest'])
    ->name('clubs.requests.reject')
    ->middleware('signed');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
