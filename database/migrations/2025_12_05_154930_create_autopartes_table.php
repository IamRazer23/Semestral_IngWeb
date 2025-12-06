<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('autopartes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 200);
            $table->text('descripcion')->nullable();
            $table->string('marca', 100);
            $table->string('modelo', 100)->nullable();
            $table->string('anio', 4)->nullable();
            $table->decimal('precio', 10, 2);
            $table->integer('stock')->default(0);
            $table->foreignId('categoria_id')->constrained('categorias')->onDelete('restrict');
            $table->string('imagen_thumb')->nullable();
            $table->string('imagen_grande')->nullable();
            $table->tinyInteger('estado')->default(1)->comment('1=disponible, 0=no disponible');
            $table->string('codigo_sku', 50)->unique()->nullable();
            $table->timestamps();
            
            $table->index('marca');
            $table->index('modelo');
            $table->index('anio');
            $table->index('categoria_id');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('autopartes');
    }
};