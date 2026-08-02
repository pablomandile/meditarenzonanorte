<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Las etiquetas del <head> que se renderizan en el servidor.
 *
 * Existen porque WhatsApp, Facebook y compañía **no ejecutan JavaScript**: leen el
 * HTML tal como sale del servidor. El <Head> de Inertia arma el título y la
 * descripción en el navegador, así que para la vista previa de un enlace compartido
 * no sirve — hay que emitirlas en app.blade.php.
 *
 * El nombre del sitio sale del ajuste `site_name` del panel y no de APP_NAME: así
 * se cambia sin tocar el .env, y no puede volver a quedar desincronizado (APP_NAME
 * y VITE_APP_NAME viven en archivos distintos, y el segundo queda horneado en el
 * bundle al compilar).
 */
class SiteMeta
{
    /**
     * @param  array<string, mixed>  $page  El array de Inertia que recibe la vista raíz.
     * @return array{site: string, title: string, description: ?string, image: ?string, url: string}
     */
    public static function from(array $page): array
    {
        $props = $page['props'] ?? [];
        $site = self::siteName();
        $title = self::text($props['page']['title'] ?? null);

        return [
            'site' => $site,
            // Mismo formato que arma el cliente en app.ts, para que el título no
            // cambie al arrancar el JavaScript.
            'title' => $title === null ? $site : $title.' - '.$site,
            'description' => self::text($props['page']['meta_description'] ?? null),
            'image' => self::image($props),
            'url' => url($page['url'] ?? '/'),
        ];
    }

    /** El nombre visible del sitio: el del panel, y si no el de APP_NAME. */
    public static function siteName(): string
    {
        $name = self::text(self::setting('site_name'));

        return $name ?? (string) config('app.name');
    }

    /**
     * La imagen de la vista previa: la portada de la página, y si no el logo. Va en
     * URL absoluta porque los crawlers no resuelven rutas relativas.
     *
     * @param  array<string, mixed>  $props
     */
    private static function image(array $props): ?string
    {
        foreach ($props['sections'] ?? [] as $section) {
            if (($section['type'] ?? null) === 'hero' && self::text($section['content']['image'] ?? null)) {
                return url('/storage/'.$section['content']['image']);
            }
        }

        $logo = self::text(self::setting('logo_path'));

        return $logo === null ? null : url('/storage/'.$logo);
    }

    /** Antes de migrar o sembrar, la tabla de ajustes todavía no existe. */
    private static function setting(string $key): ?string
    {
        return rescue(fn () => Setting::get($key), null, false);
    }

    private static function text(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
