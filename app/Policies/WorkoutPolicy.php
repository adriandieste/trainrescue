<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workout;

class WorkoutPolicy
{
    public function update(User $user, Workout $workout): bool
    {
        if ($user->rol !== 'entrenador') {
            return false;
        }

        if ($workout->creator_user_id === $user->id) {
            return true;
        }

        if (! $user->club_id || ! $workout->club_id || $user->club_id !== $workout->club_id) {
            return false;
        }

        return $user->club?->admin_user_id === $user->id;
    }
}

