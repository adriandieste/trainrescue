<?php

namespace App\Notifications;

use App\Models\Workout;
use Illuminate\Notifications\Notification;

class WorkoutAsignadoNotification extends Notification
{
    public function __construct(private readonly Workout $workout) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'workout_id'             => $this->workout->id,
            'workout_title'          => $this->workout->title,
            'workout_date'           => $this->workout->workout_date?->format('Y-m-d'),
            'workout_date_formatted' => $this->workout->workout_date?->format('d/m/Y'),
            'club_id'                => $this->workout->club_id,
        ];
    }
}

