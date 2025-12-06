<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facturas', function (Blueprint $table) {
            $table->id();
            $table->string('numero_factura', 50)->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('itbms', 10, 2)->comment('ITBMS 7%');
            $table->decimal('total', 10, 2);
            $table->timestamp('fecha_factura')->useCurrent();
            $table->string('estado', 20)->default('completada');
            $table->timestamps();
            
            $table->index('fecha_factura');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facturas');
    }
};