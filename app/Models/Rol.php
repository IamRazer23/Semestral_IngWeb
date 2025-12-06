<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    use HasFactory;

    protected $table = 'rols';

    protected $fillable = [
        'nombre',
        'descripcion',
        'permisos',
    ];

    protected $casts = [
        'permisos' => 'array',
    ];

    /**
     * Relación: Un rol tiene muchos usuarios
     */
    public function usuarios()
    {
        return $this->hasMany(User::class, 'rol_id');
    }

    /**
     * Verificar si el rol tiene un permiso específico
     */
    public function tienePermiso($permiso): bool
    {
        if (!$this->permisos) {
            return false;
        }
        return isset($this->permisos[$permiso]) && $this->permisos[$permiso] === true;
    }

    /**
     * Scope para roles activos
     */
    public function scopeActivos($query)
    {
        return $query->where('estado', 1);
    }
}