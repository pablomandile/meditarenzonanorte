<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El enlace propio del afiche, distinto al del botón: la imagen suele llevar
     * a la publicación o al programa, y el botón a la inscripción.
     *
     * Nulo por defecto, y en ese caso la imagen sigue yendo a donde va el botón,
     * que es lo que hacía hasta ahora.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('image_url', 500)->nullable()->after('image_path');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('image_url');
        });
    }
};
