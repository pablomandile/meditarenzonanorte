<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Datos recurrentes" del panel: listas de maestr@s y de lugares que el dueño
     * carga una vez y después elige en los campos "Maestr@" y "Lugar" de las
     * fichas de clase, para no reescribirlos (ni escribirlos distinto) cada vez.
     *
     * Las fichas siguen guardando el texto: estas tablas son sólo el menú de
     * sugerencias, así que un nombre que ya no está en la lista no rompe nada.
     */
    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('venues', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venues');
        Schema::dropIfExists('teachers');
    }
};
