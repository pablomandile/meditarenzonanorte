<?php

namespace App\Support;

use App\Models\Setting;

/**
 * El color con que se tiñen las fichas de clase que ya pasaron su última fecha:
 * el afiche se pasa a grises y encima va un velo de este color, multiplicado, y
 * la pastilla «Evento finalizado» toma su versión oscura.
 *
 * Se elige una sola vez, en Ajustes del sitio, y vale para todas las fichas. Cada
 * ficha decide aparte si se tiñe o no (past_filter); acá sólo se decide el color.
 *
 * Es el único lugar donde viven los colores: lo leen las props compartidas (para
 * la ficha pública), el formulario de ajustes (para las muestras) y la validación
 * (el valor termina en un style, así que no puede ser texto libre).
 */
class PastTint
{
    public const DEFAULT = 'ocre';

    /**
     * veil: el velo sobre el afiche en grises; null deja sólo los grises.
     * opacity: cuánto se ve el velo. badge: el fondo de la pastilla.
     *
     * @var array<string, array{name: string, veil: string|null, opacity: float, badge: string}>
     */
    public const TINTS = [
        'ocre' => ['name' => 'Ocre', 'veil' => '#C9962F', 'opacity' => 0.6, 'badge' => '#7A5612'],
        'celeste' => ['name' => 'Celeste', 'veil' => '#259ACF', 'opacity' => 0.55, 'badge' => '#155E80'],
        'terracota' => ['name' => 'Terracota', 'veil' => '#CD6023', 'opacity' => 0.55, 'badge' => '#8A3F15'],
        'noche' => ['name' => 'Azul noche', 'veil' => '#2B3A67', 'opacity' => 0.5, 'badge' => '#1C2747'],
        'gris' => ['name' => 'Gris', 'veil' => null, 'opacity' => 0.0, 'badge' => '#3F3F46'],
    ];

    /** La clave guardada, o el ocre si no hay ninguna o quedó una que ya no existe. */
    public static function key(): string
    {
        $key = Setting::get('past_tint');

        return is_string($key) && isset(self::TINTS[$key]) ? $key : self::DEFAULT;
    }

    /**
     * El tono vigente, listo para la ficha pública.
     *
     * @return array{key: string, name: string, veil: string|null, opacity: float, badge: string}
     */
    public static function current(): array
    {
        $key = self::key();

        return ['key' => $key, ...self::TINTS[$key]];
    }

    /**
     * Todos los tonos, para las tarjetas del panel.
     *
     * @return list<array{key: string, name: string, veil: string|null, opacity: float, badge: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (string $key) => ['key' => $key, ...self::TINTS[$key]],
            array_keys(self::TINTS),
        );
    }
}
