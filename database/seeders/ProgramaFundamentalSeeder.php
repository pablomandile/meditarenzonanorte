<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Adds only the "Programa Fundamental" page (and its sections) without
 * touching the rest of the site. Safe to run in production:
 *
 *   php artisan db:seed --class=ProgramaFundamentalSeeder --force
 */
class ProgramaFundamentalSeeder extends Seeder
{
    public function run(): void
    {
        (new ContentSeeder())->seedSinglePage('programa-fundamental');
    }
}
