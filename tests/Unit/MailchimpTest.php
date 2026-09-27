<?php

namespace Tests\Unit;

use App\Exceptions\MailchimpException;
use App\Support\Mailchimp;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MailchimpTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['services.mailchimp.key' => 'fakekeyfortests-us21']);
    }

    public function test_it_needs_a_key_that_carries_its_data_center(): void
    {
        config(['services.mailchimp.key' => null]);
        $this->assertFalse(Mailchimp::configured());

        // Una key pegada a medias, sin el "-us21" del final: no se puede saber contra
        // qué servidor hablar, así que se avisa en vez de armar una URL rota.
        config(['services.mailchimp.key' => 'fakekeyfortests']);
        $this->assertFalse(Mailchimp::configured());

        config(['services.mailchimp.key' => 'fakekeyfortests-us21']);
        $this->assertTrue(Mailchimp::configured());
    }

    public function test_it_talks_to_the_data_center_of_the_key_with_a_bearer_token(): void
    {
        Http::fake(['*' => Http::response(['account_name' => 'Meditar en Zona Norte'])]);

        Mailchimp::account();

        Http::assertSent(function (Request $request) {
            return str_starts_with($request->url(), 'https://us21.api.mailchimp.com/3.0/')
                && $request->hasHeader('Authorization', 'Bearer fakekeyfortests-us21');
        });
    }

    public function test_it_creates_a_regular_campaign_and_returns_its_id(): void
    {
        Http::fake(['*/campaigns' => Http::response(['id' => 'f4c3b00c'])]);

        $id = Mailchimp::createCampaign('list-123', [
            'subject_line' => 'Clases de esta semana',
            'title' => 'Semanal 2026-09-21',
            'preview_text' => '',
        ]);

        $this->assertSame('f4c3b00c', $id);

        Http::assertSent(function (Request $request) {
            $body = $request->data();

            return $request->method() === 'POST'
                && $body['type'] === 'regular'
                && $body['recipients']['list_id'] === 'list-123'
                && $body['settings']['subject_line'] === 'Clases de esta semana'
                // El nombre interno de la campaña, que no es el asunto.
                && $body['settings']['title'] === 'Semanal 2026-09-21'
                // Lo vacío no se manda: Mailchimp rechaza varios campos en blanco.
                && ! array_key_exists('preview_text', $body['settings']);
        });
    }

    public function test_the_sending_actions_accept_an_empty_204(): void
    {
        Http::fake(['*' => Http::response(status: 204)]);

        // Ninguna de las tres devuelve cuerpo; lo que se prueba es que no explote al
        // intentar decodificar una respuesta vacía.
        Mailchimp::setContent('f4c3b00c', '<h1>Hola</h1>', 'Hola');
        Mailchimp::sendTest('f4c3b00c', ['pablo@example.com']);
        Mailchimp::send('f4c3b00c');

        Http::assertSentCount(3);

        Http::assertSent(fn (Request $r) => $r->method() === 'PUT'
            && str_ends_with($r->url(), '/campaigns/f4c3b00c/content')
            && $r->data()['html'] === '<h1>Hola</h1>'
            && $r->data()['plain_text'] === 'Hola');

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/campaigns/f4c3b00c/actions/test')
            && $r->data()['test_emails'] === ['pablo@example.com']
            && $r->data()['send_type'] === 'html');

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/campaigns/f4c3b00c/actions/send'));
    }

    public function test_it_turns_a_mailchimp_error_into_something_readable(): void
    {
        Http::fake(['*' => Http::response([
            'type' => 'https://mailchimp.com/developer/marketing/docs/errors/',
            'title' => 'Invalid Resource',
            'status' => 400,
            'detail' => 'Your HTML is missing an unsubscribe link.',
            'errors' => [
                ['field' => 'html', 'message' => 'Agregá el enlace de baja.'],
            ],
        ], 400)]);

        try {
            Mailchimp::send('f4c3b00c');
            $this->fail('Tendría que haber lanzado MailchimpException.');
        } catch (MailchimpException $e) {
            // El detalle general y el error del campo concreto, los dos.
            $this->assertStringContainsString('missing an unsubscribe link', $e->getMessage());
            $this->assertStringContainsString('html: Agregá el enlace de baja.', $e->getMessage());
            $this->assertSame(400, $e->status());
        }
    }

    public function test_an_error_without_a_readable_body_still_says_something(): void
    {
        Http::fake(['*' => Http::response('<html>502 Bad Gateway</html>', 502)]);

        $this->expectException(MailchimpException::class);
        $this->expectExceptionMessage('Mailchimp respondió 502 sin detalle.');

        Mailchimp::campaign('f4c3b00c');
    }

    public function test_it_does_not_repeat_a_send_when_mailchimp_rejects_it(): void
    {
        // Un 4xx no se arregla repitiéndolo, y repetir un envío a ciegas es lo último
        // que queremos: el reintento es sólo para cuando no se pudo hablar.
        Http::fake(['*' => Http::response(['detail' => 'Campaign cannot be sent.'], 400)]);

        try {
            Mailchimp::send('f4c3b00c');
        } catch (MailchimpException) {
            // Esperado.
        }

        Http::assertSentCount(1);
    }
}
