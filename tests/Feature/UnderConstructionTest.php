<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Setting;
use App\Models\User;
use App\Support\Construction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * El cartel de "En construcción" se enciende desde Ajustes y sólo tapa el sitio
 * público. Ver App\Http\Middleware\UnderConstruction.
 */
class UnderConstructionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Page::create(['slug' => 'home', 'title' => 'Inicio']);
        Page::create(['slug' => 'quienes-somos', 'title' => 'Quiénes somos', 'menu_label' => 'Quiénes somos']);
    }

    private function cerrar(): void
    {
        Setting::set('under_construction', '1');
    }

    /**
     * actingAs() deja al usuario puesto para todo lo que sigue en el mismo test, y
     * acá la diferencia entre estar adentro y no estarlo es justamente lo que se
     * prueba: sin esto, la visita anónima seguiría viendo el sitio como el dueño.
     */
    private function visitante(): static
    {
        Auth::logout();

        return $this;
    }

    public function test_sin_el_ajuste_el_sitio_publico_se_ve_normal(): void
    {
        $this->get('/')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->component('Public/Page'));
        $this->get('/quienes-somos')->assertOk();
    }

    public function test_con_el_ajuste_la_home_y_las_paginas_muestran_el_cartel(): void
    {
        $this->cerrar();

        foreach (['/', '/quienes-somos'] as $url) {
            $this->get($url)
                // 503 y no 404: el sitio existe, hoy no está. Así el cartel no se
                // indexa ni desplaza a las páginas de verdad en el buscador.
                ->assertStatus(503)
                ->assertInertia(fn (AssertableInertia $page) => $page->component('Public/UnderConstruction'));
        }
    }

    /** Si el cartel quedara guardado, sacar el interruptor no se vería. */
    public function test_el_cartel_pide_no_guardarse_en_ninguna_cache(): void
    {
        $this->cerrar();

        $this->get('/')->assertHeader('Cache-Control', 'no-store, private');
    }

    /**
     * Lo más importante de todo: el interruptor no puede dejar al dueño afuera. Si
     * alguna vez pasa a colgarse del grupo web en vez de las rutas públicas, esto se
     * cae.
     */
    public function test_el_panel_y_el_login_siguen_abiertos_con_el_sitio_cerrado(): void
    {
        $this->cerrar();

        $this->get('/login')->assertOk();
        $this->get('/forgot-password')->assertOk();
        $this->actingAs(User::factory()->create())->get('/admin/settings')->assertOk();
    }

    /** Con sesión iniciada se ve el sitio de verdad, para poder revisarlo. */
    public function test_con_sesion_iniciada_se_pasa_de_largo(): void
    {
        $this->cerrar();

        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Public/Page'));
    }

    /** Y por eso mismo hay una vista del cartel dentro del panel. */
    public function test_el_panel_puede_ver_el_cartel_aunque_el_sitio_este_abierto(): void
    {
        $this->get('/admin/settings/construccion')->assertRedirect('/login');

        $this->actingAs(User::factory()->create())
            ->get('/admin/settings/construccion')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Public/UnderConstruction')
                ->where('preview', true));
    }

    public function test_los_textos_vacios_caen_en_los_de_fabrica(): void
    {
        $this->cerrar();

        $this->get('/')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('title', Construction::TITLE)
            ->where('message', Construction::MESSAGE));
    }

    public function test_el_panel_guarda_el_interruptor_y_los_textos_y_permite_volver_atras(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->post('/admin/settings', [
            'under_construction' => '1',
            'construction_title' => 'Volvemos en unos días',
            'construction_message' => 'Estamos mudando el sitio.',
        ])->assertRedirect();

        $this->assertTrue(Construction::enabled());

        $this->visitante()->get('/')->assertStatus(503)->assertInertia(fn (AssertableInertia $page) => $page
            ->where('title', 'Volvemos en unos días')
            ->where('message', 'Estamos mudando el sitio.'));

        // La otra tarjeta del selector vuelve a abrir el sitio sin borrar los textos.
        $this->actingAs($admin)->post('/admin/settings', ['under_construction' => '0'])->assertRedirect();

        $this->assertFalse(Construction::enabled());
        $this->visitante()->get('/')->assertOk();
        $this->assertSame('Volvemos en unos días', Setting::get('construction_title'));
    }

    /** El sitio abierto no arrastra el ajuste al front, que dibuja la cinta de aviso. */
    public function test_el_ajuste_viaja_en_los_props_compartidos(): void
    {
        $this->cerrar();

        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('settings.under_construction', '1'));
    }
}
