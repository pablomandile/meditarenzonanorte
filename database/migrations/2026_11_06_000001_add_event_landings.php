<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Landing propia para los eventos con "Link interno".
 *
 * - pages.kind: 'page' (las páginas del sitio, como hasta ahora), 'event_template'
 *   (la plantilla genérica que se edita desde Eventos) o 'event_landing' (la copia
 *   de cada evento). Sólo las 'page' salen en el menú, en Admin › Páginas y en la
 *   ruta /{slug}; las landings se publican en /eventos-especiales/{slug}.
 * - pages.event_id: el evento dueño de la landing.
 * - events.link_type: 'external' (la URL de siempre) o 'internal' (la landing).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('kind', 20)->default('page')->after('slug')->index();
            $table->foreignId('event_id')->nullable()->after('kind')->constrained()->nullOnDelete();
        });

        Schema::table('events', function (Blueprint $table) {
            $table->string('link_type', 10)->default('external')->after('cta_url');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('event_id');
            $table->dropIndex(['kind']);
            $table->dropColumn('kind');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('link_type');
        });
    }
};
