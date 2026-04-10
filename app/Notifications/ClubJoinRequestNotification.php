<?php
namespace App\Notifications;
use App\Models\ClubJoinRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
class ClubJoinRequestNotification extends Notification
{
    use Queueable;
    public function __construct(private ClubJoinRequest $joinRequest)
    {
        //
    }
    public function via(object $notifiable): array
    {
        return ['mail'];
    }
    public function toMail(object $notifiable): MailMessage
    {
        $acceptUrl = URL::signedRoute('clubs.requests.accept', ['joinRequest' => $this->joinRequest->id]);
        $rejectUrl = URL::signedRoute('clubs.requests.reject', ['joinRequest' => $this->joinRequest->id]);
        $mail = (new MailMessage)
            ->subject('Nueva solicitud para unirse a ' . $this->joinRequest->club->name)
            ->greeting('Hola, ' . $notifiable->name . '!')
            ->line('El entrenador **' . $this->joinRequest->user->name . '** ha solicitado unirse a tu club **' . $this->joinRequest->club->name . '**.');
        if ($this->joinRequest->message) {
            $mail->line('Mensaje del solicitante: ' . $this->joinRequest->message);
        }
        $mail->action('Aceptar Solicitud', $acceptUrl)
             ->line('Para rechazar la solicitud accede al siguiente enlace:')
             ->line($rejectUrl)
             ->line('Los enlaces caducan pasadas 72 horas.')
             ->salutation('Saludos, el equipo de Train & Rescue.');
        return $mail;
    }
    public function toArray(object $notifiable): array
    {
        return [
            'join_request_id' => $this->joinRequest->id,
            'user_name'       => $this->joinRequest->user->name,
            'club_name'       => $this->joinRequest->club->name,
        ];
    }
}
