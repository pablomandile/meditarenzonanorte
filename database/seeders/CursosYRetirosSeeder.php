<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Adds only the "Cursos y Retiros" page (and its sections) without touching the
 * rest of the site's content. Safe to run in production: it uses updateOrCreate
 * and never overwrites the other pages the owner may have already edited.
 *
 *   php artisan db:seed --class=CursosYRetirosSeeder --force
 */
class CursosYRetirosSeeder extends Seeder
{
    public function run(): void
    {
        (new ContentSeeder())->seedSinglePage('cursos-y-retiros');
    }
}
