<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * El tipo `quote` guardaba una sola cita ('quote' + 'author'). Pasa a ser
 * `reviews`, un repetidor de reseñas, para poder cargar varias y mostrarlas en
 * un carrusel. La cita que ya estaba se convierte en la primera reseña.
 *
 * Va por DB::table y no por el modelo Section a propósito: el saving() del
 * modelo fuerza visible = false en las plantillas, y una migración de contenido
 * no tiene por qué cambiarle la visibilidad a nadie.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('sections')->where('type', 'quote')->get() as $section) {
            $content = json_decode($section->content ?? '{}', true) ?: [];

            // Idempotente: si ya tiene reseñas, sólo queda cambiarle el tipo.
            if (! isset($content['reviews'])) {
                $content['reviews'] = filled($content['quote'] ?? null)
                    ? [[
                        'quote' => $content['quote'],
                        'author' => $content['author'] ?? null,
                        'rating' => 5,
                    ]]
                    : [];
            }

            unset($content['quote'], $content['author']);

            DB::table('sections')->where('id', $section->id)->update([
                'type' => 'reviews',
                'content' => json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
        }
    }

    public function down(): void
    {
        foreach (DB::table('sections')->where('type', 'reviews')->get() as $section) {
            $content = json_decode($section->content ?? '{}', true) ?: [];
            $first = $content['reviews'][0] ?? [];

            // El tipo viejo sólo entra una cita: las demás se pierden al volver.
            $content['quote'] = $first['quote'] ?? null;
            $content['author'] = $first['author'] ?? null;

            unset($content['reviews'], $content['heading']);

            DB::table('sections')->where('id', $section->id)->update([
                'type' => 'quote',
                'content' => json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
        }
    }
};
