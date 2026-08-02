<?php

namespace App\Support;

/**
 * Los nombres de meses y días, y el formato de horas del sitio, en un solo lugar.
 *
 * Van escritos a mano y no con Carbon->locale('es'): APP_LOCALE es 'en' y nadie
 * fija el locale, así que translatedFormat() devolvería inglés; y el catálogo 'es'
 * de Carbon abrevia en minúscula y con punto ('ago.'), que igual habría que
 * retocar. Con estas pocas palabras fijas, CI y producción imprimen lo mismo.
 */
class SpanishDate
{
    /** @var array<int, string> */
    public const MONTHS = [
        1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
        'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre',
    ];

    /** @var array<int, string> Lunes = 1, como dayOfWeekIso. */
    public const WEEKDAYS = [1 => 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'];

    public static function month(int $month): string
    {
        return self::MONTHS[$month] ?? '';
    }

    public static function weekday(int $isoDay): string
    {
        return self::WEEKDAYS[$isoDay] ?? '';
    }

    /**
     * La hora como la escribe el sitio: '19:00' → '19', '20:15' → '20.15'
     * (minutos con punto, y sin los ':00' redondos).
     */
    public static function hour(?string $time): ?string
    {
        if (! is_string($time) || ! preg_match('/^(\d{1,2}):(\d{2})/', $time, $parts)) {
            return null;
        }

        $hours = ltrim($parts[1], '0');
        $hours = $hours === '' ? '0' : $hours;

        return $parts[2] === '00' ? $hours : $hours.'.'.$parts[2];
    }

    /** 'de 19 a 20.15 hs', 'a las 19 hs', o null si no hay hora de inicio. */
    public static function hourRange(?string $start, ?string $end): ?string
    {
        $from = self::hour($start);

        if ($from === null) {
            return null;
        }

        $to = self::hour($end);

        return $to === null ? "a las $from hs" : "de $from a $to hs";
    }
}
