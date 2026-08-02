<?php

namespace Database\Seeders;

use App\Models\Section;
use Illuminate\Database\Seeder;

/**
 * Carga por una vez las "Fechas para el calendario" de las fichas de clase que
 * están en producción, leyendo el horario que cada una ya tiene escrito.
 *
 * Los valores están acá y no en el archivo de datos a propósito: son los del sitio
 * publicado, que el dueño editó y clonó, y no coinciden con el contenido sembrado.
 *
 * Sólo completa las fichas que tengan el campo vacío: si el dueño ya cargó fechas
 * a mano, no las pisa. Es repetible y no toca ningún otro campo, ni la visibilidad
 * ni la posición. Una ficha que no exista se saltea sin error.
 *
 *   php artisan db:seed --class=CalendarioFechasSeeder --force
 */
class CalendarioFechasSeeder extends Seeder
{
    /**
     * Fin del ciclo para los horarios que dicen "de agosto". Sin esto la clase se
     * repetiría para siempre; el dueño lo corre o lo borra desde el panel cuando
     * se define el ciclo siguiente.
     */
    private const FIN_DE_AGOSTO = '2026-08-31';

    /**
     * [página, sección, fechas]. El comentario de cada línea es el texto del campo
     * "Horario" del que sale, que es lo que se sigue leyendo en la tarjeta.
     *
     * @return array<int, array{0: string, 1: string, 2: array<int, array<string, mixed>>}>
     */
    private function dates(): array
    {
        return [
            // "Lunes de agosto de 19:00 a 20:30 hs"
            ['clases-semanales', 'clase-principal', [
                self::weekly(1, '19:00', '20:30', until: self::FIN_DE_AGOSTO),
            ]],
            // "Miércoles de agosto de 19:00 a 20:30 hs"
            ['clases-semanales', 'clase-principal-copia', [
                self::weekly(3, '19:00', '20:30', until: self::FIN_DE_AGOSTO),
            ]],
            // "Lunes de 18:00 a 18:30 hs" — sin ciclo, va todas las semanas.
            ['clases-semanales', 'meditaciones-gratuitas', [
                self::weekly(1, '18:00', '18:30'),
            ]],
            // "Miércoles y jueves / 18 a 18.30hs"
            ['gratis', 'oferta-miercoles-jueves', [
                self::weekly(3, '18:00', '18:30'),
                self::weekly(4, '18:00', '18:30'),
            ]],
            // "Viernes 17 de abril de 18.00 a 19.00 hs"
            ['gratis', 'oferta-martes-jueves', [
                self::once('2026-04-17', '18:00', '19:00'),
            ]],
            // "Sábado 8 de Agosto de 16:00 a 19:00 hs."
            ['cursos-y-retiros', 'curso', [
                self::once('2026-08-08', '16:00', '19:00'),
            ]],
            // "Sábado 15 de Agosto de 16:00 a 19:00 hs."
            ['cursos-y-retiros', 'curso-copia', [
                self::once('2026-08-15', '16:00', '19:00'),
            ]],
            // "Sábado 22 de Agosto de 16:00 a 19:00 hs."
            ['cursos-y-retiros', 'curso-copia-2', [
                self::once('2026-08-22', '16:00', '19:00'),
            ]],
            // "Sábado 29 de Agosto de 10:00 a 17:30 hs."
            ['cursos-y-retiros', 'curso-copia-3', [
                self::once('2026-08-29', '10:00', '17:30'),
            ]],
        ];
    }

    public function run(): void
    {
        foreach ($this->dates() as [$slug, $key, $occurrences]) {
            $section = Section::query()
                ->whereHas('page', fn ($query) => $query->where('slug', $slug))
                ->where('key', $key)
                ->first();

            if (! $section || ! empty($section->content['occurrences'] ?? [])) {
                continue;
            }

            $section->update(['content' => [...$section->content ?? [], 'occurrences' => $occurrences]]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private static function weekly(int $weekday, ?string $start, ?string $end, ?string $from = null, ?string $until = null): array
    {
        return [
            'type' => 'weekly',
            'weekday' => $weekday,
            'date' => null,
            'from' => $from,
            'until' => $until,
            'start' => $start,
            'end' => $end,
            'label' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function once(string $date, ?string $start, ?string $end, ?string $until = null): array
    {
        return [
            'type' => 'date',
            'weekday' => null,
            'date' => $date,
            'from' => null,
            'until' => $until,
            'start' => $start,
            'end' => $end,
            'label' => null,
        ];
    }
}
