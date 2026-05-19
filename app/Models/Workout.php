<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Workout extends Model
{
    use HasFactory;
    protected $fillable = [
        'creator_user_id',
        'club_id',
        'title',
        'workout_date',
        'target_scope',
        'is_template',
        'is_public',
    ];
    protected $casts = [
        'workout_date' => 'date',
        'is_template' => 'boolean',
        'is_public' => 'boolean',
    ];
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }
    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }
    public function exercises(): HasMany
    {
        return $this->hasMany(WorkoutExercise::class)->orderBy('sort_order');
    }
    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workout_assignments');
    }
}
