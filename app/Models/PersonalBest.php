<?php

namespace App\Models;

use App\Support\ChronoTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonalBest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'performance_test_id',
        'time_centiseconds',
        'recorded_at',
    ];

    protected $casts = [
        'recorded_at' => 'date',
    ];

    protected $appends = [
        'formatted_time',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function performanceTest(): BelongsTo
    {
        return $this->belongsTo(PerformanceTest::class);
    }

    public function getFormattedTimeAttribute(): string
    {
        return ChronoTime::fromCentiseconds((int) $this->time_centiseconds);
    }
}

