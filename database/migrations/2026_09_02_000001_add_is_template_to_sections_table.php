<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La ficha plantilla: una sección de la que el dueño clona las demás y que no
     * se puede eliminar ni publicar. Es una columna y no un campo del content
     * porque es una decisión de estructura, como `visible` o `show_on_calendar`.
     *
     * Arranca en false: las secciones que ya existen son todas normales. La única
     * plantilla del sitio —la ficha de "Cursos y Retiros"— la marca el
     * CursosYRetirosFichaSeeder, que corre en el deploy.
     */
    public function up(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->boolean('is_template')->default(false)->after('visible');
        });
    }

    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->dropColumn('is_template');
        });
    }
};
