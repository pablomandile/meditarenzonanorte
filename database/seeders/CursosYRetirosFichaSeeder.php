<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Agrega a "Cursos y Retiros" la ficha **plantilla**: una "Información de clase"
 * oculta y no eliminable (columna is_template) de la que el dueño clona la ficha
 * de cada curso o retiro. Va justo debajo de la portada.
 *
 * Repetible y seguro en producción: si la página ya tiene la plantilla no hace
 * nada, y no toca ninguna otra sección.
 *
 *   php artisan db:seed --class=CursosYRetirosFichaSeeder --force
 */
class CursosYRetirosFichaSeeder extends Seeder
{
    public function run(): void
    {
        (new ContentSeeder)->seedMissingSection('cursos-y-retiros', 'plantilla');
    }
}
