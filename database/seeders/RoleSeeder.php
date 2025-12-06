<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [

            // Rol 1: Administrador
            [
                'nombre'      => 'Administrador',
                'descripcion' => 'Acceso completo al sistema',
                'permisos'    => [
                    'usuarios'     => true,
                    'roles'        => true,
                    'inventario'   => true,
                    'categorias'   => true,
                    'autopartes'   => true,
                    'carritos'     => true,
                    'estadisticas' => true,
                    'facturacion'  => true,
                    'reportes'     => true,
                ],
            ],

            // Rol 2: Operador
            [
                'nombre'      => 'Operador',
                'descripcion' => 'Gestión del inventario y facturación básica',
                'permisos'    => [
                    'inventario'   => true,
                    'categorias'   => true,
                    'autopartes'   => true,
                    'facturacion'  => true,
                    'carritos'     => true,
                ],
            ],

            // Rol 3: Cliente
            [
                'nombre'      => 'Cliente',
                'descripcion' => 'Puede visualizar productos y realizar compras',
                'permisos'    => [
                    'catalogo'     => true,
                    'carrito'      => true,
                    'compras'      => true,
                ],
            ],
        ];

        foreach ($roles as $rol) {
            DB::table('rols')->updateOrInsert(
                ['nombre' => $rol['nombre']], // clave única del rol
                [
                    'descripcion' => $rol['descripcion'],
                    'permisos'    => json_encode($rol['permisos']), // la BD almacena JSON
                    'created_at'  => Carbon::now(),
                    'updated_at'  => Carbon::now(),
                ]
            );
        }
    }
}
