<?php

use App\Http\Controllers\AutoparteController;
use App\Http\Controllers\CarritoController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EstadisticaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas Públicas
|--------------------------------------------------------------------------
*/

// Página de inicio - Redirige al catálogo
Route::get('/', function () {
    return redirect()->route('catalogo.index');
});

// Catálogo Público (accesible sin autenticación)
Route::controller(CatalogoController::class)->group(function () {
    Route::get('/catalogo', 'index')->name('catalogo.index');
    Route::get('/catalogo/{autoparte}', 'show')->name('catalogo.show');
    Route::get('/categoria/{categoria}', 'categoria')->name('catalogo.categoria');
});

/*
|--------------------------------------------------------------------------
| Rutas de Autenticación (Laravel Breeze)
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| Rutas Protegidas (Requieren Autenticación)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {
    
    // Dashboard General (redirige según rol)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Perfil de Usuario
    Route::controller(ProfileController::class)->group(function () {
        Route::get('/profile', 'edit')->name('profile.edit');
        Route::patch('/profile', 'update')->name('profile.update');
        Route::delete('/profile', 'destroy')->name('profile.destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Rutas del CARRITO (Solo Clientes)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:Cliente'])->prefix('carrito')->name('carrito.')->group(function () {
        Route::get('/', [CarritoController::class, 'index'])->name('index');
        Route::post('/agregar/{autoparte}', [CarritoController::class, 'agregar'])->name('agregar');
        Route::patch('/{carrito}', [CarritoController::class, 'actualizar'])->name('actualizar');
        Route::delete('/{carrito}', [CarritoController::class, 'eliminar'])->name('eliminar');
        Route::delete('/vaciar/todo', [CarritoController::class, 'vaciar'])->name('vaciar');
        Route::get('/conteo/items', [CarritoController::class, 'conteo'])->name('conteo');
    });

    /*
    |--------------------------------------------------------------------------
    | Rutas de COMPRA (Solo Clientes)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:Cliente'])->prefix('compra')->name('compra.')->group(function () {
        Route::get('/resumen', [CompraController::class, 'resumen'])->name('resumen');
        Route::post('/procesar', [CompraController::class, 'procesar'])->name('procesar');
        Route::get('/factura/{factura}', [CompraController::class, 'factura'])->name('factura');
        Route::get('/factura/{factura}/pdf', [CompraController::class, 'descargarPDF'])->name('factura.pdf');
        Route::get('/historial', [CompraController::class, 'historial'])->name('historial');
    });

    /*
    |--------------------------------------------------------------------------
    | Rutas de ADMINISTRADOR
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:Administrador'])->group(function () {
        
        // Gestión de Usuarios
        Route::resource('usuarios', UsuarioController::class);
        
        // Estadísticas
        Route::get('/estadisticas', [EstadisticaController::class, 'index'])->name('estadisticas.index');
        
        // Reportes
        Route::prefix('reportes')->name('reportes.')->group(function () {
            Route::get('/inventario/excel', [ReporteController::class, 'inventarioExcel'])->name('inventario.excel');
            Route::get('/ventas/excel', [ReporteController::class, 'ventasExcel'])->name('ventas.excel');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Rutas de ADMINISTRADOR y OPERADOR
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:Administrador,Operador'])->group(function () {
        
        // Gestión de Categorías
        Route::resource('categorias', CategoriaController::class);
        
        // Gestión de Autopartes
        Route::resource('autopartes', AutoparteController::class);
        
        // Ajuste de Stock
        Route::post('/autopartes/{autoparte}/ajustar-stock', [AutoparteController::class, 'ajustarStock'])
            ->name('autopartes.ajustar-stock');
    });
});