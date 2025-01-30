<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('producto', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->text('descripcion');
            $table->foreignId('categoria_producto_id')->constrained('categoria_producto');
            $table->foreignId('unidad_medida_id')->constrained('unidad_medida');
            $table->foreignId('proveedor_id')->constrained('proveedor');
            $table->foreignId('ubicacion_id')->constrained('ubicacion');
            $table->enum('estado', ['ACTIVO', 'INACTIVO']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('producto');
    }
};
