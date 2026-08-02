<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Agrega a "Cursos y Retiros" una ficha con la misma estructura que las de las
 * clases (class_info: título, descripción, horario, lugar, precio, botón e
 * imagen), justo debajo de la portada.
 *
 * Entra **oculta**: el contenido es una plantilla de relleno, así que la página
 * pública no la muestra hasta que se complete y se muestre desde el panel.
 *
 * Repetible y seguro en producción: si la página ya tiene la sección no hace
 * nada, y no toca ninguna otra.
 *
 *   php artisan db:seed --class=CursosYRetirosFichaSeeder --force
 */
class CursosYRetirosFichaSeeder extends Seeder
{
    public function run(): void
    {
        (new ContentSeeder())->seedMissingSection('cursos-y-retiros', 'curso', visible: false);
    }
}
