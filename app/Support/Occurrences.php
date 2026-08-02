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
