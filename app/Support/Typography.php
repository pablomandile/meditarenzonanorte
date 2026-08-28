<?php

namespace App\Support;

use App\Models\Setting;

/**
 * La fuente de los títulos de sección del sitio público, elegible desde el panel.
 *
 * Toca los 25 lugares con font-heading —los títulos de sección, los de las tarjetas
 * y los del calendario—, no los párrafos ni los títulos grandes: las bandas, el
 * hero y las fichas siguen en Anton, que viene con el bundle y es lo más
 * reconocible del sitio.
 *
 * Es el único lugar donde viven los nombres de las fuentes: lo leen la vista raíz
 * (para emitir el <link> a Google y pisar la variable CSS), el formulario de
 * ajustes (para dibujar las tarjetas de vista previa) y la validación (para no
 * dejar entrar cualquier texto en un font-family).
 *
 * Mientras el ajuste esté vacío el sitio no descarga ninguna fuente y se ve como
 * siempre: Helvetica del sistema. Esa es la salida para volver atrás.
 *
 * Los pesos que se piden no son decorativos: los títulos de sección usan font-light
 * (300), font-normal, font-medium y font-semibold. Helvetica no tiene 300 y el
 * navegador lo dibuja en 400, así que al cargar una fuente de verdad esos títulos
 * se ven más finos que antes.
 */
class Typography
{
    /**
     * @var array<string, array{name: string, family: string, weights: string}>
     */
    public const FONTS = [
        'montserrat' => ['name' => 'Montserrat', 'family' => "'Montserrat'", 'weights' => '300;400;500;600'],
        'roboto' => ['name' => 'Roboto', 'family' => "'Roboto'", 'weights' => '300;400;500;600'],
        'barlow' => ['name' => 'Barlow', 'family' => "'Barlow'", 'weights' => '300;400;500;600'],
    ];

    /** Lo que se ve hoy, y a lo que caen los títulos si la fuente web no carga. */
    public const FALLBACK = 'Helvetica, Arial, sans-serif';

    /**
     * La fuente elegida, o null si no hay ninguna (el sitio queda como está).
     *
     * @return array{key: string, name: string, family: string, stack: string, url: string}|null
     */
    public static function chosen(): ?array
    {
        // Antes de migrar o sembrar, la tabla de ajustes todavía no existe.
        $key = rescue(fn () => Setting::get('heading_font'), null, false);

        return $key !== null && isset(self::FONTS[$key]) ? self::describe($key) : null;
    }

    /**
     * Las tres opciones para el panel, en orden.
     *
     * @return array<int, array{key: string, name: string, family: string, stack: string, url: string}>
     */
    public static function options(): array
    {
        return array_map(self::describe(...), array_keys(self::FONTS));
    }

    /**
     * @return array{key: string, name: string, family: string, stack: string, url: string}
     */
    private static function describe(string $key): array
    {
        $font = self::FONTS[$key];

        return [
            'key' => $key,
            'name' => $font['name'],
            'family' => $font['family'],
            'stack' => $font['family'].', '.self::FALLBACK,
            // display=swap: el texto se lee con la fuente del sistema mientras baja
            // la otra, en vez de quedar invisible.
            'url' => 'https://fonts.googleapis.com/css2?family='
                .str_replace(' ', '+', $font['name']).':wght@'.$font['weights']
                .'&display=swap',
        ];
    }
}
