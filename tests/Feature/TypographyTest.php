<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * La fuente de los títulos se elige desde Ajustes y se emite en la vista raíz, que
 * es la misma para todas las páginas: por eso alcanza con mirar cualquiera.
 */
class TypographyTest extends TestCase
{
    use RefreshDatabase;

    /** Sin fuente elegida el sitio tiene que quedar exactamente como estaba. */
    public function test_sin_ajuste_no_se_descarga_ninguna_fuente(): void
    {
        $html = $this->get('/login')->assertOk()->getContent();

        $this->assertStringNotContainsString('fonts.googleapis.com', $html);
        $this->assertStringNotContainsString('--font-heading', $html);
    }

    public function test_la_fuente_elegida_se_carga_y_manda_sobre_la_de_siempre(): void
    {
        Setting::set('heading_font', 'montserrat');

        $html = $this->get('/login')->assertOk()->getContent();

        $this->assertStringContainsString('fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600', $html);
        $this->assertStringContainsString('rel="preconnect" href="https://fonts.gstatic.com"', $html);

        // La variable va como estilo en línea del <html>, así que en el HTML sale
        // escapada; el navegador la decodifica antes de parsear el CSS.
        $this->assertStringContainsString(e("--font-heading: 'Montserrat', Helvetica, Arial, sans-serif"), $html);

        // Los títulos grandes siguen en Anton: la fuente elegida no los toca.
        $this->assertStringNotContainsString('--font-display', $html);
    }

    public function test_el_panel_guarda_la_fuente_y_permite_volver_atras(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->post('/admin/settings', ['site_name' => 'Meditar', 'heading_font' => 'barlow'])
            ->assertRedirect();

        $this->assertSame('barlow', Setting::get('heading_font'));

        // El campo vacío es la salida para volver al aspecto de siempre.
        $this->actingAs($admin)
            ->post('/admin/settings', ['site_name' => 'Meditar', 'heading_font' => ''])
            ->assertRedirect();

        $this->assertNull(Setting::get('heading_font'));
        $this->assertStringNotContainsString('fonts.googleapis.com', $this->get('/login')->getContent());
    }

    /** El panel dibuja la vista previa con el catálogo del servidor, no con nombres propios. */
    public function test_el_panel_recibe_el_catalogo_de_fuentes(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/settings')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Settings/Edit')
                ->has('fonts', 3)
                ->where('fonts.0.name', 'Montserrat')
                ->has('fonts.0.url')
                ->has('fonts.0.stack'));
    }

    /** El valor termina dentro de un font-family del HTML: no puede ser texto libre. */
    public function test_una_fuente_que_no_esta_en_el_catalogo_se_rechaza(): void
    {
        Setting::set('heading_font', 'montserrat');

        $this->actingAs(User::factory()->create())
            ->post('/admin/settings', ['heading_font' => "Comic Sans; } body { display: none"])
            ->assertSessionHasErrors('heading_font');

        $this->assertSame('montserrat', Setting::get('heading_font'));
    }
}
