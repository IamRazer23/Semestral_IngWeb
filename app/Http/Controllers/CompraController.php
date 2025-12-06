<?php

namespace App\Http\Controllers;

use App\Models\Carrito;
use App\Models\Factura;
use App\Models\DetalleFactura;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompraController extends Controller
{
    /**
     * Mostrar resumen de compra antes de confirmar
     */
    public function resumen()
    {
        $carritos = Carrito::with('autoparte.categoria')
            ->where('user_id', auth()->id())
            ->get();

        if ($carritos->isEmpty()) {
            return redirect()->route('carrito.index')
                ->with('error', 'Tu carrito está vacío');
        }

        $subtotal = $carritos->sum(function ($item) {
            return $item->cantidad * $item->precio_unitario;
        });

        $itbms = Factura::calcularItbms($subtotal);
        $total = Factura::calcularTotal($subtotal);

        return view('compra.resumen', compact('carritos', 'subtotal', 'itbms', 'total'));
    }

    /**
     * Procesar la compra y generar factura
     */
    public function procesar(Request $request)
    {
        $carritos = Carrito::with('autoparte')
            ->where('user_id', auth()->id())
            ->get();

        if ($carritos->isEmpty()) {
            return redirect()->route('carrito.index')
                ->with('error', 'Tu carrito está vacío');
        }

        // Verificar stock de todos los productos
        foreach ($carritos as $item) {
            if (!$item->autoparte->tieneStock($item->cantidad)) {
                return redirect()->route('carrito.index')
                    ->with('error', "Stock insuficiente para: {$item->autoparte->nombre}");
            }
        }

        DB::beginTransaction();

        try {
            // Calcular totales
            $subtotal = $carritos->sum(function ($item) {
                return $item->cantidad * $item->precio_unitario;
            });

            $itbms = Factura::calcularItbms($subtotal);
            $total = Factura::calcularTotal($subtotal);

            // Crear factura
            $factura = Factura::create([
                'user_id' => auth()->id(),
                'subtotal' => $subtotal,
                'itbms' => $itbms,
                'total' => $total,
                'fecha_factura' => now(),
                'estado' => 'completada',
            ]);

            // Crear detalles de factura y reducir stock
            foreach ($carritos as $item) {
                // Crear detalle de factura
                DetalleFactura::create([
                    'factura_id' => $factura->id,
                    'autoparte_id' => $item->autoparte_id,
                    'cantidad' => $item->cantidad,
                    'precio_unitario' => $item->precio_unitario,
                    'subtotal' => $item->cantidad * $item->precio_unitario,
                ]);

                // Reducir stock (esto también registra en historial)
                $item->autoparte->reducirStock(
                    $item->cantidad,
                    auth()->id(),
                    "Venta - Factura #{$factura->numero_factura}"
                );
            }

            // Vaciar carrito
            Carrito::where('user_id', auth()->id())->delete();

            DB::commit();

            return redirect()->route('compra.factura', $factura)
                ->with('success', 'Compra realizada exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->route('carrito.index')
                ->with('error', 'Error al procesar la compra: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar factura generada
     */
    public function factura(Factura $factura)
    {
        // Verificar que la factura pertenezca al usuario autenticado
        // (excepto si es admin u operador)
        if (!auth()->user()->esAdministrador() && 
            !auth()->user()->esOperador() && 
            $factura->user_id !== auth()->id()) {
            abort(403);
        }

        $factura->load('detalles.autoparte.categoria', 'usuario');

        return view('compra.factura', compact('factura'));
    }

    /**
     * Descargar factura en PDF
     */
    public function descargarPDF(Factura $factura)
    {
        // Verificar permisos
        if (!auth()->user()->esAdministrador() && 
            !auth()->user()->esOperador() && 
            $factura->user_id !== auth()->id()) {
            abort(403);
        }

        $factura->load('detalles.autoparte.categoria', 'usuario');

        // Aquí puedes usar una librería como DomPDF o TCPDF
        // Por ahora retornamos la vista
        return view('compra.factura-pdf', compact('factura'));
    }

    /**
     * Historial de compras del usuario
     */
    public function historial()
    {
        $facturas = Factura::with('detalles')
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('compra.historial', compact('facturas'));
    }
}