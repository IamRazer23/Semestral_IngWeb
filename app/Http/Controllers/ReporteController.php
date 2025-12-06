<?php

namespace App\Http\Controllers;

use App\Models\Autoparte;
use App\Models\Factura;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class ReporteController extends Controller
{
    /**
     * Exportar inventario a Excel
     */
    public function inventarioExcel(Request $request)
    {
        $query = Autoparte::with('categoria');

        // Aplicar filtros
        if ($request->has('categoria_id') && $request->categoria_id != '') {
            $query->where('categoria_id', $request->categoria_id);
        }

        if ($request->has('estado') && $request->estado != '') {
            $query->where('estado', $request->estado);
        }

        $autopartes = $query->orderBy('categoria_id')->orderBy('nombre')->get();

        // Crear contenido CSV
        $csv = "Código SKU,Nombre,Marca,Modelo,Año,Categoría,Precio,Stock,Estado\n";

        foreach ($autopartes as $autoparte) {
            $estado = $autoparte->estado == 1 ? 'Activo' : 'Inactivo';
            $csv .= "\"{$autoparte->codigo_sku}\",";
            $csv .= "\"{$autoparte->nombre}\",";
            $csv .= "\"{$autoparte->marca}\",";
            $csv .= "\"{$autoparte->modelo}\",";
            $csv .= "\"{$autoparte->anio}\",";
            $csv .= "\"{$autoparte->categoria->nombre}\",";
            $csv .= "\"{$autoparte->precio}\",";
            $csv .= "\"{$autoparte->stock}\",";
            $csv .= "\"{$estado}\"\n";
        }

        $filename = 'inventario_' . date('Y-m-d_H-i-s') . '.csv';

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ]);
    }

    /**
     * Exportar ventas a Excel
     */
    public function ventasExcel(Request $request)
    {
        $query = Factura::with('usuario', 'detalles.autoparte');

        // Filtrar por fechas
        if ($request->has('fecha_inicio') && $request->has('fecha_fin')) {
            $query->whereBetween('fecha_factura', [
                $request->fecha_inicio,
                $request->fecha_fin
            ]);
        } else {
            // Por defecto, mes actual
            $query->whereMonth('fecha_factura', now()->month)
                  ->whereYear('fecha_factura', now()->year);
        }

        $facturas = $query->orderBy('fecha_factura', 'desc')->get();

        // Crear contenido CSV
        $csv = "Número Factura,Fecha,Cliente,Email,Subtotal,ITBMS,Total,Estado\n";

        foreach ($facturas as $factura) {
            $csv .= "\"{$factura->numero_factura}\",";
            $csv .= "\"{$factura->fecha_factura->format('Y-m-d H:i')}\",";
            $csv .= "\"{$factura->usuario->name}\",";
            $csv .= "\"{$factura->usuario->email}\",";
            $csv .= "\"{$factura->subtotal}\",";
            $csv .= "\"{$factura->itbms}\",";
            $csv .= "\"{$factura->total}\",";
            $csv .= "\"{$factura->estado}\"\n";
        }

        $filename = 'ventas_' . date('Y-m-d_H-i-s') . '.csv';

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ]);
    }
}