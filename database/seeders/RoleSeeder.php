<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Inserta o actualiza por 'nombre' para evitar duplicados si corres varias veces
        $roles = [
            [
                'nombre' => 'Administrador',
                'descripcion' => 'Acceso completo al sistema',
                'permisos' => [
                    'usuarios' => true,
                    'roles' => true,
                    'inventario' => true,
                    'categorias' => true,
                    'estadisticas' => true,
                    'reportes' => true
                ],
            ],
            [
                'nombre' => 'Operador',
                'descripcion' => 'Gestión de inventario',
                'permisos' => [
                    'inventario' => true,
                    'categorias' => true,
                    'buscar' => true
                ],
            ],
            [
                'nombre' => 'Cliente',
                'descripcion' => 'Compra de autopartes',
                'permisos' => [
                    'catalogo' => true,
                    'carrito' => true,
                    'compras' => true
                ],
            ],
        ];

        foreach ($roles as $r) {
            DB::table('rols')->updateOrInsert(
                ['nombre' => $r['nombre']],
                [
                    'descripcion' => $r['descripcion'],
                    // Si tu columna `permisos` es JSON en la migración, puedes guardar el array directo.
                    // Si NO es JSON, usa json_encode($r['permisos'])
                    'permisos' => json_encode($r['permisos']),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
