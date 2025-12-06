<?php

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