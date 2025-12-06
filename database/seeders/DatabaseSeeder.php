<?php
// ============================================
// RoleSeeder.php
// database/seeders/RoleSeeder.php
// ============================================

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('rols')->insert([
            [
                'nombre' => 'Administrador',
                'descripcion' => 'Acceso completo al sistema',
                'permisos' => json_encode([
                    'usuarios' => true,
                    'roles' => true,
                    'inventario' => true,
                    'categorias' => true,
                    'estadisticas' => true,
                    'reportes' => true
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Operador',
                'descripcion' => 'Gestión de inventario',
                'permisos' => json_encode([
                    'inventario' => true,
                    'categorias' => true,
                    'buscar' => true
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Cliente',
                'descripcion' => 'Compra de autopartes',
                'permisos' => json_encode([
                    'catalogo' => true,
                    'carrito' => true,
                    'compras' => true
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

// ============================================
// CategoriaSeeder.php
// database/seeders/CategoriaSeeder.php
// ============================================

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            ['nombre' => 'Motor', 'descripcion' => 'Piezas relacionadas con el motor del vehículo', 'icono' => 'fa-cog'],
            ['nombre' => 'Carrocería', 'descripcion' => 'Partes externas del vehículo', 'icono' => 'fa-car'],
            ['nombre' => 'Vidrios', 'descripcion' => 'Parabrisas y ventanas', 'icono' => 'fa-window-maximize'],
            ['nombre' => 'Eléctrico', 'descripcion' => 'Sistema eléctrico y electrónico', 'icono' => 'fa-bolt'],
            ['nombre' => 'Interior', 'descripcion' => 'Componentes internos del vehículo', 'icono' => 'fa-couch'],
            ['nombre' => 'Frenos', 'descripcion' => 'Sistema de frenado', 'icono' => 'fa-stop-circle'],
            ['nombre' => 'Suspensión', 'descripcion' => 'Sistema de suspensión y amortiguación', 'icono' => 'fa-compress'],
            ['nombre' => 'Transmisión', 'descripcion' => 'Caja de cambios y componentes', 'icono' => 'fa-gear'],
        ];

        foreach ($categorias as $categoria) {
            DB::table('categorias')->insert([
                'nombre' => $categoria['nombre'],
                'descripcion' => $categoria['descripcion'],
                'icono' => $categoria['icono'],
                'estado' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}

// ============================================
// AdminUserSeeder.php
// database/seeders/AdminUserSeeder.php
// ============================================

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->insert([
            'name' => 'Administrador',
            'email' => 'admin@inventario.com',
            'password' => Hash::make('admin123'),
            'telefono' => '6000-0000',
            'direccion' => 'Panamá, Panamá',
            'rol_id' => 1, // Administrador
            'estado' => 1,
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

// ============================================
// DatabaseSeeder.php
// database/seeders/DatabaseSeeder.php
// ============================================

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            AdminUserSeeder::class,
            CategoriaSeeder::class,
        ]);
    }
}