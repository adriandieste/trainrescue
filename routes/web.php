<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ClubController;
use App\Http\Controllers\ClubInvitationController;
use App\Http\Controllers\ExerciseLibraryController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PersonalBestController;
use App\Http\Controllers\WorkoutController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/calendario', [DashboardController::class, 'calendarioAtleta'])->name('calendario.atleta');
    Route::get('/entrenamientos', [DashboardController::class, 'entrenamientosIndex'])->name('entrenamientos.index');

    // Ruta de abandono voluntario disponible para cualquier usuario autenticado
    Route::delete('/clubs/leave', [ClubController::class, 'leave'])->name('clubs.leave');

    Route::middleware('entrenador')->group(function () {
        Route::get('/calendar', [ExerciseLibraryController::class, 'calendar'])->name('calendar.index');
        Route::get('/exercises/library', [ExerciseLibraryController::class, 'index'])->name('exercises.library');
        Route::post('/exercises/custom', [ExerciseLibraryController::class, 'storeCustom'])->name('exercises.custom.store');
        Route::patch('/exercises/custom/{customExercise}', [ExerciseLibraryController::class, 'updateCustom'])->name('exercises.custom.update');
        Route::delete('/exercises/custom/{customExercise}', [ExerciseLibraryController::class, 'destroyCustom'])->name('exercises.custom.destroy');
        Route::post('/workouts', [WorkoutController::class, 'store'])->name('workouts.store');
        Route::patch('/workouts/{workout}', [WorkoutController::class, 'update'])->name('workouts.update');
        Route::delete('/workouts/{workout}', [WorkoutController::class, 'destroy'])->name('workouts.destroy');
        Route::post('/workouts/{workout}/duplicate', [WorkoutController::class, 'duplicate'])->name('workouts.duplicate');
        Route::patch('/workouts/{workout}/reschedule', [WorkoutController::class, 'reschedule'])->name('workouts.reschedule');
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

        Route::post('/clubs/groups', [ClubController::class, 'storeGroup'])->name('clubs.groups.store');
        Route::get('/clubs/groups', [ClubController::class, 'listGroups'])->name('clubs.groups.list');
    });

    Route::post('/club-invitations/{clubInvitation}/accept', [ClubInvitationController::class, 'accept'])->name('club-invitations.accept');
    Route::post('/club-invitations/{clubInvitation}/reject', [ClubInvitationController::class, 'reject'])->name('club-invitations.reject');
    Route::patch('/workouts/{workout}/complete', [DashboardController::class, 'markWorkoutCompleted'])->name('workouts.complete');

    Route::patch('/notificaciones/{id}/leer', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
    Route::patch('/notificaciones/leer-todas', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');

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
    Route::get('/profile/personal-bests', [PersonalBestController::class, 'index'])->name('profile.personal-bests.index');
    Route::patch('/profile/personal-bests/{performanceTest}', [PersonalBestController::class, 'update'])->name('profile.personal-bests.update');
    Route::get('/users/{user}/personal-bests', [PersonalBestController::class, 'index'])->name('users.personal-bests.index');

    Route::post('/onboarding/update-rol', [OnboardingController::class, 'updateRole'])->name('onboarding.update-rol');
});

require __DIR__.'/auth.php';
