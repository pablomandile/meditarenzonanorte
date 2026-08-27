<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Cambia las dos tarjetas del pie que todavía usaban un PNG —"Cómo transformar
 * tu vida" y "Budismo moderno"— por las mismas imágenes en webp: la primera pesa
 * la décima parte y la segunda viene al triple de resolución, para que no se vea
 * borrosa en las pantallas de mucha densidad.
 *
 * Repetible y seguro en producción: sólo toca las tarjetas que siguen apuntando
 * al PNG viejo, así que si alguien ya cambió la imagen a mano no la pisa.
 *
 *   php artisan db:seed --class=RecursosWebpSeeder --force
 */
class RecursosWebpSeeder extends Seeder
{
    public function run(): void
    {
        $contenido = new ContentSeeder;

        $contenido->replaceFooterResourceImage('shared/CTTV-Pack-3D-2017-web.png', 'shared/CTTV-Pack-3D-2017-web.webp');
        $contenido->replaceFooterResourceImage('shared/bm2-2.png', 'shared/bm2-2.webp');
    }
}
