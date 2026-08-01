<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Adds the cover ("portada"/hero) section to the "Clases semanales" page — the
 * same one that 'eventos-especiales' and 'cursos-y-retiros' already use.
 *
 * Safe to run in production and repeatable: it only inserts the block if the
 * page does not have it yet, right below the page header, and leaves every
 * other section (content, images, order, visibility) untouched.
 *
 *   php artisan db:seed --class=ClasesSemanalesPortadaSeeder --force
 */
class ClasesSemanalesPortadaSeeder extends Seeder
{
    public function run(): void
    {
        (new ContentSeeder())->seedMissingSection('clases-semanales', 'banner');
    }
}
