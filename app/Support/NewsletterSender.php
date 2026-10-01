<?php

namespace App\Support;

use App\Exceptions\MailchimpException;
use App\Models\Newsletter;
use App\Models\Setting;
use Throwable;

/**
 * El envío, en un solo lugar: lo usan igual el botón "Enviar ahora" del panel y el
 * cron que dispara los programados.
 *
 * Todo el cuidado de esta clase es por una sola cosa: NO MANDAR DOS VECES. Un mail
 * repetido a mil quinientas personas no se puede deshacer, así que el orden de los
 * pasos no es casual:
 *
 *  1. Se "reclama" el newsletter con un UPDATE condicional. El que gana sigue; el que
 *     llega segundo ve cero filas afectadas y se va. Así dos procesos simultáneos
 *     —el cron y un click, por ejemplo— no pueden enviar los dos.
 *  2. Si ya había una campaña creada, se le pregunta a Mailchimp cómo quedó antes de
 *     hacer nada. Si ya salió, se anota y se corta.
 *  3. El id de la campaña se guarda APENAS se crea, antes de mandar. Si la conexión se
 *     corta justo en el envío, el paso 2 del próximo intento tiene con qué averiguar
 *     si aquello salió o no.
 */
class NewsletterSender
{
    /**
     * Lo que falta para poder enviar, escrito para que se entienda en el panel.
     *
     * @return array<int, string>
     */
    public static function missing(): array
    {
        $missing = [];

        if (! Mailchimp::configured()) {
            $missing[] = 'Falta la API key de Mailchimp en el servidor (MAILCHIMP_API_KEY).';
        }

        if (blank(Setting::get('newsletter_list_id'))) {
            $missing[] = 'Falta elegir la audiencia en Ajustes del sitio → Newsletter.';
        }

        if (blank(Setting::get('newsletter_from_name'))) {
            $missing[] = 'Falta el nombre del remitente en Ajustes del sitio → Newsletter.';
        }

        if (blank(Setting::get('newsletter_reply_to'))) {
            $missing[] = 'Falta la casilla de respuesta en Ajustes del sitio → Newsletter.';
        }

        return $missing;
    }

    public static function ready(): bool
    {
        return self::missing() === [];
    }

    /**
     * ¿Se puede mandar a la audiencia de verdad desde acá?
     *
     * Sólo en producción. En la máquina de desarrollo hay una copia de la base con
     * los mismos datos —incluida la audiencia configurada—, así que un click en
     * "Enviar ahora" mientras se prueba algo saldría a las mil quinientas personas
     * reales. No hay forma de deshacer eso, así que no se permite.
     *
     * Las pruebas a la casilla propia NO pasan por acá: ésas son justamente lo que
     * hay que poder hacer mientras se desarrolla.
     */
    public static function liveSendsAllowed(): bool
    {
        // Hay que habilitarlo A MANO, en un solo servidor.
        //
        // La tentación es atarlo a app()->isProduction(), pero este proyecto tiene DOS
        // instalaciones en Hostinger —la de desarrollo y la de producción— y las dos
        // corren con APP_ENV=production, porque las dos son servidores de verdad. Con
        // ese criterio, un "Enviar ahora" apretado en el entorno de pruebas saldría a
        // la audiencia real igual que en producción.
        //
        // Por eso el que no dice nada no manda. Si alguien se olvida de poner esto,
        // el peor caso es que un envío no salga y el panel lo explique; al revés, el
        // peor caso son novecientos correos que no se pueden deshacer.
        return (bool) config('services.mailchimp.live_sends');
    }

    /**
     * El modo de envío que eligió el dueño en Ajustes: 'auto' o 'manual'.
     *
     * Es otra cosa que liveSendsAllowed(), y las dos tienen que dar verde para que el
     * cron mande. Aquél dice qué SERVIDOR puede mandar y vive en el .env porque es de
     * la instalación; éste es una decisión editorial —"este mes reviso cada envío
     * antes de que salga"— y por eso se edita desde el panel.
     */
    public static function mode(): string
    {
        return Setting::get('newsletter_mode', 'auto') === 'manual' ? 'manual' : 'auto';
    }

    /** ¿El cron puede mandar los programados por su cuenta? */
    public static function autoSendEnabled(): bool
    {
        return self::mode() === 'auto';
    }

    /** Por qué no se puede mandar, para decirlo en el panel. */
    public static function blockedReason(): ?string
    {
        return self::liveSendsAllowed()
            ? null
            : 'En este servidor los envíos a la audiencia real están bloqueados (falta MAILCHIMP_LIVE_SENDS=true en el .env, y sólo va en producción). Mandate una prueba a tu casilla, que sí funciona.';
    }

    /**
     * Congela el mail: lo dibuja con el contenido de hoy y lo guarda.
     *
     * A partir de acá el envío ya no depende del sitio. Borrar una ficha el martes no
     * rompe el envío del miércoles, editarla no cambia lo que se decidió mandar, y lo
     * que salió queda archivado tal cual para cuando alguien pregunte qué decía.
     */
    public static function freeze(Newsletter $newsletter): void
    {
        $newsletter->forceFill([
            'html' => NewsletterRenderer::html($newsletter),
            'plain_text' => NewsletterRenderer::text($newsletter),
        ])->save();
    }

