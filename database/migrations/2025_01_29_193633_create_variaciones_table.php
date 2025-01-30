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
        Schema::create('variaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('color_id')->nullable()->constrained('color')->onDelete('set null');
            $table->foreignId('longitud_id')->nullable()->constrained('longitud')->onDelete('set null');
            $table->foreignId('tamano_id')->nullable()->constrained('tamano')->onDelete('set null');
            $table->timestamps();

            $table->unique(['color_id', 'longitud_id', 'tamano_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variaciones');
    }
};
