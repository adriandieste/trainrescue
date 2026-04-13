<?php
namespace App\Policies;
use App\Models\Club;
use App\Models\User;
class ClubPolicy
{
    /**
     * Solo el entrenador que creó el club (admin) puede disolverlo.
     */
    public function delete(User $user, Club $club): bool
    {
        return $user->rol === 'entrenador' && $user->id === $club->admin_user_id;
    }
}
