<?php

namespace App\Http\Requests;

use App\Models\Club;
use App\Models\ClubInvitation;
use App\Models\ClubJoinRequest;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class GuardarInvitacionClubRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user || $user->rol !== 'entrenador' || ! $user->club_id) {
            return false;
        }

        $club = Club::find($user->club_id);

        return $club?->admin_user_id === $user->id;
    }

    public function rules(): array
    {
        return [
            'invited_user_id' => ['required', 'integer', 'exists:users,id'],
            'search' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $trainer = $this->user();
            $club = $trainer?->club_id ? Club::find($trainer->club_id) : null;
            $invitedUser = User::find($this->input('invited_user_id'));

            if (! $trainer || ! $club || ! $invitedUser) {
                return;
            }

            if ($invitedUser->id === $trainer->id) {
                $validator->errors()->add('invited_user_id', 'No puedes invitarte a ti mismo.');
            }

            if (! in_array($invitedUser->rol, ['socorrista', 'atleta'], true)) {
                $validator->errors()->add('invited_user_id', 'Solo puedes invitar a socorristas.');
            }

            if ($invitedUser->club_id === $club->id) {
                $validator->errors()->add('invited_user_id', 'Este usuario ya forma parte de tu club.');
            }

            if ($invitedUser->club_id !== null && $invitedUser->club_id !== $club->id) {
                $validator->errors()->add('invited_user_id', 'Este usuario ya pertenece a otro club.');
            }

            if (ClubInvitation::where('club_id', $club->id)
                ->where('invited_user_id', $invitedUser->id)
                ->where('status', 'pending')
                ->exists()) {
                $validator->errors()->add('invited_user_id', 'Este usuario ya tiene una invitación pendiente para tu club.');
            }

            if (ClubJoinRequest::where('club_id', $club->id)
                ->where('user_id', $invitedUser->id)
                ->where('status', 'pending')
                ->exists()) {
                $validator->errors()->add('invited_user_id', 'Este usuario ya tiene una solicitud pendiente para este club.');
            }
        }];
    }
}
