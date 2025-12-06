<?php

namespace App\Providers;

use App\Models\Carrito;
use App\Models\Categoria;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Compartir categorías en todas las vistas
        View::composer('*', function ($view) {
            // Categorías activas para el menú
            $categoriasMenu = Categoria::activas()
                ->orderBy('nombre')
                ->get();
            
            $view->with('categoriasMenu', $categoriasMenu);
        });
        // Compartir conteo del carrito solo si el usuario está autenticado
        View::composer('*', function ($view) {
            $carritoConteo = 0;
            
            if (Auth::check() && Auth::user()->esCliente()) {
                $carritoConteo = Carrito::where('user_id', Auth::id())->sum('cantidad');
            }
            
            $view->with('carritoConteo', $carritoConteo);
        });

        // Compartir información del usuario actual
        View::composer('*', function ($view) {
            $usuario = Auth::user();
            
            $view->with('usuario', $usuario);
        });
    }
}