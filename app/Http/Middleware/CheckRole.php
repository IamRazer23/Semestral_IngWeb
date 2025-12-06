<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // Verificar si el usuario está activo
        if (auth()->user()->estado != 1) {
            auth()->logout();
            return redirect()->route('login')
                ->with('error', 'Tu cuenta ha sido deshabilitada. Contacta al administrador.');
        }

        // Convertir string de roles a array
        $allowedRoles = explode(',', $roles);

        // Verificar si el usuario tiene uno de los roles permitidos
        $userRole = auth()->user()->rol?->nombre;

        if (!$userRole || !in_array($userRole, $allowedRoles)) {
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }

        return $next($request);
    }
}