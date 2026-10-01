<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Newsletter;
use App\Models\Page;
use App\Models\Section;
use App\Models\Setting;
use App\Support\NewsletterRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsletterRendererTest extends TestCase
{
    use RefreshDatabase;

    private function newsletter(array $items): Newsletter
    {
        return Newsletter::create([
            'type' => 'weekly',
            'subject' => 'Clases de esta semana',
            'preview_text' => 'Lo que viene en Zona Norte',
            'content' => ['items' => $items],
        ]);
    }

    private function classSection(array $content): Section
    {
        $page = Page::firstOrCreate(
            ['slug' => 'clases-semanales'],
            ['title' => 'Clases semanales', 'menu_order' => 1],
        );

        return Section::create([
            'page_id' => $page->id,
            'type' => 'class_info',
            'key' => 'clase-'.fake()->unique()->numberBetween(1, 9999),
            'content' => $content,
        ]);
    }

    public function test_it_renders_the_chosen_activities_with_the_site_header_and_footer(): void
    {
        Setting::set('site_name', 'Meditar en Zona Norte');
        Setting::set('email', 'hola@example.org');
        Setting::set('address', 'Av. Siempreviva 742, Vicente Lopez');
        Setting::set('instagram_url', '@meditarzn');
        Setting::set('footer_logo_path', 'settings/isotipo.png');

        $section = $this->classSection([
            'heading' => 'Libertad emocional',
            'teachers' => 'Kelsang Panchen',
            'body' => "Primer parrafo.\nSegundo parrafo.",
            'schedule' => 'Miercoles de 19 a 20.15 hs',
            'location' => 'Vicente Lopez',
            'price' => '$5000',
            'image' => 'sections/afiche.jpg',
        ]);

        $html = NewsletterRenderer::html($this->newsletter([
            ['kind' => 'section', 'id' => $section->id],
        ]));

        $this->assertStringContainsString('Libertad emocional', $html);
        $this->assertStringContainsString('con Kelsang Panchen', $html);
        $this->assertStringContainsString('Miercoles de 19 a 20.15 hs', $html);
        $this->assertStringContainsString('Primer parrafo.', $html);
        $this->assertStringContainsString('Segundo parrafo.', $html);

        // Cabecera y pie salen de Ajustes del sitio, no están escritos en el mail.
        $this->assertStringContainsString('Meditar en Zona Norte', $html);
        $this->assertStringContainsString('hola@example.org', $html);
        $this->assertStringContainsString('Av. Siempreviva 742', $html);
        // El handle suelto se convierte en URL: sin esquema, el enlace sería relativo
        // y en un mail no llevaría a ninguna parte.
        $this->assertStringContainsString('https://www.instagram.com/meditarzn', $html);

        // Todas las imágenes absolutas: un mail no tiene contra qué resolver una ruta.
        $this->assertStringContainsString(url('/storage/settings/isotipo.png'), $html);
        $this->assertStringContainsString(url('/storage/sections/afiche.jpg'), $html);
        $this->assertStringNotContainsString('src="/storage/', $html);
    }

    public function test_the_unsubscribe_link_is_a_real_link(): void
    {
        // Sin esto Mailchimp rechaza la campaña, y como texto suelto no alcanza:
        // tiene que ser el href de un <a>. Es el error más común al mandar HTML propio.
        $html = NewsletterRenderer::html($this->newsletter([]));

        $this->assertStringContainsString('href="*|UNSUB|*"', $html);
        $this->assertStringContainsString('*|HTML:LIST_ADDRESS_HTML|*', $html);
    }

    public function test_the_plain_text_version_carries_the_same_information(): void
    {
        $event = Event::create([
            'title' => 'Retiro de fin de semana',
            'description' => 'Dos dias de practica.',
            'starts_at' => '2026-08-29',
            'location' => 'Tigre',
            'cta_label' => 'Inscribirse',
            'cta_url' => 'https://example.com/inscripcion',
            'visible' => true,
        ]);

        $text = NewsletterRenderer::text($this->newsletter([
            ['kind' => 'event', 'id' => $event->id],
        ]));

        $this->assertStringContainsString('Retiro de fin de semana', $text);
        $this->assertStringContainsString('Tigre', $text);
        $this->assertStringContainsString('Dos dias de practica.', $text);
        $this->assertStringContainsString('https://example.com/inscripcion', $text);
        // También en texto plano hay que poder darse de baja.
        $this->assertStringContainsString('*|UNSUB|*', $text);
        // Y no queda nada de HTML dando vueltas.
        $this->assertStringNotContainsString('<td', $text);
    }
}
