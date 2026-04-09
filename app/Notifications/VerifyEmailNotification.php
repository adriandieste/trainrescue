<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Auth\Notifications\VerifyEmail as VerifyEmailBase;

class VerifyEmailNotification extends VerifyEmailBase
{

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Verificar tu email - Train & Rescue')
            ->view('emails.verify-email', [
                'user' => $notifiable,
                'actionUrl' => $verificationUrl,
            ]);
    }
}


