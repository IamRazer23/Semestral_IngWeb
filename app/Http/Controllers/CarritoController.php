<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;

use App\Models\Carrito;
use App\Models\Autoparte;
use Illuminate\Http\Request;

class CarritoController extends Controller
{
    /**
     * Mostrar el carrito del usuario
     */
    public function index()
    {
        $carritos = Carrito::with('autoparte.categoria')
            ->where('user_id', Auth::id())
            ->get();

        $subtotal = $carritos->sum(function ($item) {
            return $item->cantidad * $item->precio_unitario;
        });

        $itbms = round($subtotal * 0.07, 2);
        $total = $subtotal + $itbms;

        return view('carrito.index', compact('carritos', 'subtotal', 'itbms', 'total'));
    }

    /**
     * Agregar autoparte al carrito
     */
    public function agregar(Request $request, Autoparte $autoparte)
    {
        $validated = $request->validate([
            'cantidad' => 'required|integer|min:1',
        ]);

        // Verificar que haya stock disponible
        if (!$autoparte->tieneStock($validated['cantidad'])) {
            return redirect()->back()
                ->with('error', 'Stock insuficiente. Solo hay ' . $autoparte->stock . ' unidades disponibles');
        }

        // Buscar si ya existe en el carrito
        $carritoItem = Carrito::where('user_id', Auth::id())
            ->where('autoparte_id', $autoparte->id)
            ->first();

        if ($carritoItem) {
            // Si ya existe, aumentar cantidad
            $nuevaCantidad = $carritoItem->cantidad + $validated['cantidad'];
            
            // Verificar stock para la nueva cantidad
            if (!$autoparte->tieneStock($nuevaCantidad)) {
                return redirect()->back()
                    ->with('error', 'No puedes agregar más unidades. Stock insuficiente');
            }

            $carritoItem->update([
                'cantidad' => $nuevaCantidad,
            ]);

            return redirect()->back()
                ->with('success', 'Cantidad actualizada en el carrito');
        } else {
            // Si no existe, crear nuevo item
            Carrito::create([
                'user_id' => Auth::id(),
                'autoparte_id' => $autoparte->id,
                'cantidad' => $validated['cantidad'],
                'precio_unitario' => $autoparte->precio,
            ]);

            return redirect()->back()
                ->with('success', 'Producto agregado al carrito');
        }
    }

    /**
     * Actualizar cantidad de un item del carrito
     */
    public function actualizar(Request $request, Carrito $carrito)
    {
        // Verificar que el carrito pertenezca al usuario autenticado
        if ($carrito->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'cantidad' => 'required|integer|min:1',
        ]);

        // Verificar stock
        if (!$carrito->autoparte->tieneStock($validated['cantidad'])) {
            return redirect()->back()
                ->with('error', 'Stock insuficiente');
        }

        $carrito->update([
            'cantidad' => $validated['cantidad'],
        ]);

        return redirect()->back()
            ->with('success', 'Cantidad actualizada');
    }

    /**
     * Eliminar item del carrito
     */
    public function eliminar(Carrito $carrito)
    {
        // Verificar que el carrito pertenezca al usuario autenticado
        if ($carrito->user_id !== Auth::id()) {
            abort(403);
        }

        $carrito->delete();

        return redirect()->back()
            ->with('success', 'Producto eliminado del carrito');
    }

    /**
     * Vaciar todo el carrito
     */
    public function vaciar()
    {
        Carrito::where('user_id', Auth::id())->delete();

        return redirect()->back()
            ->with('success', 'Carrito vaciado');
    }

    /**
     * Obtener el conteo de items en el carrito (para el badge del menú)
     */
    public function conteo()
    {
        $conteo = Carrito::where('user_id', Auth::id())->sum('cantidad');
        return response()->json(['conteo' => $conteo]);
    }
}