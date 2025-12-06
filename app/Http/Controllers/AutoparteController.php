<?php

namespace App\Http\Controllers;

use App\Models\Autoparte;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AutoparteController extends Controller
{
    /**
     * Mostrar listado de autopartes
     */
    public function index(Request $request)
    {
        $query = Autoparte::with('categoria');

        // Búsqueda
        if ($request->has('buscar') && $request->buscar != '') {
            $query->buscar($request->buscar);
        }

        // Filtro por categoría
        if ($request->has('categoria_id') && $request->categoria_id != '') {
            $query->porCategoria($request->categoria_id);
        }

        // Filtro por marca
        if ($request->has('marca') && $request->marca != '') {
            $query->porMarca($request->marca);
        }

        // Filtro por año
        if ($request->has('anio') && $request->anio != '') {
            $query->porAnio($request->anio);
        }

        // Filtro por rango de precio
        if ($request->has('precio_min') && $request->has('precio_max')) {
            $query->porRangoPrecio($request->precio_min, $request->precio_max);
        }

        // Filtro por disponibilidad
        if ($request->has('disponible') && $request->disponible == '1') {
            $query->disponibles();
        }

        $autopartes = $query->orderBy('created_at', 'desc')->paginate(15);
        $categorias = Categoria::activas()->get();
        
        // Obtener marcas únicas para filtro
        $marcas = Autoparte::select('marca')->distinct()->orderBy('marca')->pluck('marca');
        
        // Obtener años únicos para filtro
        $anios = Autoparte::select('anio')->distinct()->orderBy('anio', 'desc')->pluck('anio');

        return view('autopartes.index', compact('autopartes', 'categorias', 'marcas', 'anios'));
    }

    /**
     * Mostrar formulario de creación
     */
    public function create()
    {
        $categorias = Categoria::activas()->get();
        return view('autopartes.create', compact('categorias'));
    }

    /**
     * Guardar nueva autoparte
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:200',
            'descripcion' => 'nullable|string',
            'marca' => 'required|string|max:100',
            'modelo' => 'nullable|string|max:100',
            'anio' => 'nullable|string|max:4',
            'precio' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'categoria_id' => 'required|exists:categorias,id',
            'codigo_sku' => 'nullable|string|max:50|unique:autopartes,codigo_sku',
            'imagen_thumb' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'imagen_grande' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
        ]);

        // Procesar imagen thumbnail
        if ($request->hasFile('imagen_thumb')) {
            $validated['imagen_thumb'] = $request->file('imagen_thumb')->store('autopartes/thumbs', 'public');
        }

        // Procesar imagen grande
        if ($request->hasFile('imagen_grande')) {
            $validated['imagen_grande'] = $request->file('imagen_grande')->store('autopartes/images', 'public');
        }

        $autoparte = Autoparte::create($validated);

        // Registrar en historial si se agregó stock inicial
        if ($autoparte->stock > 0) {
            $autoparte->aumentarStock($autoparte->stock, auth()->id(), 'Stock inicial');
        }

        return redirect()->route('autopartes.index')
            ->with('success', 'Autoparte creada exitosamente');
    }

    /**
     * Mostrar detalle de autoparte
     */
    public function show(Autoparte $autoparte)
    {
        $autoparte->load('categoria', 'historial.usuario');
        return view('autopartes.show', compact('autoparte'));
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(Autoparte $autoparte)
    {
        $categorias = Categoria::activas()->get();
        return view('autopartes.edit', compact('autoparte', 'categorias'));
    }

    /**
     * Actualizar autoparte
     */
    public function update(Request $request, Autoparte $autoparte)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:200',
            'descripcion' => 'nullable|string',
            'marca' => 'required|string|max:100',
            'modelo' => 'nullable|string|max:100',
            'anio' => 'nullable|string|max:4',
            'precio' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'categoria_id' => 'required|exists:categorias,id',
            'codigo_sku' => 'nullable|string|max:50|unique:autopartes,codigo_sku,' . $autoparte->id,
            'estado' => 'required|boolean',
            'imagen_thumb' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'imagen_grande' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
        ]);

        // Procesar nueva imagen thumbnail
        if ($request->hasFile('imagen_thumb')) {
            // Eliminar imagen anterior
            if ($autoparte->imagen_thumb) {
                Storage::disk('public')->delete($autoparte->imagen_thumb);
            }
            $validated['imagen_thumb'] = $request->file('imagen_thumb')->store('autopartes/thumbs', 'public');
        }

        // Procesar nueva imagen grande
        if ($request->hasFile('imagen_grande')) {
            // Eliminar imagen anterior
            if ($autoparte->imagen_grande) {
                Storage::disk('public')->delete($autoparte->imagen_grande);
            }
            $validated['imagen_grande'] = $request->file('imagen_grande')->store('autopartes/images', 'public');
        }

        // Verificar si cambió el stock
        $stockAnterior = $autoparte->stock;
        $stockNuevo = $validated['stock'];

        $autoparte->update($validated);

        // Registrar cambio de stock en historial
        if ($stockAnterior != $stockNuevo) {
            $diferencia = $stockNuevo - $stockAnterior;
            $tipo = $diferencia > 0 ? 'entrada' : 'salida';
            
            \App\Models\HistorialInventario::create([
                'autoparte_id' => $autoparte->id,
                'user_id' => auth()->id(),
                'tipo_movimiento' => 'ajuste',
                'cantidad' => abs($diferencia),
                'stock_anterior' => $stockAnterior,
                'stock_nuevo' => $stockNuevo,
                'motivo' => 'Ajuste manual de inventario',
            ]);
        }

        return redirect()->route('autopartes.index')
            ->with('success', 'Autoparte actualizada exitosamente');
    }

    /**
     * Eliminar autoparte (soft delete - cambiar estado)
     */
    public function destroy(Autoparte $autoparte)
    {
        // Verificar si tiene ventas asociadas
        if ($autoparte->detalleFacturas()->exists()) {
            return redirect()->route('autopartes.index')
                ->with('error', 'No se puede eliminar esta autoparte porque tiene ventas asociadas. Se deshabilitará en su lugar.');
        }

        // Deshabilitar en lugar de eliminar
        $autoparte->update(['estado' => 0]);

        return redirect()->route('autopartes.index')
            ->with('success', 'Autoparte deshabilitada exitosamente');
    }

    /**
     * Ajustar stock manualmente
     */
    public function ajustarStock(Request $request, Autoparte $autoparte)
    {
        $validated = $request->validate([
            'cantidad' => 'required|integer',
            'tipo' => 'required|in:entrada,salida,ajuste',
            'motivo' => 'required|string|max:255',
        ]);

        try {
            $stockAnterior = $autoparte->stock;
            
            if ($validated['tipo'] == 'entrada') {
                $autoparte->aumentarStock($validated['cantidad'], auth()->id(), $validated['motivo']);
            } elseif ($validated['tipo'] == 'salida') {
                $autoparte->reducirStock($validated['cantidad'], auth()->id(), $validated['motivo']);
            } else {
                // Ajuste directo
                $autoparte->update(['stock' => $validated['cantidad']]);
                
                \App\Models\HistorialInventario::create([
                    'autoparte_id' => $autoparte->id,
                    'user_id' => auth()->id(),
                    'tipo_movimiento' => 'ajuste',
                    'cantidad' => abs($validated['cantidad'] - $stockAnterior),
                    'stock_anterior' => $stockAnterior,
                    'stock_nuevo' => $validated['cantidad'],
                    'motivo' => $validated['motivo'],
                ]);
            }

            return redirect()->back()
                ->with('success', 'Stock ajustado exitosamente');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }
}