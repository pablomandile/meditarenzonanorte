<?php

namespace App\Support;

use App\Models\Newsletter;
use App\Models\Setting;
use Illuminate\Support\Facades\View;

/**
 * Arma el mail: junta los items ya normalizados con los datos del sitio y devuelve
 * el HTML y la versión en texto plano.
 *
 * Esto se ejecuta DURANTE un pedido del panel (al previsualizar y al programar), no
 * desde el cron, y no por casualidad: url() necesita el host real, y en el servidor
 * —con la config cacheada— cae en APP_URL. Congelando el HTML acá, las imágenes del
 * mail apuntan a donde tienen que apuntar aunque APP_URL esté mal puesto.
 */
class NewsletterRenderer
{
    public static function html(Newsletter $newsletter): string
    {
        return View::make('emails.newsletter', self::data($newsletter))->render();
    }

    public static function text(Newsletter $newsletter): string
    {
        return View::make('emails.newsletter-text', self::data($newsletter))->render();
    }

    /**
     * @return array<string, mixed>
     */
    private static function data(Newsletter $newsletter): array
    {
        $settings = Setting::values();
        $logo = $settings['footer_logo_path'] ?? null ?: ($settings['logo_path'] ?? null);

        return [
            'newsletter' => $newsletter,
            'items' => NewsletterItems::resolve($newsletter->items()),
            'site' => [
                'name' => $settings['site_name'] ?? config('app.name'),
                'url' => url('/'),
                // El del pie primero, igual que SiteFooter.vue: suele ser el isotipo
                // cuadrado, y el del menú es el logo ancho con el nombre al lado.
                'logo' => filled($logo) ? url('/storage/'.ltrim((string) $logo, '/')) : null,
                'email' => $settings['email'] ?? null,
                'phone' => $settings['phone_display'] ?? null,
                'phone_link' => $settings['phone_link'] ?? null,
                'instagram' => self::instagramUrl($settings['instagram_url'] ?? null),
                'address' => $settings['address'] ?? null,
            ],
        ];
    }

    /**
     * Los párrafos de un texto largo, uno por renglón escrito.
     *
     * @return array<int, string>
     */
    public static function paragraphs(?string $text): array
    {
        return array_values(array_filter(
            array_map('trim', preg_split('/\R+/', (string) $text) ?: []),
            fn ($line) => $line !== '',
        ));
    }

    /**
     * El ajuste de Instagram se carga de las tres maneras (URL entera, dominio sin
     * esquema, o el usuario suelto). Es el mismo criterio que instagramUrl() en
     * resources/js/lib/site.ts, y hace falta por lo mismo: un valor sin esquema es
     * una URL relativa, y en un mail eso no lleva a ninguna parte.
     */
    private static function instagramUrl(?string $value): ?string
    {
        $raw = trim((string) $value);

        if ($raw === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $raw)) {
            return $raw;
        }

        if (preg_match('#^(www\.)?instagram\.com/#i', $raw)) {
            return 'https://'.$raw;
        }

        return 'https://www.instagram.com/'.ltrim(trim($raw, '/'), '@');
    }
}
