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
        return (new MailMessage)
            ->subject('Nueva solicitud para unirse a ' . $this->joinRequest->club->name)
            ->view('emails.club-join-request', [
                'adminName'      => $notifiable->name,
                'requesterName'  => $this->joinRequest->user->name,
                'requesterEmail' => $this->joinRequest->user->email,
                'clubName'       => $this->joinRequest->club->name,
                'requestMessage' => $this->joinRequest->message,
                'acceptUrl'      => $acceptUrl,
                'rejectUrl'      => $rejectUrl,
            ]);
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
