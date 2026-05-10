<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')
            ->redirect();
    }

    public function handleGoogleCallback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable) {
            return redirect()
                ->route('login')
                ->with('error', 'No se pudo completar el acceso con Google. Intentalo de nuevo.');
        }

        $email = $googleUser->getEmail();

        if (! $email) {
            return redirect()
                ->route('login')
                ->with('error', 'Tu cuenta de Google no tiene un correo valido para iniciar sesion.');
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            if (! $user->google_id) {
                $user->google_id = $googleUser->getId();
            }

            if (! $user->email_verified_at) {
                $user->email_verified_at = now();
            }

            if ($googleUser->getAvatar()) {
                $user->google_avatar = $googleUser->getAvatar();
            }

            $user->save();
        } else {
            $user = User::create([
                'name' => $googleUser->getName() ?: $googleUser->getNickname() ?: 'Usuario Google',
                'email' => $email,
                'password' => Str::random(40),
                'google_id' => $googleUser->getId(),
                'google_avatar' => $googleUser->getAvatar(),
                'email_verified_at' => now(),
                'rol' => null,
            ]);
        }

        Auth::login($user, true);
        request()->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }
}

