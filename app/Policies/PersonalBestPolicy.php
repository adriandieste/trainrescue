<?php

namespace App\Policies;

use App\Models\PersonalBest;
use App\Models\User;

class PersonalBestPolicy
{
    public function viewForUser(User $user, User $target): bool
    {
        return $user->is($target) || $user->rol === 'entrenador';
    }

    public function createForUser(User $user, User $target): bool
    {
        return $user->is($target);
    }

    public function update(User $user, PersonalBest $personalBest): bool
    {
        return $user->id === $personalBest->user_id;
    }
}

