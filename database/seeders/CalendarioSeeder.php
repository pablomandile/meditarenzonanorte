<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Publica la página "Calendario" (encabezado + la grilla mensual).
 *
 * **No carga las "Fechas para el calendario" de las clases**, a propósito: los
 * horarios de producción no son los del archivo de datos (el dueño los editó y
 * clonó fichas), así que sembrarlos publicaría clases en días equivocados. Las
 * fechas se completan desde el panel, ficha por ficha, que es donde está el dato
 * verdadero. Una ficha sin fechas simplemente no aparece en el calendario.
 *
 * En una instalación nueva (`migrate:fresh --seed`) las fechas vienen igual, por
 * la vía normal del ContentSeeder.
 *
 * Como usa seedSinglePage(), que reescribe la página entera, **se corre una sola
 * vez** — igual que los otros seeders de página nueva.
 *
 *   php artisan db:seed --class=CalendarioSeeder --force
 */
class CalendarioSeeder extends Seeder
{
    public function run(): void
    {
        (new ContentSeeder)->seedSinglePage('calendario');
    }
}
