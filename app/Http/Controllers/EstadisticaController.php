<?php

namespace App\Http\Controllers;

use App\Models\Autoparte;
use App\Models\Categoria;
use App\Models\Factura;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EstadisticaController extends Controller
{
    public function index(Request $request)
    {
        // Período seleccionado (default: mes actual)
        $mes = $request->get('mes', now()->month);
        $anio = $request->get('anio', now()->year);

        // Ventas del período
        $ventasPeriodo = Factura::whereMonth('fecha_factura', $mes)
            ->whereYear('fecha_factura', $anio)
            ->sum('total');

        $cantidadVentas = Factura::whereMonth('fecha_factura', $mes)
            ->whereYear('fecha_factura', $anio)
            ->count();

        // Ventas por mes (últimos 12 meses)
        $ventasMensuales = Factura::select(
            DB::raw('DATE_FORMAT(fecha_factura, "%Y-%m") as mes'),
            DB::raw('SUM(total) as total'),
            DB::raw('COUNT(*) as cantidad')
        )
        ->where('fecha_factura', '>=', now()->subMonths(12))
        ->groupBy('mes')
        ->orderBy('mes', 'asc')
        ->get();

        // Top 10 autopartes más vendidas
        $topAutopartes = Autoparte::select(
            'autopartes.id',
            'autopartes.nombre',
            'autopartes.codigo_sku',
            DB::raw('SUM(detalle_facturas.cantidad) as total_vendido'),
            DB::raw('SUM(detalle_facturas.subtotal) as ingresos')
        )
        ->join('detalle_facturas', 'autopartes.id', '=', 'detalle_facturas.autoparte_id')
        ->join('facturas', 'detalle_facturas.factura_id', '=', 'facturas.id')
        ->whereMonth('facturas.fecha_factura', $mes)
        ->whereYear('facturas.fecha_factura', $anio)
        ->groupBy('autopartes.id', 'autopartes.nombre', 'autopartes.codigo_sku')
        ->orderBy('total_vendido', 'desc')
        ->limit(10)
        ->get();

        // Ventas por categoría
        $ventasPorCategoria = Categoria::select(
            'categorias.nombre',
            DB::raw('SUM(detalle_facturas.cantidad) as cantidad_vendida'),
            DB::raw('SUM(detalle_facturas.subtotal) as ingresos')
        )
        ->join('autopartes', 'categorias.id', '=', 'autopartes.categoria_id')
        ->join('detalle_facturas', 'autopartes.id', '=', 'detalle_facturas.autoparte_id')
        ->join('facturas', 'detalle_facturas.factura_id', '=', 'facturas.id')
        ->whereMonth('facturas.fecha_factura', $mes)
        ->whereYear('facturas.fecha_factura', $anio)
        ->groupBy('categorias.id', 'categorias.nombre')
        ->orderBy('ingresos', 'desc')
        ->get();

        // Alertas de stock bajo
        $stockBajo = Autoparte::with('categoria')
            ->where('stock', '<=', 5)
            ->where('stock', '>', 0)
            ->orderBy('stock', 'asc')
            ->get();

        return view('estadisticas.index', compact(
            'ventasPeriodo',
            'cantidadVentas',
            'ventasMensuales',
            'topAutopartes',
            'ventasPorCategoria',
            'stockBajo',
            'mes',
            'anio'
        ));
    }
}
