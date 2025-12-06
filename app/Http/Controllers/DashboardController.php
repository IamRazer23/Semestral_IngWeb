<?php

namespace App\Http\Controllers;

use App\Models\Autoparte;
use App\Models\Categoria;
use App\Models\Factura;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Mostrar el dashboard según el rol del usuario
     */
    public function index()
    {
        $user = auth()->user();

        if ($user->esAdministrador()) {
            return $this->dashboardAdmin();
        } elseif ($user->esOperador()) {
            return $this->dashboardOperador();
        } elseif ($user->esCliente()) {
            return $this->dashboardCliente();
        }

        return redirect()->route('catalogo.index');
    }

    /**
     * Dashboard para Administrador
     */
    private function dashboardAdmin()
    {
        // Estadísticas generales
        $totalAutopartes = Autoparte::count();
        $autopartesDisponibles = Autoparte::disponibles()->count();
        $autopartesBajoStock = Autoparte::where('stock', '<=', 5)->where('stock', '>', 0)->count();
        $autopartesSinStock = Autoparte::where('stock', 0)->count();
        
        $totalCategorias = Categoria::activas()->count();
        $totalUsuarios = User::activos()->count();
        
        // Ventas del mes
        $ventasMesActual = Factura::delMesActual()->sum('total');
        $totalFacturasMes = Factura::delMesActual()->count();
        
        // Ventas por mes (últimos 6 meses)
        $ventasPorMes = Factura::select(
            DB::raw('DATE_FORMAT(fecha_factura, "%Y-%m") as mes'),
            DB::raw('SUM(total) as total'),
            DB::raw('COUNT(*) as cantidad_facturas')
        )
        ->where('fecha_factura', '>=', now()->subMonths(6))
        ->groupBy('mes')
        ->orderBy('mes', 'desc')
        ->get();

        // Top 5 autopartes más vendidas
        $topAutopartes = Autoparte::select('autopartes.*', DB::raw('SUM(detalle_facturas.cantidad) as total_vendido'))
            ->join('detalle_facturas', 'autopartes.id', '=', 'detalle_facturas.autoparte_id')
            ->groupBy('autopartes.id')
            ->orderBy('total_vendido', 'desc')
            ->limit(5)
            ->get();

        // Categorías más vendidas
        $categoriasMasVendidas = Categoria::select('categorias.nombre', DB::raw('SUM(detalle_facturas.cantidad) as total_vendido'))
            ->join('autopartes', 'categorias.id', '=', 'autopartes.categoria_id')
            ->join('detalle_facturas', 'autopartes.id', '=', 'detalle_facturas.autoparte_id')
            ->groupBy('categorias.id', 'categorias.nombre')
            ->orderBy('total_vendido', 'desc')
            ->limit(5)
            ->get();

        // Últimas facturas
        $ultimasFacturas = Factura::with('usuario')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('dashboard.admin', compact(
            'totalAutopartes',
            'autopartesDisponibles',
            'autopartesBajoStock',
            'autopartesSinStock',
            'totalCategorias',
            'totalUsuarios',
            'ventasMesActual',
            'totalFacturasMes',
            'ventasPorMes',
            'topAutopartes',
            'categoriasMasVendidas',
            'ultimasFacturas'
        ));
    }

    /**
     * Dashboard para Operador
     */
    private function dashboardOperador()
    {
        // Estadísticas de inventario
        $totalAutopartes = Autoparte::count();
        $autopartesDisponibles = Autoparte::disponibles()->count();
        $autopartesBajoStock = Autoparte::where('stock', '<=', 5)->where('stock', '>', 0)->count();
        $autopartesSinStock = Autoparte::where('stock', 0)->count();

        // Últimas autopartes agregadas
        $ultimasAutopartes = Autoparte::with('categoria')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Alertas de stock
        $alertasStock = Autoparte::with('categoria')
            ->where('stock', '<=', 5)
            ->orderBy('stock', 'asc')
            ->get();

        return view('dashboard.operador', compact(
            'totalAutopartes',
            'autopartesDisponibles',
            'autopartesBajoStock',
            'autopartesSinStock',
            'ultimasAutopartes',
            'alertasStock'
        ));
    }

    /**
     * Dashboard para Cliente
     */
    private function dashboardCliente()
    {
        $user = auth()->user();

        // Mis compras
        $misCompras = Factura::with('detalles.autoparte')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Total gastado
        $totalGastado = Factura::where('user_id', $user->id)->sum('total');

        // Cantidad de compras
        $cantidadCompras = Factura::where('user_id', $user->id)->count();

        // Categorías favoritas (más compradas)
        $categoriasFavoritas = Categoria::select('categorias.nombre', DB::raw('COUNT(detalle_facturas.id) as veces_comprado'))
            ->join('autopartes', 'categorias.id', '=', 'autopartes.categoria_id')
            ->join('detalle_facturas', 'autopartes.id', '=', 'detalle_facturas.autoparte_id')
            ->join('facturas', 'detalle_facturas.factura_id', '=', 'facturas.id')
            ->where('facturas.user_id', $user->id)
            ->groupBy('categorias.id', 'categorias.nombre')
            ->orderBy('veces_comprado', 'desc')
            ->limit(5)
            ->get();

        return view('dashboard.cliente', compact(
            'misCompras',
            'totalGastado',
            'cantidadCompras',
            'categoriasFavoritas'
        ));
    }
}