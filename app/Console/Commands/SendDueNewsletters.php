<?php

namespace App\Console\Commands;

use App\Models\Newsletter;
use App\Support\NewsletterSender;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Manda los newsletters a los que ya les llegó la hora.
 *
 * Esto es la "programación de envíos": el plan de Mailchimp que se usa no la incluye
 * (actions/schedule es de Essentials para arriba), así que la fecha y la hora las
 * guarda esta app y este comando, llamado por el cron, dispara lo que venció.
 *
 * Todo el cuidado de no mandar dos veces vive en NewsletterSender: acá no hay que
 * agregar ninguno, y sobre todo no hay que agregar reintentos.
 */
class SendDueNewsletters extends Command
{
    protected $signature = 'newsletter:send-due';

    protected $description = 'Envía los newsletters programados cuya fecha ya pasó';

    public function handle(): int
    {
        // En modo manual el cron no manda nada. Lo que ya estaba programado NO se
        // toca: queda esperando con su fecha vencida y el panel lo muestra como "listo
        // para que lo mandes". Cancelarlo solo sería decidir por el dueño algo que él
        // no pidió, y perder el trabajo de haberlo armado.
        if (! NewsletterSender::autoSendEnabled()) {
            return self::SUCCESS;
        }

        $due = Newsletter::due()->get();

        if ($due->isEmpty()) {
            // Corre cada cinco minutos: sin novedad no se dice nada, para que el log
            // del cron sirva para algo cuando haya que leerlo.
            return self::SUCCESS;
        }

        foreach ($due as $newsletter) {
            try {
                NewsletterSender::send($newsletter);

                $newsletter->refresh();

                $this->components->info("Newsletter #{$newsletter->id} enviado a {$newsletter->recipient_count} suscriptores.");
                Log::info('Newsletter enviado', ['id' => $newsletter->id, 'destinatarios' => $newsletter->recipient_count]);
            } catch (Throwable $e) {
                // El newsletter ya quedó marcado como fallido con el motivo a la vista
                // en el panel; acá sólo se deja rastro para quien mire el log.
                $this->components->error("Newsletter #{$newsletter->id}: {$e->getMessage()}");
                Log::error('Falló el envío del newsletter', ['id' => $newsletter->id, 'error' => $e->getMessage()]);
            }
        }

        // Siempre 0, incluso si alguno falló: un código de salida distinto en el cron
        // de un hosting compartido no lo lee nadie, y haría ruido sin avisar a nadie.
        return self::SUCCESS;
    }
}
