<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Factura extends Model
{
    use HasFactory;

    protected $fillable = [
        'numero_factura',
        'user_id',
        'subtotal',
        'itbms',
        'total',
        'fecha_factura',
        'estado',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'itbms' => 'decimal:2',
        'total' => 'decimal:2',
        'fecha_factura' => 'datetime',
    ];

    /**
     * Boot del modelo para generar número de factura automáticamente
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($factura) {
            if (empty($factura->numero_factura)) {
                $factura->numero_factura = 'FAC-' . date('Ymd') . '-' . str_pad(
                    Factura::whereDate('created_at', today())->count() + 1,
                    4,
                    '0',
                    STR_PAD_LEFT
                );
            }
        });
    }

    /**
     * Relación: Una factura pertenece a un usuario
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relación: Una factura tiene muchos detalles
     */
    public function detalles()
    {
        return $this->hasMany(DetalleFactura::class, 'factura_id');
    }

    /**
     * Scope para facturas de un usuario
     */
    public function scopeDelUsuario($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope para facturas por fecha
     */
    public function scopePorFecha($query, $desde, $hasta)
    {
        return $query->whereBetween('fecha_factura', [$desde, $hasta]);
    }

    /**
     * Scope para facturas del mes actual
     */
    public function scopeDelMesActual($query)
    {
        return $query->whereMonth('fecha_factura', now()->month)
                     ->whereYear('fecha_factura', now()->year);
    }

    /**
     * Calcular ITBMS (7%)
     */
    public static function calcularItbms($subtotal)
    {
        return round($subtotal * 0.07, 2);
    }

    /**
     * Calcular total con ITBMS
     */
    public static function calcularTotal($subtotal)
    {
        $itbms = self::calcularItbms($subtotal);
        return $subtotal + $itbms;
    }
}
