<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El JSON de Inertia y el HTML de arranque comparten URL, y el CDN borra el Vary
 * que los distingue. Si la respuesta XHR se puede guardar, al restaurar una
 * pestaña descartada el navegador muestra el JSON crudo en pantalla.
 */
class InertiaCacheHeadersTest extends TestCase
{
    use RefreshDatabase;

    /** Sin esto Inertia contesta 409 en vez de la página, y el test miente. */
    private function versionDeInertia(): string
    {
        return (string) app(HandleInertiaRequests::class)->version(request());
    }

    public function test_la_respuesta_xhr_de_inertia_no_se_puede_guardar(): void
    {
        $respuesta = $this->get('/login', [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => $this->versionDeInertia(),
        ]);

        $respuesta->assertOk();
        $this->assertStringContainsString('application/json', (string) $respuesta->headers->get('Content-Type'));
        $this->assertStringContainsString('no-store', (string) $respuesta->headers->get('Cache-Control'));
        $this->assertStringContainsString('X-Inertia', (string) $respuesta->headers->get('Vary'));
    }

    /**
     * La otra mitad, y la que se rompe sola si alguien "simplifica" el middleware
     * poniendo no-store en todo: Chrome no guarda en bfcache un documento servido
     * con no-store, y cada "atrás" pasa a ser una ida completa a la red.
     */
    public function test_el_documento_html_sigue_siendo_cacheable(): void
    {
        $respuesta = $this->get('/login');

        $respuesta->assertOk();
        $this->assertStringContainsString('text/html', (string) $respuesta->headers->get('Content-Type'));
        $this->assertStringNotContainsString('no-store', (string) $respuesta->headers->get('Cache-Control'));
    }
}
