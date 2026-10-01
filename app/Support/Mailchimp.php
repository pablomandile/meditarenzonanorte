<?php

namespace App\Support;

use App\Exceptions\MailchimpException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * El pedazo de Mailchimp que usamos: elegir la audiencia, crear una campaña,
 * ponerle el HTML y mandarla.
 *
 * Es un cliente escrito a mano sobre el facade Http y no el SDK oficial a propósito:
 * el SDK es un cliente generado enorme, y acá hacen falta siete llamadas contadas.
 *
 * La API key termina con el centro de datos de la cuenta ("...-us21"), y ese sufijo
 * ES el subdominio al que hay que pegarle. Una key sin sufijo no sirve para nada, así
 * que se avisa en vez de armar una URL rota y esperar un 404 incomprensible.
 *
 * Dos cosas que conviene tener claras antes de tocar esto:
 *
 *  - La cuota de envíos es de la CUENTA, no de la interfaz: mandar por acá gasta
 *    exactamente lo mismo que mandar desde el editor de ellos, ni más ni menos.
 *  - Lo que sí depende del plan es PROGRAMAR (actions/schedule es de Essentials para
 *    arriba). Por eso acá no hay un schedule(): la fecha y la hora las maneja el cron
 *    propio, que además nos deja congelar el contenido de nuestro lado.
 *
 * Doc: https://mailchimp.com/developer/marketing/docs/fundamentals/
 */
class Mailchimp
{
    /** ¿Hay una key con su centro de datos? Sin esto el panel esconde los botones. */
    public static function configured(): bool
    {
        $key = (string) config('services.mailchimp.key');

        return $key !== '' && str_contains($key, '-');
    }

    /**
     * Los datos de la cuenta: nombre, plan y total de suscriptores.
     *
     * Es la llamada que contesta "¿cuántos mails puedo mandar?" sin tener que
     * adivinar mirando la web de precios, que cambia cada tanto.
     *
     * @return array<string, mixed>
     */
    public static function account(): array
    {
        return self::get('/', [
            'fields' => 'account_name,email,pricing_plan_type,total_subscribers',
        ]);
    }

    /**
     * Las audiencias de la cuenta, para el desplegable de Ajustes.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function lists(): array
    {
        $body = self::get('/lists', [
            'count' => 100,
            'fields' => 'lists.id,lists.name,lists.stats.member_count',
        ]);

        return $body['lists'] ?? [];
    }

    /**
     * Una audiencia con sus estadísticas (de ahí sale member_count).
     *
     * @return array<string, mixed>
     */
    public static function list(string $id): array
    {
        return self::get("/lists/{$id}");
    }

    /**
     * Crea la campaña —todavía vacía y sin enviar— y devuelve su id.
     *
     * Ojo con `settings.title`: NO es el asunto, es el nombre interno con el que la
     * campaña aparece en el tablero de Mailchimp. El asunto es `subject_line`.
     *
     * @param  array<string, mixed>  $settings
     */
    public static function createCampaign(string $listId, array $settings): string
    {
        $body = self::post('/campaigns', [
            'type' => 'regular',
            'recipients' => ['list_id' => $listId],
            'settings' => array_filter([
                ...$settings,
                // Aunque el HTML ya va con los estilos puestos a mano, que Mailchimp
                // los vuelva a meter en cada etiqueta no molesta y cubre lo que se
                // nos escape en un <style>.
                'inline_css' => true,
            ], fn ($value) => $value !== null && $value !== ''),
        ]);

        return (string) $body['id'];
    }

    public static function setContent(string $id, string $html, string $plainText): void
    {
        self::call('put', "/campaigns/{$id}/content", [
            'html' => $html,
            'plain_text' => $plainText,
        ]);
    }

    /**
     * Manda la prueba a las casillas que se le pasen, sin tocar la audiencia.
     *
     * @param  array<int, string>  $emails
     */
    public static function sendTest(string $id, array $emails): void
    {
        self::call('post', "/campaigns/{$id}/actions/test", [
            'test_emails' => array_values($emails),
            'send_type' => 'html',
        ]);
    }

    /** El envío de verdad. Contesta 204 sin cuerpo: acá no hay nada que devolver. */
    public static function send(string $id): void
    {
        self::call('post', "/campaigns/{$id}/actions/send");
    }

    /**
     * Borra una campaña que todavía no se envió.
     *
     * Se usa para la prueba: mandarse el mail a uno mismo obliga a crear una campaña,
     * y si no se limpia, el tablero de Mailchimp se llena de borradores.
     */
    public static function deleteCampaign(string $id): void
    {
        self::call('delete', "/campaigns/{$id}");
    }

    /**
     * El estado de una campaña: save, paused, schedule, sending o sent.
     *
     * Es lo que se consulta antes de reintentar un envío que quedó a mitad de camino,
     * para no mandar dos veces lo mismo.
     *
     * @return array<string, mixed>
     */
    public static function campaign(string $id): array
    {
        return self::get("/campaigns/{$id}", [
            'fields' => 'id,status,emails_sent,send_time,archive_url',
        ]);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private static function get(string $path, array $query = []): array
    {
        return self::call('get', $path, $query);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private static function post(string $path, array $payload = []): array
    {
        return self::call('post', $path, $payload);
    }

    /**
     * El único lugar que habla con la red: autentica, resuelve errores y devuelve el
     * cuerpo ya decodificado (array vacío cuando contestan 204, que es lo normal en
     * las acciones de enviar).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function call(string $method, string $path, array $data = []): array
    {
        $response = self::http()->{$method}($path, $data);

        if ($response->failed()) {
            throw MailchimpException::fromResponse($response);
        }

        $body = $response->json();

        return is_array($body) ? $body : [];
    }

    private static function http(): PendingRequest
    {
        return Http::withToken(self::key())
            ->baseUrl(self::baseUrl())
            ->acceptJson()
            ->timeout(20)
            // Se reintenta SÓLO cuando no se pudo hablar con el servidor. Un 4xx no se
            // arregla repitiéndolo, y repetir un envío a ciegas es justo lo que no
            // queremos: si algo quedó dudoso, lo resuelve el que llama consultando el
            // estado de la campaña.
            ->retry(2, 500, fn ($e) => $e instanceof ConnectionException, throw: false);
    }

    private static function key(): string
    {
        $key = (string) config('services.mailchimp.key');

        if ($key === '') {
            throw new MailchimpException('Falta la API key de Mailchimp (MAILCHIMP_API_KEY en el .env).');
        }

        return $key;
    }

    /**
     * El centro de datos sale del final de la key: "abc...123-us21" pega contra
     * https://us21.api.mailchimp.com/3.0.
     */
    private static function baseUrl(): string
    {
        $key = self::key();
        $dc = substr(strrchr($key, '-') ?: '', 1);

        if ($dc === '' || ! preg_match('/^[a-z]{2}\d+$/', $dc)) {
            throw new MailchimpException(
                'La API key de Mailchimp no termina con su centro de datos (tiene que ser algo como "...-us21"). '
                .'Copiala de nuevo entera desde Account → Extras → API keys.'
            );
        }

        return "https://{$dc}.api.mailchimp.com/3.0";
    }
}
