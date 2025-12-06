<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $table = 'categorias';

    protected $fillable = ['nombre','descripcion','icono','estado'];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // 👇 Esto habilita Categoria::activas()
    public function scopeActivas($query)
    {
        return $query->where('estado', 1);
    }
}
