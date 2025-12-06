<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla asociada
     * (importante porque tu tabla se llama "rols" y no "roles")
     */
    protected $table = 'rols';

    /**
     * Campos asignables masivamente
     */
    protected $fillable = [
        'nombre',
        'descripcion',
        'permisos', // JSON
    ];

    /**
     * Cast para que "permisos" se convierta automáticamente en array
     */
    protected $casts = [
        'permisos' => 'array', // <- Muy importante
    ];

    /**
     * Relación: un rol tiene muchos usuarios
     * FK: users.rol_id → rols.id
     */
    public function usuarios()
    {
        return $this->hasMany(\App\Models\User::class, 'rol_id');
    }

    /**
     * Verificar si dentro de este rol existe un permiso específico
     * (Este método es opcional porque normalmente lo llamamos desde User)
     */
    public function permite(string $permiso): bool
    {
        $permisos = $this->permisos ?? [];

        // normalizamos clave solicitada
        $clave = mb_strtolower($permiso);

        $normalizados = [];
        foreach ($permisos as $k => $v) {
            $normalizados[mb_strtolower($k)] = (bool) $v;
        }

        return $normalizados[$clave] ?? false;
    }
}
