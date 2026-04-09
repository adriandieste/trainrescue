<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            $resetUrl = route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]);

            $expireMinutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

            return (new MailMessage)
                ->subject('Restablece tu contraseña - Train & Rescue')
                ->view('emails.reset-password', [
                    'user' => $notifiable,
                    'actionUrl' => $resetUrl,
                    'expireMinutes' => $expireMinutes,
                ]);
        });
    }
}
