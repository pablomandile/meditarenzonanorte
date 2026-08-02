<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Las "Fechas para el calendario" de una ficha de clase, resueltas a días concretos.
 *
 * Cada regla es semanal (`weekday` 1..7 ISO, lunes = 1, acotada opcionalmente por
 * from/until) o de fecha fija (`date`, más `until` si dura varios días).
 *
 * No toca la base ni la zona horaria: un 'Y-m-d' no tiene zona, y por eso las
 * cuentas de días no deben pasar por una. Todo se valida al guardar, pero acá se
 * vuelve a comprobar: el JSON guardado puede venir de un seed o de una versión
 * anterior del formulario.
 */
class Occurrences
{
    /**
     * Las fechas escritas como las escribiría una persona, para publicar como
     * "Horario" de la ficha y para el listado del panel:
     *
     *   Miércoles de 19 a 20.15 hs
     *   Martes y jueves de 18 a 18.30 hs
     *   Miércoles de 19 a 20.30 hs (hasta el 31 de agosto)
     *   Sábado 8 de agosto de 16 a 19 hs
     *   Del 28 al 30 de agosto de 10 a 17.30 hs
     *
     * Los días con el mismo horario se agrupan en una sola frase, que es como se
     * dice: "martes y jueves de 18 a 18.30", no "martes de 18 a 18.30 y jueves de
     * 18 a 18.30". Devuelve null si no hay ninguna fecha usable.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    public static function schedule(array $rows): ?string
    {
        $groups = [];
        $parts = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $hours = SpanishDate::hourRange(self::time($row['start'] ?? null), self::time($row['end'] ?? null));

            if (($row['type'] ?? 'weekly') === 'date') {
                $phrase = self::datePhrase($row, $hours);

                if ($phrase !== null) {
                    $parts[] = $phrase;
                }

                continue;
            }

            $weekday = filter_var($row['weekday'] ?? null, FILTER_VALIDATE_INT);

            if ($weekday === false || $weekday < 1 || $weekday > 7) {
                continue;
            }

            // Se agrupa por horario y vigencia; el orden de aparición se conserva.
            $key = implode('|', [$hours ?? '', $row['from'] ?? '', $row['until'] ?? '']);
            $groups[$key] ??= ['hours' => $hours, 'row' => $row, 'weekdays' => []];
            $groups[$key]['weekdays'][] = $weekday;
        }

        foreach ($groups as $group) {
            $days = array_unique($group['weekdays']);
            sort($days);

            $names = array_map(fn ($day) => SpanishDate::weekday($day), $days);
            $phrase = self::join($names);

            if ($group['hours']) {
                $phrase .= ' '.$group['hours'];
            }

            $parts[] = $phrase.self::validity($group['row']);
        }

        // Cada frase es una afirmación aparte, así que va con mayúscula propia.
        return $parts === [] ? null : implode(' · ', array_map('ucfirst', $parts));
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private static function datePhrase(array $row, ?string $hours): ?string
    {
        $date = self::date($row['date'] ?? null);

        if (! $date) {
            return null;
        }

        $until = self::date($row['until'] ?? null);

        $when = $until && $until->greaterThan($date)
            ? ($date->month === $until->month
                ? 'del '.$date->day.' al '.$until->day.' de '.SpanishDate::month($until->month)
                : 'del '.$date->day.' de '.SpanishDate::month($date->month).' al '.$until->day.' de '.SpanishDate::month($until->month))
            : SpanishDate::weekday($date->dayOfWeekIso).' '.$date->day.' de '.SpanishDate::month($date->month);

        return $hours ? $when.' '.$hours : $when;
    }

    /** " (hasta el 31 de agosto)" y sus variantes, para las reglas semanales. */
    private static function validity(array $row): string
    {
        $from = self::date($row['from'] ?? null);
        $until = self::date($row['until'] ?? null);

        if ($from && $until) {
            // Dentro del mismo mes el mes se nombra una sola vez.
            return $from->month === $until->month
                ? ' (del '.$from->day.' al '.$until->day.' de '.SpanishDate::month($until->month).')'
                : ' (del '.$from->day.' de '.SpanishDate::month($from->month).' al '.$until->day.' de '.SpanishDate::month($until->month).')';
        }

        if ($until) {
            return ' (hasta el '.$until->day.' de '.SpanishDate::month($until->month).')';
        }

        if ($from) {
            return ' (desde el '.$from->day.' de '.SpanishDate::month($from->month).')';
        }

        return '';
    }

    /**
     * @param  array<int, string>  $items
     */
    private static function join(array $items): string
    {
        if (count($items) <= 1) {
            return $items[0] ?? '';
        }

        $last = array_pop($items);

        return implode(', ', $items).' y '.$last;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array{date: string, start: ?string, end: ?string, label: ?string}>
     */
    public static function expand(array $rows, string $from, string $until): array
    {
        $windowStart = self::date($from);
        $windowEnd = self::date($until);

        if (! $windowStart || ! $windowEnd || $windowStart->greaterThan($windowEnd)) {
            return [];
        }

        $dates = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $start = self::time($row['start'] ?? null);
            $end = self::time($row['end'] ?? null);
            $label = self::label($row['label'] ?? null);

            foreach (self::datesOf($row, $windowStart, $windowEnd) as $date) {
                $dates[] = ['date' => $date, 'start' => $start, 'end' => $end, 'label' => $label];
            }
        }

        return $dates;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<int, string>
     */
    private static function datesOf(array $row, CarbonImmutable $windowStart, CarbonImmutable $windowEnd): array
    {
        $rowStart = self::date($row['from'] ?? null);
        $rowEnd = self::date($row['until'] ?? null);

        if (($row['type'] ?? 'weekly') === 'date') {
            $date = self::date($row['date'] ?? null);

            if (! $date) {
                return [];
            }

            // Una fecha fija con `until` es un retiro: ocupa todos los días del rango.
            $rowStart = $date;
            $rowEnd = $rowEnd && $rowEnd->greaterThanOrEqualTo($date) ? $rowEnd : $date;
            $weekday = null;
        } else {
            $weekday = filter_var($row['weekday'] ?? null, FILTER_VALIDATE_INT);

            if ($weekday === false || $weekday < 1 || $weekday > 7) {
                return [];
            }
        }

        $cursor = $rowStart && $rowStart->greaterThan($windowStart) ? $rowStart : $windowStart;
        $last = $rowEnd && $rowEnd->lessThan($windowEnd) ? $rowEnd : $windowEnd;

        $dates = [];

        // La ventana nunca pasa de 6 semanas, así que recorrer día por día es más
        // claro que hacer cuentas con módulo y se comporta igual.
        while ($cursor->lessThanOrEqualTo($last)) {
            if ($weekday === null || $cursor->dayOfWeekIso === $weekday) {
                $dates[] = $cursor->toDateString();
            }

            $cursor = $cursor->addDay();
        }

        return $dates;
    }

    private static function date(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || ! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $parts)) {
            return null;
        }

        // checkdate descarta un 2026-02-31, que createFromFormat convertiría en marzo.
        if (! checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])) {
            return null;
        }

        return CarbonImmutable::createFromFormat('Y-m-d', $value)->startOfDay();
    }

    /** Normaliza a H:i: MySQL devuelve las horas como '19:00:00'. */
    private static function time(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $time = substr($value, 0, 5);

        return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time) ? $time : null;
    }

    private static function label(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        return trim($value) === '' ? null : trim($value);
    }
}
