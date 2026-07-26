<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Adds only the "Abonos" page (and its sections) without touching the rest of
 * the site's content. Safe to run in production: it uses updateOrCreate and
 * never overwrites the other pages the owner may have already edited.
 *
 *   php artisan db:seed --class=AbonosSeeder --force
 */
class AbonosSeeder extends Seeder
{
    public function run(): void
    {
        (new ContentSeeder())->seedSinglePage('abonos');
    }
}
