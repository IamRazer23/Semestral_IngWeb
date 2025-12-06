<?php

namespace App\Http\Controllers;

use App\Models\Carrito;
use App\Models\Factura;
use App\Models\DetalleFactura;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CompraController extends Controller
{
    /**
     * Mostrar resumen de compra antes de confirmar
     */
    public function resumen()
    {
        $carritos = Carrito::with('autoparte.categoria')
            ->where('user_id', Auth::id())
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
            ->where('user_id', Auth::id())
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
                'user_id' => Auth::id(),
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
                    Auth::id(),
                    "Venta - Factura #{$factura->numero_factura}"
                );
            }

            // Vaciar carrito
            Carrito::where('user_id', Auth::id())->delete();

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
    public function factura(Factura $factura, Request $request)
{
    $user = $request->user(); // ✅ evita ambigüedad con helpers/facades

    if (!$user) {
        return redirect()->route('login')->with('error', 'Debes iniciar sesión.');
    }

    // Permisos: admin u operador, o dueño de la factura
    $esAdmin    = method_exists($user, 'esAdministrador') ? $user->esAdministrador() : false;
    $esOperador = method_exists($user, 'esOperador')      ? $user->esOperador()      : false;

    if (!$esAdmin && !$esOperador && $factura->user_id !== $user->id) {
        abort(403);
    }

    // Carga relaciones. Usa el nombre REAL en el modelo Factura (usuario o user)
    // Si en Factura tienes public function usuario(){ return $this->belongsTo(User::class,'user_id'); }
    $factura->load(['detalles.autoparte.categoria', 'usuario']);

    return view('compra.factura', compact('factura'));
}
    /**
     * Descargar factura en PDF
     */
public function descargarPDF(Factura $factura, Request $request)
{
    $user = $request->user(); // ✅

    if (!$user) {
        return redirect()->route('login')->with('error', 'Debes iniciar sesión.');
    }

    $esAdmin    = method_exists($user, 'esAdministrador') ? $user->esAdministrador() : false;
    $esOperador = method_exists($user, 'esOperador')      ? $user->esOperador()      : false;

    if (!$esAdmin && !$esOperador && $factura->user_id !== $user->id) {
        abort(403);
    }

    $factura->load(['detalles.autoparte.categoria', 'usuario']);

    return view('compra.factura-pdf', compact('factura'));
}

    /**
     * Historial de compras del usuario
     */
    public function historial()
    {
        $facturas = Factura::with('detalles')
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('compra.historial', compact('facturas'));
    }
}