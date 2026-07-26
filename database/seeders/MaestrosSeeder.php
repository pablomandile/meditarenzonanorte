<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Re-seeds only the "¿Quienes somos?" page from content.php (adding the
 * teacher/person sections) without touching the other pages. Safe to run
 * in production with:
 *
 *   php artisan db:seed --class=MaestrosSeeder --force
 */
class MaestrosSeeder extends Seeder
{
    public function run(): void
    {
        (new ContentSeeder())->seedSinglePage('quienes-somos');
    }
}
