<?php

namespace App\Policies;

use App\Models\CustomExercise;
use App\Models\User;

class CustomExercisePolicy
{
    public function update(User $user, CustomExercise $customExercise): bool
    {
        return $user->id === $customExercise->user_id;
    }

    public function delete(User $user, CustomExercise $customExercise): bool
    {
        return $user->id === $customExercise->user_id;
    }
}

