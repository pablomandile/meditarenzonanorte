<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La ficha de cada maestr@ de "Datos recurrentes": una descripción y una foto.
     * Se ven al tocar su nombre en una ficha de clase y en las secciones
     * "Maestro / persona" que lo eligen (¿Quiénes somos?).
     */
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->string('photo')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn(['description', 'photo']);
        });
    }
};
