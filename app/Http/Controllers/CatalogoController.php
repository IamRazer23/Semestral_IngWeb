<?php

namespace App\Http\Controllers;

use App\Models\Autoparte;
use App\Models\Categoria;
use Illuminate\Http\Request;

class CatalogoController extends Controller
{
    /**
     * Mostrar catálogo público de autopartes
     */
    public function index(Request $request)
    {
        $query = Autoparte::with('categoria')->disponibles();

        // Búsqueda
        if ($request->has('buscar') && $request->buscar != '') {
            $query->buscar($request->buscar);
        }

        // Filtro por categoría
        if ($request->has('categoria') && $request->categoria != '') {
            $query->porCategoria($request->categoria);
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

        // Ordenamiento
        $ordenamiento = $request->get('ordenar', 'recientes');
        switch ($ordenamiento) {
            case 'precio_asc':
                $query->orderBy('precio', 'asc');
                break;
            case 'precio_desc':
                $query->orderBy('precio', 'desc');
                break;
            case 'nombre':
                $query->orderBy('nombre', 'asc');
                break;
            case 'recientes':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $autopartes = $query->paginate(12);
        
        // Obtener categorías con conteo de autopartes disponibles
        $categorias = Categoria::activas()
            ->withCount(['autopartes' => function ($query) {
                $query->disponibles();
            }])
            ->get();
        
        // Obtener marcas únicas
        $marcas = Autoparte::disponibles()
            ->select('marca')
            ->distinct()
            ->orderBy('marca')
            ->pluck('marca');
        
        // Obtener años únicos
        $anios = Autoparte::disponibles()
            ->select('anio')
            ->distinct()
            ->orderBy('anio', 'desc')
            ->pluck('anio');

        return view('catalogo.index', compact(
            'autopartes',
            'categorias',
            'marcas',
            'anios'
        ));
    }

    /**
     * Mostrar detalle de una autoparte
     */
    public function show(Autoparte $autoparte)
    {
        // Solo mostrar si está disponible
        if ($autoparte->estado != 1 || $autoparte->stock <= 0) {
            abort(404);
        }

        $autoparte->load('categoria');

        // Autopartes relacionadas de la misma categoría
        $relacionadas = Autoparte::disponibles()
            ->where('categoria_id', $autoparte->categoria_id)
            ->where('id', '!=', $autoparte->id)
            ->limit(4)
            ->get();

        return view('catalogo.show', compact('autoparte', 'relacionadas'));
    }

    /**
     * Mostrar autopartes por categoría
     */
    public function categoria(Categoria $categoria)
    {
        $autopartes = Autoparte::disponibles()
            ->where('categoria_id', $categoria->id)
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return view('catalogo.categoria', compact('categoria', 'autopartes'));
    }
}