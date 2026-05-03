<?php

namespace App\Providers;

use App\Models\Club;
use App\Models\CustomExercise;
use App\Policies\ClubPolicy;
use App\Policies\CustomExercisePolicy;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Gate;
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
        Gate::policy(Club::class, ClubPolicy::class);
        Gate::policy(CustomExercise::class, CustomExercisePolicy::class);

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
