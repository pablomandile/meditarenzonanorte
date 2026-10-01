<?php

namespace Tests\Feature;

use App\Models\Newsletter;
use App\Models\Page;
use App\Models\Section;
use App\Models\Setting;
use App\Models\User;
use App\Support\NewsletterSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NewsletterSendTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.mailchimp.key' => 'fakekeyfortests-us21']);
        Setting::set('newsletter_list_id', 'list-123');
        Setting::set('newsletter_from_name', 'Meditar en Zona Norte');
        Setting::set('newsletter_reply_to', 'hola@example.org');

        // Los envíos a la audiencia sólo se permiten en producción. Acá se finge el
        // entorno a propósito y con Http::fake() puesto: es la única forma de probar
        // el camino real sin que salga un correo.
        config(['services.mailchimp.live_sends' => true]);
    }

    public function test_in_manual_mode_the_cron_sends_nothing_but_keeps_it_waiting(): void
    {
        Setting::set('newsletter_mode', 'manual');
        $this->fakeHappyMailchimp();

        $newsletter = $this->scheduled();

        $this->artisan('newsletter:send-due')->assertSuccessful();

        Http::assertNothingSent();

        // Lo importante: no se cancela ni vuelve a borrador. Sigue programado, con la
        // fecha vencida, esperando que alguien lo mande. El trabajo no se pierde.
        $newsletter->refresh();
        $this->assertSame(Newsletter::SCHEDULED, $newsletter->status);
        $this->assertTrue($newsletter->scheduled_at->isPast());
    }

    public function test_manual_mode_does_not_block_the_send_now_button(): void
    {
        // El modo manual dice "nada sale SOLO", no "no se puede mandar".
        Setting::set('newsletter_mode', 'manual');
        $this->fakeHappyMailchimp();

        $newsletter = $this->scheduled();
        $newsletter->update([
            'status' => Newsletter::DRAFT,
            'scheduled_at' => null,
            'content' => ['items' => [['kind' => 'section', 'id' => $this->classSection()->id]]],
        ]);

        $this->actingAs(User::factory()->create())
            ->post(route('admin.newsletters.send', $newsletter))
            ->assertSessionHas('success');

        $this->assertSame(Newsletter::SENT, $newsletter->fresh()->status);
    }

    public function test_outside_production_nothing_is_sent_to_the_audience(): void
    {
        // La red de seguridad: en la máquina de desarrollo la base es una copia con la
        // audiencia real configurada, así que un envío de prueba saldría a la gente.
        config(['services.mailchimp.live_sends' => null]);
        $this->fakeHappyMailchimp();

        $newsletter = $this->scheduled();

        $this->artisan('newsletter:send-due')->assertSuccessful();

        Http::assertNothingSent();
        $this->assertNotSame(Newsletter::SENT, $newsletter->fresh()->status);
    }

    /**
     * Una cuenta de Mailchimp que contesta todo bien.
     *
     * Los patrones llevan * al final porque varias llamadas van con ?fields=…, y el
     * comodín de Http::fake se compara contra la URL entera, query incluida. Y van de
     * lo más específico a lo más general: gana el primero que coincide.
     */
    private function fakeHappyMailchimp(string $campaignStatus = 'sending'): void
    {
        Http::fake([
            '*/campaigns/abc123/content' => Http::response(status: 204),
            '*/campaigns/abc123/actions/*' => Http::response(status: 204),
            '*/campaigns/abc123*' => Http::response([
                'id' => 'abc123',
                'status' => $campaignStatus,
                'emails_sent' => 1500,
            ]),
            '*/lists/list-123*' => Http::response(['stats' => ['member_count' => 1500]]),
            '*/campaigns' => Http::response(['id' => 'abc123']),
        ]);
    }

    /** Una ficha de clase de verdad, para los caminos que exigen elegir algo. */
    private function classSection(): Section
    {
        $page = Page::firstOrCreate(
            ['slug' => 'clases-semanales'],
            ['title' => 'Clases semanales', 'menu_order' => 1],
        );

        return Section::create([
            'page_id' => $page->id,
            'type' => 'class_info',
            'key' => 'clase-1',
            'content' => ['heading' => 'Libertad emocional'],
        ]);
    }

    private function scheduled(): Newsletter
    {
        return Newsletter::create([
            'type' => 'weekly',
            'subject' => 'Clases de esta semana',
            'content' => ['items' => []],
            'html' => '<html><body>Hola</body></html>',
            'plain_text' => 'Hola',
            'status' => Newsletter::SCHEDULED,
            'scheduled_at' => now()->subMinute(),
        ]);
    }

    public function test_the_command_sends_what_is_due(): void
    {
        $this->fakeHappyMailchimp();
        $newsletter = $this->scheduled();

        $this->artisan('newsletter:send-due')->assertSuccessful();

        $newsletter->refresh();

        $this->assertSame(Newsletter::SENT, $newsletter->status);
        $this->assertSame('abc123', $newsletter->mailchimp_campaign_id);
        $this->assertSame(1500, $newsletter->recipient_count);
        $this->assertNotNull($newsletter->sent_at);

        // Crear la campaña, subirle el HTML y mandarla: en ese orden.
        Http::assertSent(fn (Request $r) => $r->method() === 'POST' && str_ends_with($r->url(), '/campaigns')
            && $r->data()['recipients']['list_id'] === 'list-123');
        Http::assertSent(fn (Request $r) => $r->method() === 'PUT' && str_ends_with($r->url(), '/campaigns/abc123/content')
            && $r->data()['html'] === '<html><body>Hola</body></html>');
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/campaigns/abc123/actions/send'));
    }

    public function test_running_the_command_twice_does_not_send_twice(): void
    {
        // El escenario que hay que hacer imposible: mil quinientas personas recibiendo
        // el mismo correo dos veces, sin manera de deshacerlo.
        $this->fakeHappyMailchimp();
        $this->scheduled();

        $this->artisan('newsletter:send-due');
        $sentFirstRun = count(Http::recorded());

        $this->artisan('newsletter:send-due');

        // La segunda corrida no encuentra nada vencido: ya está en "enviado".
        $this->assertCount($sentFirstRun, Http::recorded());
        Http::assertSentCount($sentFirstRun);
    }

    public function test_a_newsletter_still_scheduled_in_the_future_waits(): void
    {
        $this->fakeHappyMailchimp();

        $newsletter = $this->scheduled();
        $newsletter->update(['scheduled_at' => now()->addDay()]);

        $this->artisan('newsletter:send-due')->assertSuccessful();

        Http::assertNothingSent();
        $this->assertSame(Newsletter::SCHEDULED, $newsletter->fresh()->status);
    }

    public function test_a_rejection_leaves_it_marked_as_failed_with_the_reason(): void
    {
        Http::fake(['*' => Http::response([
            'title' => 'Invalid Resource',
            'detail' => 'Your HTML is missing an unsubscribe link.',
        ], 400)]);

        $newsletter = $this->scheduled();

        $this->artisan('newsletter:send-due')->assertSuccessful();

        $newsletter->refresh();

        $this->assertSame(Newsletter::FAILED, $newsletter->status);
        $this->assertStringContainsString('unsubscribe link', $newsletter->error);
        $this->assertNull($newsletter->sent_at);
    }

    public function test_retrying_a_failed_send_asks_mailchimp_before_sending_again(): void
    {
        // El caso feo: se creó la campaña, se mandó, y la respuesta se perdió en el
        // camino. Reintentar a ciegas sería mandar todo de nuevo.
        $this->fakeHappyMailchimp(campaignStatus: 'sent');

        $newsletter = $this->scheduled();
        $newsletter->update([
            'status' => Newsletter::FAILED,
            'mailchimp_campaign_id' => 'abc123',
            'error' => 'Se cortó la conexión.',
        ]);

        NewsletterSender::send($newsletter);

        $newsletter->refresh();

        // Se da cuenta de que ya salió y lo anota, sin volver a mandarlo.
        $this->assertSame(Newsletter::SENT, $newsletter->status);
        $this->assertSame(1500, $newsletter->recipient_count);
        Http::assertNotSent(fn (Request $r) => str_ends_with($r->url(), '/actions/send'));
        Http::assertNotSent(fn (Request $r) => $r->method() === 'POST' && str_ends_with($r->url(), '/campaigns'));
    }

    public function test_a_newsletter_already_being_sent_is_left_alone(): void
    {
        $this->fakeHappyMailchimp();

        $newsletter = $this->scheduled();
        $newsletter->update(['status' => Newsletter::SENDING]);

        NewsletterSender::send($newsletter);

        Http::assertNothingSent();
        $this->assertSame(Newsletter::SENDING, $newsletter->fresh()->status);
    }

    public function test_the_send_now_button_goes_through_the_same_road(): void
    {
        $this->fakeHappyMailchimp();

        $newsletter = $this->scheduled();
        $newsletter->update([
            'status' => Newsletter::DRAFT,
            'scheduled_at' => null,
            'content' => ['items' => [['kind' => 'section', 'id' => $this->classSection()->id]]],
        ]);

        $this->actingAs(User::factory()->create())
            ->post(route('admin.newsletters.send', $newsletter))
            ->assertSessionHas('success');

        $this->assertSame(Newsletter::SENT, $newsletter->fresh()->status);
    }

    public function test_the_test_email_cleans_up_after_itself(): void
    {
        $this->fakeHappyMailchimp();

        $newsletter = $this->scheduled();
        $newsletter->update(['status' => Newsletter::DRAFT]);

        $this->actingAs(User::factory()->create())
            ->post(route('admin.newsletters.test', $newsletter), ['email' => 'pablo@example.com'])
            ->assertSessionHas('success');

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/campaigns/abc123/actions/test')
            && $r->data()['test_emails'] === ['pablo@example.com']);

        // La campaña de prueba se borra: si no, el tablero de Mailchimp se llena de
        // borradores que nadie mandó.
        Http::assertSent(fn (Request $r) => $r->method() === 'DELETE' && str_ends_with($r->url(), '/campaigns/abc123'));

        // Y la casilla queda recordada para la próxima vez.
        $this->assertSame('pablo@example.com', Setting::get('newsletter_test_email'));
        $this->assertSame(Newsletter::DRAFT, $newsletter->fresh()->status);
    }
}
