<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // 👈 usamos el facade importado
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $permission
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }

        // 'estado' es boolean si lo casteaste en User::$casts; usa comparación laxa
        if (!$user->estado) {
            Auth::logout(); // 👈 sin backslash
            return redirect()->route('login')
                ->with('error', 'Tu cuenta ha sido deshabilitada.');
        }

        if (!method_exists($user, 'tienePermiso') || !$user->tienePermiso($permission)) {
            abort(403, 'No tienes permiso para realizar esta acción.');
        }

        return $next($request);
    }
}
