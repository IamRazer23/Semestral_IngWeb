<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    /**
     * Atributos asignables masivamente
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'telefono',
        'direccion',
        'rol_id',
        'estado',
        'email_verified_at',
    ];

    /**
     * Atributos ocultos para arrays/JSON
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Conversión automática de tipos
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'estado'            => 'boolean',
    ];

    /**
     * Relación con el rol
     * Tabla: rols
     * FK: rol_id
     */
    public function rol()
    {
        return $this->belongsTo(\App\Models\Rol::class, 'rol_id');
    }

    /**
     * Verifica si el usuario es administrador
     */
    public function esAdministrador(): bool
    {
        return (int) $this->rol_id === 1
            || (optional($this->rol)->nombre === 'Administrador');
    }

    /**
     * Verifica si el usuario es operador
     */
    public function esOperador(): bool
    {
        return (int) $this->rol_id === 2
            || (optional($this->rol)->nombre === 'Operador');
    }

    /**
     * Verifica si el usuario posee un permiso específico
     */
    public function tienePermiso(string $permiso): bool
    {
        // Administrador tiene acceso total
        if ($this->esAdministrador()) {
            return true;
        }

        $rol = $this->rol;
        if (!$rol) {
            return false;
        }

        // Los permisos vienen como JSON en la tabla rols
        // En Rol.php debes tener: protected $casts = ['permisos' => 'array'];
        $permisos = $rol->permisos ?? [];

        // Normalizar a minúsculas
        $clave = mb_strtolower($permiso);

        // Convertimos claves del JSON a minúsculas
        $normalizados = [];
        foreach ($permisos as $key => $value) {
            $normalizados[mb_strtolower($key)] = (bool) $value;
        }

        return $normalizados[$clave] ?? false;
    }
}
