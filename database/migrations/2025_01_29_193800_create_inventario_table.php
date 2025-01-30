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
        Schema::create('inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('producto');
            $table->foreignId('variacion_id')->constrained('variaciones');
            $table->double('precio_unitario')->default(0);
            $table->integer('cantidad')->default(0);
            $table->timestamps();

            $table->unique(['producto_id','variacion_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventario');
    }
};
