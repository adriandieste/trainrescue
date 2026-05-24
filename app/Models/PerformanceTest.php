<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PerformanceTest extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'structure',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function personalBests(): HasMany
    {
        return $this->hasMany(PersonalBest::class);
    }
}

