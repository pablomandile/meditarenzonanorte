<?php

namespace App\Http\Middleware;

use App\Models\Page;
use App\Models\Setting;
use App\Support\SiteMeta;
use App\Support\WhatsApp;
use Closure;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Inertia\Support\Header;
use Symfony\Component\HttpFoundation\Response;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Una misma URL contesta dos cuerpos distintos según el header X-Inertia: el
     * HTML de arranque para una navegación, el JSON de la página para un XHR. Lo
     * único que se lo dice a una caché es el Vary, y el CDN de Hostinger lo borra
     * cuando comprime con brotli, que es lo que pide cualquier navegador real. Con
     * las dos respuestas compartiendo clave de caché, al restaurar una pestaña
     * descartada Chrome reusa la entrada guardada y muestra el JSON crudo en
     * pantalla; F5 lo tapa, pero vuelve.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = parent::handle($request, $next);

        // Lo pone Inertia y el CDN lo borra, pero se declara igual: es lo correcto
        // y sirve en cualquier intermediario que sí lo respete.
        $response->headers->set('Vary', Header::INERTIA.', Accept-Encoding');

        /*
         * no-store, no no-cache: no-cache deja guardar y solo obliga a revalidar, y
         * una navegación de historial —restaurar una pestaña, el botón atrás— se
         * saltea la revalidación.
         *
         * Y solo sobre la respuesta XHR, nunca sobre el HTML: no-store en el
         * documento principal desactiva el back/forward cache de Chrome y convierte
         * cada "atrás" en una ida completa a la red.
         */
        if ($request->header(Header::INERTIA)) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'name' => SiteMeta::siteName(),
            'auth' => [
                'user' => $request->user(),
            ],
            'nav' => fn () => Page::inMenu()->get(['slug', 'menu_label'])
                ->map(fn ($page) => ['slug' => $page->slug, 'label' => $page->menu_label])
                ->values(),
            'settings' => function () {
                $settings = Setting::values();
                $settings['footer_resources'] = json_decode($settings['footer_resources'] ?? '[]', true) ?: [];
                // El botón flotante recibe el link ya armado con el mensaje adentro;
                // el número y el mensaje se editan por separado en Ajustes.
                $settings['whatsapp_url'] = WhatsApp::link();

                return $settings;
            },
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
            ],
        ]);
    }
}
