<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Los videos de "Ayuda → Tutoriales" del panel: un nombre y el enlace de un
     * video de YouTube. Se guardan y se ordenan como las FAQ. Son solo para quien
     * administra el sitio, así que no llevan `visible` ni tocan el sitio público.
     */
    public function up(): void
    {
        Schema::create('tutorials', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('youtube_url', 500);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutorials');
    }
};
