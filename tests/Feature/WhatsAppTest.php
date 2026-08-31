<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Support\WhatsApp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * El número y el mensaje del botón de WhatsApp se editan por separado en
 * Ajustes; App\Support\WhatsApp los junta en un solo link con el ?text=.
 */
class WhatsAppTest extends TestCase
{
    use RefreshDatabase;

    public function test_sin_numero_no_hay_boton(): void
    {
        $this->assertNull(WhatsApp::link());

        // El <a> del botón flotante va detrás de v-if="settings.whatsapp_url":
        // con esto en null, PublicLayout.vue no lo dibuja.
        // '/login', no '/': la home depende de que exista la Page 'home' en la
        // base, y este test no siembra contenido; el login siempre renderiza.
        $this->get('/login')->assertInertia(fn (AssertableInertia $page) => $page->where('settings.whatsapp_url', null));
    }

    public function test_el_link_publico_lleva_el_mensaje_codificado(): void
    {
        Setting::set('whatsapp_url', 'https://wa.me/5491166633921');
        Setting::set('whatsapp_message', 'Hola me gustaría recibir info sobre las actividades.');

        $this->assertSame(
            'https://wa.me/5491166633921?text=Hola%20me%20gustar%C3%ADa%20recibir%20info%20sobre%20las%20actividades.',
            WhatsApp::link(),
        );

        // El botón flotante lee la 'settings' compartida, no las claves sueltas.
        $this->get('/login')->assertInertia(fn (AssertableInertia $page) => $page->where(
            'settings.whatsapp_url',
            'https://wa.me/5491166633921?text=Hola%20me%20gustar%C3%ADa%20recibir%20info%20sobre%20las%20actividades.',
        ));
    }

    /** Sin mensaje, el chat abre en blanco: no hace falta forzar uno. */
    public function test_sin_mensaje_el_link_es_solo_el_numero(): void
    {
        Setting::set('whatsapp_url', 'https://wa.me/5491166633921');

        $this->assertSame('https://wa.me/5491166633921', WhatsApp::link());
    }

    /**
     * Antes el mensaje vivía pegado adentro de whatsapp_url, como el ?text= de la
     * URL. Si alguien pega ese enlace viejo en el campo del panel, se guarda sólo
     * la parte del número: el mensaje se escribe aparte, en su propio campo.
     */
    public function test_al_guardar_se_le_saca_cualquier_mensaje_pegado_al_enlace(): void
    {
        $this->actingAs(User::factory()->create())->post('/admin/settings', [
            'whatsapp_url' => 'https://wa.me/5491166633921?text=Un+mensaje+viejo+pegado+a+mano',
            'whatsapp_message' => 'Hola me gustaría recibir info sobre las actividades.',
        ])->assertRedirect();

        $this->assertSame('https://wa.me/5491166633921', Setting::get('whatsapp_url'));
        $this->assertSame('Hola me gustaría recibir info sobre las actividades.', Setting::get('whatsapp_message'));
    }

    /** El campo del panel muestra el número limpio, nunca el ?text= codificado. */
    public function test_el_panel_muestra_el_enlace_sin_el_mensaje(): void
    {
        Setting::set('whatsapp_url', 'https://wa.me/5491166633921');
        Setting::set('whatsapp_message', 'Hola me gustaría recibir info sobre las actividades.');

        $this->actingAs(User::factory()->create())
            ->get('/admin/settings')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('settings.whatsapp_url', 'https://wa.me/5491166633921')
                ->where('settings.whatsapp_message', 'Hola me gustaría recibir info sobre las actividades.'));
    }
}
