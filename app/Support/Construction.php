<?php

namespace App\Support;

use App\Models\Setting;

/**
 * El cartel de "En construcción": el interruptor que cierra el sitio público a las
 * visitas y los textos que se muestran mientras está cerrado.
 *
 * Es el único lugar donde viven los textos de fábrica: los lee el middleware que
 * dibuja el cartel y el formulario de Ajustes, que los pinta como marca de agua de
 * los campos vacíos. Así lo que se ve en el panel es exactamente lo que va a salir
 * publicado. Ver App\Http\Middleware\UnderConstruction.
 */
class Construction
{
    public const TITLE = 'Estamos renovando el sitio';

    public const MESSAGE = 'Muy pronto vas a encontrar acá toda la información de nuestras actividades. Mientras tanto seguimos con las puertas abiertas: escribinos y te contamos.';

    /** Antes de migrar o sembrar, la tabla de ajustes todavía no existe. */
    public static function enabled(): bool
    {
        // '0' es falso en PHP, así que sirve tanto para el ajuste ausente como para
        // el apagado, se haya guardado como null o como cadena vacía.
        return (bool) rescue(fn () => Setting::get('under_construction'), null, false);
    }

    /**
     * Los textos del cartel: los del panel, y los de fábrica donde estén vacíos.
     *
     * @return array{title: string, message: string}
     */
    public static function content(): array
    {
        return [
            'title' => self::text(Setting::get('construction_title')) ?? self::TITLE,
            'message' => self::text(Setting::get('construction_message')) ?? self::MESSAGE,
        ];
    }

    /**
     * Lo que el panel dibuja como marca de agua de los campos vacíos.
     *
     * @return array{title: string, message: string}
     */
    public static function defaults(): array
    {
        return ['title' => self::TITLE, 'message' => self::MESSAGE];
    }

    private static function text(?string $value): ?string
    {
        return $value !== null && trim($value) !== '' ? trim($value) : null;
    }
}