    /**
     * Manda una prueba a las casillas indicadas, sin tocar la audiencia.
     *
     * Mailchimp sólo sabe mandar pruebas de una campaña que exista, así que se crea
     * una, se usa y se borra: si no, el tablero se llena de borradores.
     *
     * @param  array<int, string>  $emails
     */
    public static function sendTest(Newsletter $newsletter, array $emails): void
    {
        $campaignId = Mailchimp::createCampaign(
            (string) Setting::get('newsletter_list_id'),
            self::campaignSettings($newsletter, prefix: '[PRUEBA] '),
        );

        try {
            Mailchimp::setContent(
                $campaignId,
                NewsletterRenderer::html($newsletter),
                NewsletterRenderer::text($newsletter),
            );

            Mailchimp::sendTest($campaignId, $emails);
        } finally {
            // Que la limpieza falle no puede tapar el error de arriba, que es el que
            // le importa a quien apretó el botón.
            try {
                Mailchimp::deleteCampaign($campaignId);
            } catch (Throwable) {
                // Queda un borrador suelto en Mailchimp; no es motivo para fallar.
            }
        }
    }

    /**
     * El envío de verdad. Silencioso si otro ya lo tomó.
     *
     * @throws Throwable lo que devuelva Mailchimp, después de dejarlo marcado como fallido
     */
    public static function send(Newsletter $newsletter): void
    {
        // El último cerrojo, antes de tocar nada. Está acá y no sólo en el panel para
        // que valga también si a esto lo llama el cron, un comando o un test.
        if (! self::liveSendsAllowed()) {
            throw new MailchimpException((string) self::blockedReason());
        }

        // El reclamo atómico: una sola de las llamadas simultáneas afecta una fila.
        $claimed = Newsletter::query()
            ->whereKey($newsletter->getKey())
            ->whereIn('status', [Newsletter::SCHEDULED, Newsletter::FAILED])
            ->update(['status' => Newsletter::SENDING]);

        if ($claimed === 0) {
            return;
        }

        $newsletter->refresh();

        try {
            if (filled($newsletter->mailchimp_campaign_id)) {
                $campaign = Mailchimp::campaign($newsletter->mailchimp_campaign_id);

                // Ya está en marcha o ya salió: se anota y no se toca nada más. Un
                // reenvío "por las dudas" es exactamente el error que no se perdona.
                if (in_array($campaign['status'] ?? '', ['sending', 'sent', 'schedule'], true)) {
                    self::markSent($newsletter, $campaign);

                    return;
                }
            } else {
                $id = Mailchimp::createCampaign(
                    (string) Setting::get('newsletter_list_id'),
                    self::campaignSettings($newsletter),
                );

                // Guardado ANTES de enviar: es el único rastro que queda si lo que
                // viene se corta por la mitad.
                $newsletter->forceFill(['mailchimp_campaign_id' => $id])->save();
            }

            Mailchimp::setContent(
                $newsletter->mailchimp_campaign_id,
                (string) $newsletter->html,
                (string) $newsletter->plain_text,
            );

            Mailchimp::send($newsletter->mailchimp_campaign_id);

            self::markSent($newsletter, Mailchimp::campaign($newsletter->mailchimp_campaign_id));
        } catch (Throwable $e) {
            // El id de campaña NO se borra: es lo que hace seguro el reintento.
            $newsletter->forceFill([
                'status' => Newsletter::FAILED,
                'error' => $e->getMessage(),
            ])->save();

            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $campaign
     */
    private static function markSent(Newsletter $newsletter, array $campaign): void
    {
        $sent = (int) ($campaign['emails_sent'] ?? 0);

        // Recién disparada, Mailchimp todavía informa 0 enviados. Para el contador del
        // panel vale igual el tamaño de la audiencia, que es lo que va a consumir.
        if ($sent === 0) {
            $sent = (int) (Mailchimp::list((string) Setting::get('newsletter_list_id'))['stats']['member_count'] ?? 0);
        }

        $newsletter->forceFill([
            'status' => Newsletter::SENT,
            'sent_at' => now(),
            'recipient_count' => $sent,
            'error' => null,
        ])->save();
    }

    /**
     * @return array<string, mixed>
     */
    private static function campaignSettings(Newsletter $newsletter, string $prefix = ''): array
    {
        return [
            'subject_line' => $prefix.$newsletter->subject,
            // El nombre interno con el que se ve en el tablero de Mailchimp, que no es
            // el asunto: con la fecha adelante quedan ordenadas solas.
            'title' => $prefix.now(EventCalendar::TIMEZONE)->format('Y-m-d').' · '.$newsletter->subject,
            'preview_text' => (string) $newsletter->preview_text,
            'from_name' => (string) Setting::get('newsletter_from_name'),
            'reply_to' => (string) Setting::get('newsletter_reply_to'),
        ];
    }
}
