<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Page;
use App\Models\Section;
use App\Models\User;
use App\Support\EventLanding;
use App\Support\SectionRegistry;
use Database\Seeders\ContentSeeder;
use Database\Seeders\EventLandingTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * La landing de los eventos con "Link interno": una copia de la plantilla genérica
 * por evento, publicada en /eventos-especiales/{slug}.
 */
class EventLandingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->seed(ContentSeeder::class);
        $this->seed(EventLandingTemplateSeeder::class);
    }

    private function admin(): User
    {
        return User::factory()->create();
    }

    private function template(): Page
    {
        return Page::where('kind', Page::KIND_EVENT_TEMPLATE)->firstOrFail();
    }

    /** @param  array<string, mixed>  $overrides */
    private function createInternalEvent(array $overrides = []): Event
    {
        $this->actingAs($this->admin())
            ->post('/admin/events', [
                'title' => 'Iniciacion de Tara Verde',
                'starts_at' => '2026-12-04',
                'ends_at' => '2026-12-06',
                'location' => 'Av. Siempreviva 742, Olivos',
                'price' => '$ 30.000',
                'link_type' => 'internal',
                'visible' => true,
                ...$overrides,
            ])
            ->assertRedirect();

        return Event::where('title', $overrides['title'] ?? 'Iniciacion de Tara Verde')->firstOrFail();
    }

    public function test_the_template_has_the_eight_sections_in_order(): void
    {
        $template = $this->template();

        $this->assertSame(SectionRegistry::landingTypes(), $template->sections()->pluck('type')->all());
        $this->assertSame('¿Qué es una iniciación?', $template->sections()->where('type', 'landing_intro')->first()->content['heading']);
        Storage::disk('public')->assertExists('seed/landing-evento/tara-verde.jpg');

        // Repetir el seeder no duplica ni pisa la plantilla.
        $this->seed(EventLandingTemplateSeeder::class);
        $this->assertSame(1, Page::where('kind', Page::KIND_EVENT_TEMPLATE)->count());
    }

    public function test_the_template_is_edited_under_events_and_not_listed_as_a_page(): void
    {
        $admin = $this->admin();
        $template = $this->template();

        $this->actingAs($admin)->get('/admin/landing-eventos/plantilla')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Pages/Show')
                ->where('page.kind', 'event_template')
                ->has('sections', 8));

        $this->actingAs($admin)->get("/admin/pages/{$template->id}")->assertRedirect('/admin/landing-eventos/plantilla');

        $this->actingAs($admin)->get('/admin/pages')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('pages', fn ($pages) => collect($pages)->doesntContain('slug', Page::EVENT_TEMPLATE_SLUG)));

        // Vista previa sólo con sesión; la dirección del comodín da 404.
        $this->actingAs($admin)->get('/admin/landing-eventos/plantilla/vista-previa')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Public/EventLanding')->where('preview', true));
    }

    public function test_the_template_is_not_public(): void
    {
        $this->get('/'.Page::EVENT_TEMPLATE_SLUG)->assertNotFound();
        $this->get('/admin/landing-eventos/plantilla/vista-previa')->assertRedirect('/login');
    }

    public function test_saving_an_internal_event_creates_its_landing_from_the_template(): void
    {
        $event = $this->createInternalEvent();
        $landing = $event->landing()->firstOrFail();

        $this->assertSame('iniciacion-de-tara-verde', $landing->slug);
        $this->assertSame(Page::KIND_EVENT_LANDING, $landing->kind);
        $this->assertSame(SectionRegistry::landingTypes(), $landing->sections()->pluck('type')->all());

        // La tarjeta lleva a la landing con el texto de botón por defecto.
        $this->assertSame('internal', $event->link_type);
        $this->assertSame('/eventos-especiales/iniciacion-de-tara-verde', $event->cta_url);
        $this->assertNull($event->image_url);
        $this->assertSame('Más información', $event->cta_label);

        // Se completa con lo cargado en el evento.
        $hero = $landing->sections()->where('type', 'landing_hero')->first()->content;
        $this->assertSame('Iniciacion de Tara Verde', $hero['heading']);
        $this->assertNull($hero['heading_accent']);
        $this->assertSame('Av. Siempreviva 742, Olivos', $hero['place']);
        $this->assertStringContainsString('Del 4 al 6 de diciembre', $hero['date']);

        $booking = $landing->sections()->where('type', 'landing_booking')->first()->content;
        $this->assertSame([['note' => null, 'amount' => '$ 30.000']], $booking['prices']);
        $this->assertSame('Av. Siempreviva 742, Olivos', $booking['address']);

        // Después de crear la landing, el panel lleva a editarla.
        $this->actingAs($this->admin())
            ->post('/admin/events', ['title' => 'Otro evento', 'link_type' => 'internal', 'visible' => true])
            ->assertRedirect(route('admin.pages.show', Event::where('title', 'Otro evento')->first()->landing));
    }

    public function test_the_landing_is_published_and_the_event_card_points_to_it(): void
    {
        $this->createInternalEvent(['show_on_home' => true]);

        $this->get('/eventos-especiales/iniciacion-de-tara-verde')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Public/EventLanding')
                ->where('preview', false)
                ->has('sections', 8)
                ->where('sections.0.type', 'landing_hero'));

        $this->get('/')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('homeEvents', fn ($events) => collect($events)->contains('cta_url', '/eventos-especiales/iniciacion-de-tara-verde')));

        // No es una página del sitio: ni en el comodín ni en el menú.
        $this->get('/iniciacion-de-tara-verde')->assertNotFound();
        $this->get('/')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('nav', fn ($nav) => collect($nav)->doesntContain('slug', 'iniciacion-de-tara-verde')));
    }

    public function test_a_hidden_or_external_event_does_not_publish_its_landing(): void
    {
        $event = $this->createInternalEvent();
        auth()->logout();

        $event->update(['visible' => false]);
        $this->get('/eventos-especiales/iniciacion-de-tara-verde')->assertNotFound();

        // Quien tiene sesión la sigue viendo, avisado.
        $this->actingAs($this->admin())->get('/eventos-especiales/iniciacion-de-tara-verde')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('preview', true));
        auth()->logout();

        $event->update(['visible' => true]);
        $this->actingAs($this->admin())
            ->put("/admin/events/{$event->id}", [
                'title' => $event->title,
                'link_type' => 'external',
                'cta_url' => '/eventos-especiales/iniciacion-de-tara-verde',
                'visible' => true,
            ])
            ->assertRedirect(route('admin.events.index'));
        auth()->logout();

        $event->refresh();
        $this->assertSame('external', $event->link_type);
        // La dirección vieja de la landing no queda como si fuera una URL elegida.
        $this->assertNull($event->cta_url);
        // La landing no se borra: queda para volver a interno.
        $this->assertNotNull($event->landing()->first());
        $this->get('/eventos-especiales/iniciacion-de-tara-verde')->assertNotFound();
    }

    public function test_going_back_to_internal_reuses_the_same_landing(): void
    {
        $event = $this->createInternalEvent();
        $landingId = $event->landing()->first()->id;
        $admin = $this->admin();

        $this->actingAs($admin)->put("/admin/events/{$event->id}", ['title' => $event->title, 'link_type' => 'external', 'visible' => true]);
        $this->actingAs($admin)
            ->put("/admin/events/{$event->id}", ['title' => $event->title, 'link_type' => 'internal', 'visible' => true])
            ->assertRedirect(route('admin.events.index'));

        $this->assertSame($landingId, $event->landing()->first()->id);
        $this->assertSame(1, Page::where('kind', Page::KIND_EVENT_LANDING)->count());
    }

    public function test_editing_the_template_later_does_not_change_existing_landings(): void
    {
        $event = $this->createInternalEvent();
        $intro = $this->template()->sections()->where('type', 'landing_intro')->first();

        $this->actingAs($this->admin())
            ->put("/admin/sections/{$intro->id}", ['content' => [...$intro->content, 'heading' => 'Titulo nuevo de la plantilla']])
            ->assertRedirect();

        $this->assertSame('Titulo nuevo de la plantilla', $intro->fresh()->content['heading']);
        $this->assertSame(
            '¿Qué es una iniciación?',
            $event->landing()->first()->sections()->where('type', 'landing_intro')->first()->content['heading'],
        );
    }

    public function test_landing_images_are_copies_owned_by_the_landing(): void
    {
        $admin = $this->admin();
        $about = $this->template()->sections()->where('type', 'landing_about')->first();

        // La plantilla con una imagen subida (no sembrada).
        $this->actingAs($admin)
            ->post("/admin/sections/{$about->id}", [
                '_method' => 'put',
                'content' => $about->content,
                'files' => ['image' => UploadedFile::fake()->image('tara.jpg')],
            ])
            ->assertRedirect();
        $templatePath = $about->fresh()->content['image'];
        $this->assertStringStartsWith('sections/', $templatePath);

        $event = $this->createInternalEvent();
        $landingPath = $event->landing()->first()->sections()->where('type', 'landing_about')->first()->content['image'];

        $this->assertNotSame($templatePath, $landingPath);
        Storage::disk('public')->assertExists($landingPath);

        // Borrar el evento borra la landing y su copia, no la de la plantilla.
        $this->actingAs($admin)->delete("/admin/events/{$event->id}")->assertRedirect();

        $this->assertSame(0, Page::where('kind', Page::KIND_EVENT_LANDING)->count());
        Storage::disk('public')->assertMissing($landingPath);
        Storage::disk('public')->assertExists($templatePath);
    }

    public function test_landing_sections_can_be_moved_hidden_and_cloned(): void
    {
        $admin = $this->admin();
        $landing = $this->createInternalEvent()->landing()->first();
        $quote = $landing->sections()->where('type', 'landing_quote')->first();

        $this->actingAs($admin)->patch("/admin/sections/{$quote->id}/move", ['direction' => 'up'])->assertRedirect();
        $this->assertSame('landing_quote', $landing->sections()->get()[2]->type);

        $this->actingAs($admin)->patch("/admin/sections/{$quote->id}/toggle")->assertRedirect();
        $this->assertFalse($quote->fresh()->visible);

        $this->actingAs($admin)->post("/admin/sections/{$quote->id}/duplicate")->assertRedirect();
        $this->assertSame(2, $landing->sections()->where('type', 'landing_quote')->count());

        // La oculta no se publica.
        $this->get("/eventos-especiales/{$landing->slug}")
            ->assertInertia(fn (AssertableInertia $page) => $page->has('sections', 7));

        // Las migas de pan cuelgan de Eventos.
        $this->actingAs($admin)->get("/admin/sections/{$quote->id}/edit")
            ->assertInertia(fn (AssertableInertia $page) => $page->where('page.breadcrumbs.0.title', 'Eventos'));
    }

    public function test_program_days_and_prices_are_saved_normalized(): void
    {
        $landing = $this->createInternalEvent()->landing()->first();
        $program = $landing->sections()->where('type', 'landing_program')->first();
        $booking = $landing->sections()->where('type', 'landing_booking')->first();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put("/admin/sections/{$program->id}", ['content' => [
                'heading' => 'Programa',
                'days' => [
                    ['weekday' => 'Sábado', 'day' => '05', 'featured' => '1', 'items' => [
                        ['time' => '10:00', 'name' => 'Meditación', 'highlight' => '0'],
                        ['time' => '', 'name' => '', 'highlight' => '0'],
                    ]],
                    ['weekday' => '', 'day' => '', 'featured' => '0'],
                ],
            ]])
            ->assertSessionHasNoErrors();

        $this->assertSame(
            [['weekday' => 'Sábado', 'day' => '05', 'featured' => true, 'items' => [['time' => '10:00', 'name' => 'Meditación', 'highlight' => false]]]],
            $program->fresh()->content['days'],
        );

        $this->actingAs($admin)
            ->put("/admin/sections/{$booking->id}", ['content' => [
                'heading' => 'Reserva tu lugar',
                'prices' => [['note' => 'Hasta el 26', 'amount' => '$ 20.000'], ['note' => '', 'amount' => '']],
                'cta_label' => 'Inscribirme',
                'cta_url' => 'https://forms.gle/abc',
            ]])
            ->assertSessionHasNoErrors();

        $this->assertSame([['note' => 'Hasta el 26', 'amount' => '$ 20.000']], $booking->fresh()->content['prices']);
    }

    public function test_slugs_never_collide_with_existing_pages(): void
    {
        $this->createInternalEvent(['title' => 'Abonos']);

        $this->assertSame('abonos-2', Event::where('title', 'Abonos')->first()->landing->slug);
        $this->get('/abonos')->assertOk();
    }

    public function test_the_landing_is_created_even_if_the_template_was_never_seeded(): void
    {
        Section::where('page_id', $this->template()->id)->delete();
        $this->template()->delete();

        $event = Event::create(['title' => 'Sin plantilla', 'visible' => true]);
        $landing = EventLanding::createFor($event);

        $this->assertSame(8, $landing->sections()->count());
    }
}
