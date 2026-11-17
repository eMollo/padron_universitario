<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Corta la sesión de un usuario que fue desactivado mientras estaba logueado.
 * El login ya filtra por is_active, pero eso solo se revisa al iniciar sesión.
 * Debe ir DESPUÉS de 'auth' en la lista de middlewares.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && !$user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Usuario desactivado'], 401);
            }

            return redirect('/login');
        }

        return $next($request);
    }
}