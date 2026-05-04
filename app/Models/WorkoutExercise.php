<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutExercise extends Model
{
    use HasFactory;

    protected $fillable = [
        'workout_id',
        'predefined_exercise_id',
        'custom_exercise_id',
        'sort_order',
        'sets',
        'reps',
        'meters',
        'rest_seconds',
    ];

    public function workout(): BelongsTo
    {
        return $this->belongsTo(Workout::class);
    }

    public function predefinedExercise(): BelongsTo
    {
        return $this->belongsTo(PredefinedExercise::class);
    }

    public function customExercise(): BelongsTo
    {
        return $this->belongsTo(CustomExercise::class);
    }
}
