<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Los newsletters que se arman en el panel y se mandan por la API de Mailchimp.
     *
     * Dos decisiones que explican la forma de la tabla:
     *
     *  - `content` guarda las REFERENCIAS a lo elegido ({kind: section|event, id}),
     *    igual que las secciones guardan todo en su JSON. No hay tabla pivote porque
     *    esto nunca se consulta al revés ("¿en qué newsletters salió esta ficha?").
     *
     *  - `html` y `plain_text` guardan el mail YA ARMADO, congelado en el momento de
     *    programarlo. El cron no vuelve a dibujar nada: sube lo que está acá. Así,
     *    borrar una ficha el martes no rompe el envío del miércoles, editarla no
     *    cambia lo que ya se decidió mandar, y queda archivado tal cual lo que salió.
     */
    public function up(): void
    {
        Schema::create('newsletters', function (Blueprint $table) {
            $table->id();
            $table->string('type')->default('weekly');   // weekly | monthly
            $table->string('subject');
            $table->string('preview_text')->nullable();
            $table->json('content')->nullable();
            $table->longText('html')->nullable();
            $table->longText('plain_text')->nullable();

            // draft → scheduled → sending → sent, y failed cuando Mailchimp se queja.
            $table->string('status')->default('draft')->index();

            // Se guarda en UTC, como todo Laravel; el panel lo muestra en hora argentina.
            $table->dateTime('scheduled_at')->nullable()->index();
            $table->dateTime('sent_at')->nullable();

            // El id de la campaña se guarda apenas Mailchimp la crea, ANTES de enviar:
            // es lo que permite saber, si algo se corta a mitad de camino, si aquello
            // ya salió o no. El unique es el cinturón además de los tirantes.
            $table->string('mailchimp_campaign_id')->nullable()->unique();
            $table->unsignedInteger('recipient_count')->nullable();
            $table->text('error')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletters');
    }
};
