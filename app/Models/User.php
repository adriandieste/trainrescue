<?php

namespace App\Models;

use App\Models\ClubInvitation;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Notifications\VerifyEmailNotification;

#[Fillable(['name', 'email', 'password', 'rol', 'club_id', 'avatar'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Enviar notificación de verificación de email.
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmailNotification());
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Club::class);
    }

    public function ownedClubs(): HasMany
    {
        return $this->hasMany(\App\Models\Club::class, 'admin_user_id');
    }

    public function sentClubInvitations(): HasMany
    {
        return $this->hasMany(ClubInvitation::class, 'inviter_user_id');
    }

    public function receivedClubInvitations(): HasMany
    {
        return $this->hasMany(ClubInvitation::class, 'invited_user_id');
    }
}
