<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EntrenadorMiddleware
{
    /**
     * Permite el acceso únicamente a usuarios con rol 'entrenador'.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || $request->user()->rol !== 'entrenador') {
            abort(403, 'Acceso denegado: solo los entrenadores pueden realizar esta acción.');
        }

        return $next($request);
    }
}

