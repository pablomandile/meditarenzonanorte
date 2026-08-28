<?php

namespace App\Http\Middleware;

use App\Support\Construction;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Con el interruptor de Ajustes puesto, el sitio público muestra el cartel de
 * "En construcción" en lugar de la página.
 *
 * Va colgado de las dos rutas públicas —la home y el catch-all de slugs— y no del
 * grupo web entero: así el panel, el login y el recupero de contraseña quedan afuera
 * por construcción y no por una lista de excepciones que se puede olvidar de
 * actualizar. Poner el interruptor no puede dejar al dueño afuera.
 *
 * Con sesión iniciada se pasa de largo: el dueño sigue viendo el sitio de verdad
 * para revisarlo mientras las visitas ven el cartel. Como eso se puede confundir con
 * "no funcionó", PublicLayout le muestra una cinta recordándole que está cerrado.
 */
class UnderConstruction
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() || ! Construction::enabled()) {
            return $next($request);
        }

        $response = Inertia::render('Public/UnderConstruction', Construction::content())
            ->toResponse($request);

        /*
         * 503 y no 200: le dice a Google "el sitio existe pero hoy no está, volvé
         * más tarde", así el cartel no se indexa ni desplaza a las páginas de verdad
         * en los resultados. Inertia lo dibuja igual: el cliente decide por el header
         * X-Inertia, no por el código de estado.
         */
        $response->setStatusCode(Response::HTTP_SERVICE_UNAVAILABLE);

        /*
         * Y no se guarda en ninguna caché, para que sacar el interruptor se vea al
         * toque: si un intermediario se quedara con el cartel, lo seguiría sirviendo
         * con el sitio ya abierto. Es la misma precaución que toma
         * HandleInertiaRequests con el JSON de Inertia.
         */
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
