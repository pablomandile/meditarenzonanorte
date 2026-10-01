<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Los newsletters programados.
 *
 * Cada cinco minutos y no cada minuto: un envío que sale cuatro minutos tarde no le
 * cambia la vida a nadie, y así se le pega menos a Mailchimp.
 *
 * withoutOverlapping es la primera de las dos defensas contra mandar dos veces: evita
 * que dos corridas se pisen. La segunda, la que de verdad importa, está en
 * NewsletterSender, que reclama cada newsletter con un UPDATE condicional antes de
 * tocar nada.
 *
 * Para que esto corra en el servidor hace falta UN cron en hPanel, cada minuto, que
 * llame a `artisan schedule:run`. Sin `cd ... &&`, que el ejecutor de hPanel no
 * soporta: el comando va con la ruta completa al php y al artisan.
 */
Schedule::command('newsletter:send-due')
    ->everyFiveMinutes()
    ->withoutOverlapping(10);
