<?php

namespace Database\Seeders;

use App\Support\EventLanding;
use Illuminate\Database\Seeder;

/**
 * Crea la plantilla genérica de la landing de evento (Admin › Plantilla de landing)
 * con el contenido de database/seeders/data/event-landing.php.
 *
 * Repetible y seguro en producción: si la plantilla ya existe no la toca. Igual no
 * es imprescindible: la primera landing que se cree la siembra si falta.
 *
 *   php artisan db:seed --class=EventLandingTemplateSeeder --force
 */
class EventLandingTemplateSeeder extends Seeder
{
    public function run(): void
    {
        EventLanding::seedTemplate();
    }
}
