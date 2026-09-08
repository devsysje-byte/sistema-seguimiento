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
        Schema::create('modalidades', function (Blueprint $table) {
            // Se cambia a id_modalidad para mantener la coherencia con tu tabla trámites
            $table->id('id_modalidad');

            // Columnas requeridas por el Seeder
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->integer('duracion_maxima_meses')->nullable();
            $table->text('requisitos_minimos')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modalidades');
    }
};
