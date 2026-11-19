<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Agrega a "Gratis" el bloque que muestra las fichas de las otras páginas con
 * "Gratuito" tildado, justo debajo de las fichas propias de la página.
 *
 * Repetible y seguro en producción: si la página ya tiene el bloque no hace
 * nada, y no toca ninguna otra sección.
 *
 *   php artisan db:seed --class=GratisEspejoSeeder --force
 */
class GratisEspejoSeeder extends Seeder
{
    public function run(): void
    {
        (new ContentSeeder)->seedMissingSection('gratis', 'gratuitas-de-otras-paginas');
    }
}
