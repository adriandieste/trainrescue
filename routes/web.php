<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ClubController;
use App\Http\Controllers\ClubInvitationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Ruta de abandono voluntario disponible para cualquier usuario autenticado
    Route::delete('/clubs/leave', [ClubController::class, 'leave'])->name('clubs.leave');

    Route::middleware('entrenador')->group(function () {
        Route::get('/clubs/create', [ClubController::class, 'crear'])->name('clubs.create');
        Route::post('/clubs', [ClubController::class, 'guardar'])->name('clubs.store');
        Route::get('/clubs/{club}/edit', [ClubController::class, 'editar'])->name('clubs.edit');
        Route::patch('/clubs/{club}', [ClubController::class, 'actualizar'])->name('clubs.update');
        Route::delete('/clubs/{club}', [ClubController::class, 'destroy'])->name('clubs.destroy');
        Route::delete('/clubs/members/{user}', [ClubController::class, 'removeMember'])->name('clubs.members.remove');
        Route::patch('/clubs/members/{user}/role', [ClubController::class, 'updateRole'])->name('clubs.members.update-role');
        Route::post('/clubs/invitations', [ClubInvitationController::class, 'store'])->name('club-invitations.store');

        Route::get('/clubs/available', [ClubController::class, 'obtenerClubesDisponibles'])->name('clubs.available');
        Route::post('/clubs/join-request', [ClubController::class, 'solicitarUnion'])->name('clubs.join-request');

        Route::get('/clubs/join-requests', [ClubController::class, 'joinRequests'])->name('clubs.join-requests.index');
        Route::post('/clubs/join-requests/{joinRequest}/accept', [ClubController::class, 'acceptJoinRequest'])->name('clubs.join-requests.accept');
        Route::post('/clubs/join-requests/{joinRequest}/reject', [ClubController::class, 'rejectJoinRequest'])->name('clubs.join-requests.reject');
    });

    Route::post('/club-invitations/{clubInvitation}/accept', [ClubInvitationController::class, 'accept'])->name('club-invitations.accept');
    Route::post('/club-invitations/{clubInvitation}/reject', [ClubInvitationController::class, 'reject'])->name('club-invitations.reject');

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
