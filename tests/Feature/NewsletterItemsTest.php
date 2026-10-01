<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Page;
use App\Models\Section;
use App\Support\NewsletterItems;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsletterItemsTest extends TestCase
{
    use RefreshDatabase;

    private function page(string $slug = 'clases-semanales'): Page
    {
        return Page::firstOrCreate(
            ['slug' => $slug],
            ['title' => 'Clases semanales', 'menu_order' => 1],
        );
    }

    /**
     * @param  array<string, mixed>  $content
     */
    private function classSection(array $content = [], array $attributes = []): Section
    {
        return Section::create([
            'page_id' => ($attributes['page'] ?? null)?->id ?? $this->page()->id,
            'type' => 'class_info',
            'key' => $attributes['key'] ?? 'clase-'.fake()->unique()->numberBetween(1, 9999),
            'position' => $attributes['position'] ?? 0,
            'visible' => $attributes['visible'] ?? true,
            'is_template' => $attributes['is_template'] ?? false,
            'content' => $content,
        ]);
    }

    public function test_a_class_card_becomes_a_newsletter_item(): void
    {
        $section = $this->classSection([
            'heading' => 'Libertad emocional',
            'teachers' => 'Kelsang Panchen, Ana',
            'body' => 'Una serie de clases sobre la mente.',
            'occurrences' => [['type' => 'weekly', 'weekday' => 3, 'start' => '19:00', 'end' => '20:15']],
            'location' => 'Vicente López',
            'price' => '$5000',
            'image' => 'sections/afiche.jpg',
            'anchor' => 'libertad',
        ]);

        $item = NewsletterItems::resolve([['kind' => 'section', 'id' => $section->id]])[0];

        $this->assertSame('Libertad emocional', $item['title']);
        // Los maestros se enumeran como se dicen, igual que en la tarjeta del sitio.
        $this->assertSame('con Kelsang Panchen y Ana', $item['teachers']);
        // El horario no se reescribe: sale el mismo que arma el sitio con las fechas.
        $this->assertSame('Miércoles de 19 a 20.15 hs', $item['when']);
        $this->assertSame('Vicente López', $item['location']);
        $this->assertSame('$5000', $item['price']);
        // En un mail no hay URL relativa que valga.
        $this->assertSame(url('/storage/sections/afiche.jpg'), $item['image']);
        // Sin botón propio, el enlace lleva a la ficha en el sitio, con su ancla.
        $this->assertSame(url('/clases-semanales').'#libertad', $item['cta_url']);
        $this->assertSame('Ver más', $item['cta_label']);
    }

    public function test_a_handwritten_schedule_wins_over_the_calendar_dates(): void
    {
        // El mismo orden de preferencia que PageController::content(): si el mail
        // dijera un horario distinto al de la página, sería peor que no mandarlo.
        $section = $this->classSection([
            'heading' => 'Meditación guiada',
            'schedule' => 'Todos los días al amanecer',
            'occurrences' => [['type' => 'weekly', 'weekday' => 1, 'start' => '19:00']],
        ]);

        $item = NewsletterItems::resolve([['kind' => 'section', 'id' => $section->id]])[0];

        $this->assertSame('Todos los días al amanecer', $item['when']);
    }

    public function test_an_event_becomes_a_newsletter_item(): void
    {
        $event = Event::create([
            'title' => 'Retiro de fin de semana',
            'description' => 'Dos días de práctica.',
            'starts_at' => '2026-08-29',
            'start_time' => '10:00',
            'end_time' => '17:30',
            'location' => 'Tigre',
            'price' => 'Gratis',
            'image_path' => 'events/retiro.jpg',
            'cta_label' => 'Inscribirse',
            'cta_url' => 'https://example.com/inscripcion',
            'visible' => true,
        ]);

        $item = NewsletterItems::resolve([['kind' => 'event', 'id' => $event->id]])[0];

        $this->assertSame('Retiro de fin de semana', $item['title']);
        // La fecha escrita en castellano ya la calcula el modelo.
        $this->assertSame('Sábado 29 de agosto de 10 a 17.30 hs', $item['when']);
        $this->assertSame(url('/storage/events/retiro.jpg'), $item['image']);
        $this->assertSame('Inscribirse', $item['cta_label']);
        $this->assertSame('https://example.com/inscripcion', $item['cta_url']);
    }

    public function test_it_keeps_the_chosen_order_and_skips_what_no_longer_exists(): void
    {
        $first = $this->classSection(['heading' => 'Primera']);
        $second = $this->classSection(['heading' => 'Segunda']);

        $items = NewsletterItems::resolve([
            ['kind' => 'section', 'id' => $second->id],
            // Una ficha borrada entre que se armó el newsletter y que se envía: un
            // mail con un agujero es mejor que un error.
            ['kind' => 'section', 'id' => 99999],
            ['kind' => 'event', 'id' => 99999],
            ['kind' => 'section', 'id' => $first->id],
        ]);

        $this->assertSame(['Segunda', 'Primera'], array_column($items, 'title'));
    }

    public function test_the_pool_leaves_out_hidden_cards_and_templates(): void
    {
        $this->classSection(['heading' => 'Visible']);
        $this->classSection(['heading' => 'Oculta'], ['visible' => false]);
        // Un molde para clonar no es una clase que exista: anunciarla sería inventar.
        $this->classSection(['heading' => 'Plantilla'], ['is_template' => true]);

        Event::create(['title' => 'Evento visible', 'visible' => true]);
        Event::create(['title' => 'Evento oculto', 'visible' => false]);

        $pool = NewsletterItems::pool();

        $this->assertSame(['Visible'], array_column($pool['classes'], 'title'));
        $this->assertSame(['Evento visible'], array_column($pool['events'], 'title'));
    }
}
