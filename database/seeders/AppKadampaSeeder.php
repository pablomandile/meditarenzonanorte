<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Agrega a los recursos del pie la tarjeta de la app de meditación kadampa. La
 * imagen lleva el QR que abre kadampa.org/app, que es también el enlace de la
 * tarjeta: sirve tanto para quien la escanea desde la compu como para quien le
 * hace click desde el teléfono.
 *
 * Repetible y seguro en producción: si la tarjeta ya está no hace nada, y no toca
 * las otras ni ningún otro ajuste.
 *
 *   php artisan db:seed --class=AppKadampaSeeder --force
 */
class AppKadampaSeeder extends Seeder
{
    public function run(): void
    {
        (new ContentSeeder)->seedMissingFooterResource('https://kadampa.org/app');
    }
}
