<?php

namespace App\Notifications;

use App\Models\Club;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class ClubWelcomeNotification extends Notification
{
    use Queueable;

    public function __construct(private Club $club) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->club->loadMissing('admin');

        return (new MailMessage)
            ->subject('¡Bienvenido/a a ' . $this->club->name . '!')
            ->view('emails.club-welcome', [
                'userName'         => $notifiable->name,
                'clubName'         => $this->club->name,
                'clubDescription'  => $this->club->description,
                'adminName'        => $this->club->administrador?->name ?? 'el entrenador',
                'dashboardUrl'     => URL::to(route('dashboard')),
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'club_id'   => $this->club->id,
            'club_name' => $this->club->name,
        ];
    }
}

