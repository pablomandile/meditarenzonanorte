<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El tilde "mostrar en el calendario" de cada ficha de clase.
     *
     * Entra en true: las fichas con fechas cargadas ya se están mostrando en el
     * calendario, y arrancar en false las haría desaparecer del sitio publicado.
     * Es una columna y no un campo del content porque es una decisión de
     * publicación, como `visible`, y no contenido de la sección.
     */
    public function up(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->boolean('show_on_calendar')->default(true)->after('visible');
        });
    }

    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->dropColumn('show_on_calendar');
        });
    }
};
