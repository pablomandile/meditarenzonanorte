<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Agrega a "Voluntariado" el encabezado de página y la portada, para que empiece
 * como el resto de las páginas internas.
 *
 * El orden de las dos llamadas importa: cada sección se ubica debajo de la que la
 * precede en el archivo de datos, así que el encabezado tiene que existir antes
 * de insertar la portada.
 *
 * Repetible y seguro en producción: sólo inserta lo que falta y no toca el
 * contenido, las imágenes, el orden ni la visibilidad de las demás secciones.
 *
 *   php artisan db:seed --class=VoluntariadoPortadaSeeder --force
 */
class VoluntariadoPortadaSeeder extends Seeder
{
    public function run(): void
    {
        $content = new ContentSeeder;

        $content->seedMissingSection('voluntariado', 'titulo');
        $content->seedMissingSection('voluntariado', 'banner');
    }
}
