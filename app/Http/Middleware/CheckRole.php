<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        // Verificar si el usuario está autenticado
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // Verificar si el usuario está activo
        if (Auth::user()->estado != 1) {
            Auth::logout();
            return redirect()->route('login')
                ->with('error', 'Tu cuenta ha sido deshabilitada. Contacta al administrador.');
        }

        // Convertir string de roles a array
        $allowedRoles = explode(',', $roles);
        // Verificar si el usuario tiene uno de los roles permitidos
        $userRole = Auth::user()->rol?->nombre;

        if (!$userRole || !in_array($userRole, $allowedRoles)) {
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }

        return $next($request);
    }
}