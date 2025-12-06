<?php

/**
 * Funciones auxiliares globales del sistema
 */

if (!function_exists('formatearPrecio')) {
    /**
     * Formatear precio con símbolo de moneda
     */
    function formatearPrecio($precio)
    {
        return 'B/. ' . number_format($precio, 2);
    }
}

if (!function_exists('calcularItbms')) {
    /**
     * Calcular ITBMS (7%)
     */
    function calcularItbms($subtotal)
    {
        return round($subtotal * 0.07, 2);
    }
}

if (!function_exists('calcularTotal')) {
    /**
     * Calcular total con ITBMS
     */
    function calcularTotal($subtotal)
    {
        return $subtotal + calcularItbms($subtotal);
    }
}

if (!function_exists('rutaActiva')) {
    /**
     * Verificar si la ruta actual coincide
     */
    function rutaActiva($ruta, $clase = 'active')
    {
        return request()->routeIs($ruta) ? $clase : '';
    }
}

if (!function_exists('urlImagen')) {
    /**
     * Obtener URL de imagen o placeholder
     */
    function urlImagen($ruta, $default = 'images/no-image.png')
    {
        if (!$ruta) {
            return asset($default);
        }
        
        if (filter_var($ruta, FILTER_VALIDATE_URL)) {
            return $ruta;
        }
        
        return asset('storage/' . $ruta);
    }
}

if (!function_exists('generarSKU')) {
    /**
     * Generar código SKU único
     */
    function generarSKU($prefijo = 'AP')
    {
        return $prefijo . '-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
    }
}

if (!function_exists('estadoColor')) {
    /**
     * Obtener color según estado
     */
    function estadoColor($estado)
    {
        return $estado == 1 ? 'success' : 'danger';
    }
}

if (!function_exists('estadoTexto')) {
    /**
     * Obtener texto según estado
     */
    function estadoTexto($estado)
    {
        return $estado == 1 ? 'Activo' : 'Inactivo';
    }
}

if (!function_exists('stockColor')) {
    /**
     * Obtener color según nivel de stock
     */
    function stockColor($stock)
    {
        if ($stock == 0) return 'danger';
        if ($stock <= 5) return 'warning';
        return 'success';
    }
}

if (!function_exists('stockTexto')) {
    /**
     * Obtener texto según nivel de stock
     */
    function stockTexto($stock)
    {
        if ($stock == 0) return 'Sin Stock';
        if ($stock <= 5) return 'Stock Bajo';
        return 'Disponible';
    }
}

if (!function_exists('formatearFecha')) {
    /**
     * Formatear fecha en español
     */
    function formatearFecha($fecha, $formato = 'd/m/Y')
    {
        if (!$fecha) return '-';
        
        if (is_string($fecha)) {
            $fecha = \Carbon\Carbon::parse($fecha);
        }
        
        return $fecha->format($formato);
    }
}

if (!function_exists('formatearFechaHora')) {
    /**
     * Formatear fecha y hora
     */
    function formatearFechaHora($fecha)
    {
        return formatearFecha($fecha, 'd/m/Y H:i');
    }
}

if (!function_exists('tiempoTranscurrido')) {
    /**
     * Mostrar tiempo transcurrido (hace X tiempo)
     */
    function tiempoTranscurrido($fecha)
    {
        if (!$fecha) return '-';
        
        if (is_string($fecha)) {
            $fecha = \Carbon\Carbon::parse($fecha);
        }
        
        return $fecha->diffForHumans();
    }
}

if (!function_exists('userCan')) {
    /**
     * Verificar si el usuario tiene permiso
     */
    function userCan($permiso)
    {
        if (!auth()->check()) return false;
        return auth()->user()->tienePermiso($permiso);
    }
}

if (!function_exists('isAdmin')) {
    /**
     * Verificar si el usuario es administrador
     */
    function isAdmin()
    {
        if (!auth()->check()) return false;
        return auth()->user()->esAdministrador();
    }
}

if (!function_exists('isOperador')) {
    /**
     * Verificar si el usuario es operador
     */
    function isOperador()
    {
        if (!auth()->check()) return false;
        return auth()->user()->esOperador();
    }
}

if (!function_exists('isCliente')) {
    /**
     * Verificar si el usuario es cliente
     */
    function isCliente()
    {
        if (!auth()->check()) return false;
        return auth()->user()->esCliente();
    }
}

if (!function_exists('alertClass')) {
    /**
     * Obtener clase de Bootstrap según tipo de alerta
     */
    function alertClass($tipo)
    {
        $clases = [
            'success' => 'alert-success',
            'error' => 'alert-danger',
            'warning' => 'alert-warning',
            'info' => 'alert-info',
        ];
        
        return $clases[$tipo] ?? 'alert-info';
    }
}

if (!function_exists('truncate')) {
    /**
     * Truncar texto a cierta longitud
     */
    function truncate($texto, $longitud = 100, $final = '...')
    {
        if (strlen($texto) <= $longitud) {
            return $texto;
        }
        
        return substr($texto, 0, $longitud) . $final;
    }
}