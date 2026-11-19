<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\Section;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Agrega a "Gratis" el bloque que muestra las fichas de las otras páginas con
 * "Gratuito" tildado, debajo del título y de las fichas propias de la página.
 *
 * Repetible y seguro en producción: si la página ya tiene el bloque no lo
 * duplica, y no toca el contenido de ninguna sección.
 *
 * Si el bloque quedó arriba del título lo baja. Pasó en los servidores: se
 * ubicaba después de la ficha 'oferta-sabados', que allá ya no existe, y sin
 * ella entraba primero.
 *
 *   php artisan db:seed --class=GratisEspejoSeeder --force
 */
class GratisEspejoSeeder extends Seeder
{
    public const KEY = 'gratuitas-de-otras-paginas';

    public function run(): void
    {
        (new ContentSeeder)->seedMissingSection('gratis', self::KEY);

        $this->placeBelowTheHeader();
    }

    private function placeBelowTheHeader(): void
    {
        $page = Page::where('slug', 'gratis')->first();

        if (! $page) {
            return;
        }

        $sections = $page->sections()->orderBy('position')->get()->values();
        $block = $sections->firstWhere('key', self::KEY);
        $headerIndex = $sections->search(fn (Section $s) => $s->type === 'page_header');

        if (! $block || $headerIndex === false || $sections->search(fn (Section $s) => $s->is($block)) > $headerIndex) {
            return;
        }

        $rest = $sections->reject(fn (Section $s) => $s->is($block))->values();
        $headerIndex = $rest->search(fn (Section $s) => $s->type === 'page_header');

        // Después de la última ficha propia que sigue al título (las de Gratis y
        // las espejadas quedan juntas); sin fichas, justo debajo del título.
        $after = $headerIndex;
        for ($i = $headerIndex + 1; $i < $rest->count() && $rest[$i]->type === 'class_info'; $i++) {
            $after = $i;
        }

        $ordered = $rest->slice(0, $after + 1)->push($block)->concat($rest->slice($after + 1))->values();

        DB::transaction(function () use ($ordered) {
            foreach ($ordered as $i => $section) {
                if ($section->position !== $i + 1) {
                    $section->update(['position' => $i + 1]);
                }
            }
        });
    }
}
