<?php

namespace App\Models;

use App\Models\ClubInvitation;
use App\Models\ClubJoinRequest;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'description', 'logo_path', 'admin_user_id'])]
class Club extends Model
{
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($club) {
            if (!$club->invitation_code) {
                $club->invitation_code = Str::uuid()->toString();
            }
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }


    public function joinRequests(): HasMany
    {
        return $this->hasMany(ClubJoinRequest::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(ClubInvitation::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }
}

