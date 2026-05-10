<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    /**
     * Persiste el rol elegido por el usuario tras el primer inicio de sesión
     * y redirige a su Dashboard correspondiente.
     */
    public function updateRole(Request $request): RedirectResponse
    {
        $request->validate([
            'rol' => ['required', 'string', 'in:socorrista,entrenador'],
        ], [
            'rol.required' => 'Debes seleccionar un rol.',
            'rol.in'       => 'El rol seleccionado no es válido.',
        ]);

        $request->user()->update(['rol' => $request->rol]);

        return redirect()->route('dashboard');
    }
}

