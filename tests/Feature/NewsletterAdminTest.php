<?php

namespace Tests\Feature;

use App\Models\Newsletter;
use App\Models\Page;
use App\Models\Section;
use App\Models\Setting;
use App\Models\User;
use App\Support\EventCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class NewsletterAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    private function configureSending(): void
    {
        config(['services.mailchimp.key' => 'fakekeyfortests-us21']);
        Setting::set('newsletter_list_id', 'list-123');
        Setting::set('newsletter_from_name', 'Meditar en Zona Norte');
        Setting::set('newsletter_reply_to', 'hola@example.org');

        // Programar y enviar sólo se permiten en producción; ver NewsletterSender.
        config(['services.mailchimp.live_sends' => true]);
    }

    public function test_outside_production_it_refuses_to_schedule(): void
    {
        $this->configureSending();
        config(['services.mailchimp.live_sends' => null]);

        $newsletter = $this->draft([['kind' => 'section', 'id' => $this->classSection()->id]]);

        $this->actingAs($this->admin())
            ->patch(route('admin.newsletters.schedule', $newsletter), ['scheduled_at' => '2027-03-15T09:00'])
            ->assertSessionHas('error');

        $this->assertSame(Newsletter::DRAFT, $newsletter->fresh()->status);
    }

    private function classSection(string $heading = 'Libertad emocional'): Section
    {
        $page = Page::firstOrCreate(
            ['slug' => 'clases-semanales'],
            ['title' => 'Clases semanales', 'menu_order' => 1],
        );

        return Section::create([
            'page_id' => $page->id,
            'type' => 'class_info',
            'key' => 'clase-'.fake()->unique()->numberBetween(1, 9999),
            'content' => ['heading' => $heading],
        ]);
    }

    private function draft(array $items = []): Newsletter
    {
        return Newsletter::create([
            'type' => 'weekly',
            'subject' => 'Clases de esta semana',
            'content' => ['items' => $items],
            'status' => Newsletter::DRAFT,
        ]);
    }

    public function test_the_panel_needs_a_session(): void
    {
        $this->get('/admin/newsletters')->assertRedirect('/login');
    }

    public function test_it_lists_newsletters_and_says_what_is_missing(): void
    {
        $this->draft();

        $this->actingAs($this->admin())
            ->get('/admin/newsletters')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Newsletters/Index')
                ->has('newsletters', 1)
                // Sin configurar, el panel lo dice antes de que alguien intente enviar.
                ->has('missing', 4));
    }

    public function test_it_creates_a_draft_and_opens_it(): void
    {
        $response = $this->actingAs($this->admin())->post('/admin/newsletters', [
            'type' => 'monthly',
            'subject' => 'Novedades del mes',
            'items' => [],
        ]);

        $newsletter = Newsletter::firstOrFail();

        $response->assertRedirect(route('admin.newsletters.edit', $newsletter));
        $this->assertSame('monthly', $newsletter->type);
        $this->assertSame(Newsletter::DRAFT, $newsletter->status);
    }

    public function test_it_saves_the_chosen_activities_in_order(): void
    {
        $newsletter = $this->draft();
        $first = $this->classSection('Primera');
        $second = $this->classSection('Segunda');

        $this->actingAs($this->admin())
            ->put(route('admin.newsletters.update', $newsletter), [
                'type' => 'weekly',
                'subject' => 'Clases de esta semana',
                'items' => [
                    ['kind' => 'section', 'id' => $second->id],
                    ['kind' => 'section', 'id' => $first->id],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(
            [['kind' => 'section', 'id' => $second->id], ['kind' => 'section', 'id' => $first->id]],
            $newsletter->fresh()->items(),
        );
    }

    public function test_it_rejects_an_activity_that_does_not_exist(): void
    {
        $newsletter = $this->draft();

        $this->actingAs($this->admin())
            ->put(route('admin.newsletters.update', $newsletter), [
                'type' => 'weekly',
                'subject' => 'Clases de esta semana',
                'items' => [['kind' => 'section', 'id' => 99999]],
            ])
            ->assertSessionHasErrors('items.0.id');
    }

    public function test_scheduling_stores_utc_and_freezes_the_email(): void
    {
        $this->configureSending();
        $newsletter = $this->draft([['kind' => 'section', 'id' => $this->classSection()->id]]);

        // Las 9 de la mañana de Buenos Aires son las 12 UTC. Sin la conversión, el
        // envío saldría tres horas antes de lo pedido.
        $this->actingAs($this->admin())
            ->patch(route('admin.newsletters.schedule', $newsletter), ['scheduled_at' => '2027-03-15T09:00'])
            ->assertSessionHas('success');

        $newsletter->refresh();

        $this->assertSame(Newsletter::SCHEDULED, $newsletter->status);
        $this->assertSame('2027-03-15 12:00:00', $newsletter->scheduled_at->utc()->format('Y-m-d H:i:s'));
        $this->assertSame('2027-03-15 09:00', $newsletter->scheduled_at->setTimezone(EventCalendar::TIMEZONE)->format('Y-m-d H:i'));

        // El mail queda congelado: de acá en más el envío no depende del sitio.
        $this->assertStringContainsString('Libertad emocional', $newsletter->html);
        $this->assertNotEmpty($newsletter->plain_text);
    }

    public function test_it_refuses_to_schedule_in_the_past_or_with_nothing_chosen(): void
    {
        $this->configureSending();

        $empty = $this->draft();
        $this->actingAs($this->admin())
            ->patch(route('admin.newsletters.schedule', $empty), ['scheduled_at' => '2027-03-15T09:00'])
            ->assertSessionHasErrors('items');

        $withItems = $this->draft([['kind' => 'section', 'id' => $this->classSection()->id]]);
        $this->actingAs($this->admin())
            ->patch(route('admin.newsletters.schedule', $withItems), [
                'scheduled_at' => CarbonImmutable::now(EventCalendar::TIMEZONE)->subHour()->format('Y-m-d\TH:i'),
            ])
            ->assertSessionHasErrors('scheduled_at');
    }

    public function test_it_will_not_schedule_while_the_sending_is_unconfigured(): void
    {
        // Programar algo que después no va a poder salir es peor que no programarlo:
        // el cron lo intentaría, fallaría, y nadie estaría mirando.
        $newsletter = $this->draft([['kind' => 'section', 'id' => $this->classSection()->id]]);

        $this->actingAs($this->admin())
            ->patch(route('admin.newsletters.schedule', $newsletter), ['scheduled_at' => '2027-03-15T09:00'])
            ->assertSessionHas('error');

        $this->assertSame(Newsletter::DRAFT, $newsletter->fresh()->status);
    }

    public function test_editing_a_scheduled_newsletter_refreezes_it(): void
    {
        $this->configureSending();
        $section = $this->classSection('Antes');
        $newsletter = $this->draft([['kind' => 'section', 'id' => $section->id]]);

        $this->actingAs($this->admin())
            ->patch(route('admin.newsletters.schedule', $newsletter), ['scheduled_at' => '2027-03-15T09:00']);

        $otra = $this->classSection('Despues');

        $this->actingAs($this->admin())->put(route('admin.newsletters.update', $newsletter), [
            'type' => 'weekly',
            'subject' => 'Clases de esta semana',
            'items' => [['kind' => 'section', 'id' => $otra->id]],
        ]);

        $newsletter->refresh();

        // Sigue programado, pero con lo que se acaba de guardar: que salga la versión
        // anterior sería lo peor de los dos mundos.
        $this->assertSame(Newsletter::SCHEDULED, $newsletter->status);
        $this->assertStringContainsString('Despues', $newsletter->html);
        $this->assertStringNotContainsString('Antes', $newsletter->html);
    }

    public function test_unscheduling_returns_it_to_a_draft(): void
    {
        $this->configureSending();
        $newsletter = $this->draft([['kind' => 'section', 'id' => $this->classSection()->id]]);

        $this->actingAs($this->admin())
            ->patch(route('admin.newsletters.schedule', $newsletter), ['scheduled_at' => '2027-03-15T09:00']);

        $this->actingAs($this->admin())
            ->patch(route('admin.newsletters.unschedule', $newsletter))
            ->assertSessionHas('success');

        $newsletter->refresh();

        $this->assertSame(Newsletter::DRAFT, $newsletter->status);
        $this->assertNull($newsletter->scheduled_at);
    }

    public function test_the_preview_returns_the_email_itself(): void
    {
        $newsletter = $this->draft([['kind' => 'section', 'id' => $this->classSection()->id]]);

        $response = $this->actingAs($this->admin())->get(route('admin.newsletters.preview', $newsletter));

        $response->assertOk();
        $this->assertStringContainsString('text/html', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Libertad emocional', $response->getContent());
    }

    public function test_duplicating_copies_the_content_as_a_new_draft(): void
    {
        $newsletter = $this->draft([['kind' => 'section', 'id' => $this->classSection()->id]]);
        $newsletter->update(['status' => Newsletter::SENT, 'sent_at' => now()]);

        $this->actingAs($this->admin())
            ->post(route('admin.newsletters.duplicate', $newsletter))
            ->assertSessionHas('success');

        $copy = Newsletter::where('status', Newsletter::DRAFT)->firstOrFail();

        $this->assertSame($newsletter->subject, $copy->subject);
        $this->assertSame($newsletter->items(), $copy->items());
        $this->assertNull($copy->sent_at);
    }

    public function test_a_sent_newsletter_can_no_longer_be_touched(): void
    {
        $newsletter = $this->draft();
        $newsletter->update(['status' => Newsletter::SENT, 'sent_at' => now()]);

        $this->actingAs($this->admin())
            ->put(route('admin.newsletters.update', $newsletter), [
                'type' => 'weekly',
                'subject' => 'Otro asunto',
                'items' => [],
            ])
            ->assertForbidden();

        $this->actingAs($this->admin())
            ->delete(route('admin.newsletters.destroy', $newsletter))
            ->assertForbidden();

        $this->assertSame('Clases de esta semana', $newsletter->fresh()->subject);
    }
}
