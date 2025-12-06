<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->updateOrInsert(
            ['email' => 'admin@inventario.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('admin123'),
                'telefono' => '6000-0000',
                'direccion' => 'Panamá, Panamá',
                'rol_id' => 1,      // Debe existir en 'rols'
                'estado' => 1,
                'email_verified_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}
