<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Si ya existen, puedes proteger con 'hasColumn' pero en migraciones estándar basta con añadirlos
            $table->string('telefono', 20)->nullable()->after('password');
            $table->string('direccion')->nullable()->after('telefono');
            $table->foreignId('rol_id')->after('direccion')
                  ->constrained('rols')   // tu tabla es rols
                  ->cascadeOnUpdate()
                  ->restrictOnDelete();
            $table->boolean('estado')->default(1)->after('rol_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // eliminar la FK y columnas en orden inverso
            $table->dropConstrainedForeignId('rol_id');
            $table->dropColumn(['estado', 'direccion', 'telefono']);
        });
    }
};
