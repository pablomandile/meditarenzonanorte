<?php

namespace App\Support;

use App\Models\Event;
use App\Models\Page;
use App\Models\Section;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Arma la grilla mensual del calendario público a partir de las secciones
 * visibles: las fichas de clase (class_info) con sus "Fechas para el calendario"
 * y los eventos que el panel marcó para el calendario.
 */
class EventCalendar
{
    /**
     * Rosario / Buenos Aires. config('app.timezone') es UTC y así se queda: el mes
     * y el "hoy" del calendario se calculan acá, que es donde importan. A las 21 hs
     * de un 31 de agosto en Argentina ya es 1 de septiembre en UTC, y el calendario
     * abriría en el mes siguiente.
     */
    public const TIMEZONE = 'America/Argentina/Buenos_Aires';

    /** @var array<int, string> */
    private const MONTHS = [
        1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
        'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre',
    ];

    /**
     * Los nombres van a mano en lugar de Carbon->locale('es'): APP_LOCALE es 'en'
     * y nadie fija el locale, así que translatedFormat() devolvería inglés; y el
     * catálogo 'es' de Carbon abrevia con minúscula y punto ('ago.'), que igual
     * habría que retocar. Con 19 palabras fijas, CI y producción imprimen lo mismo.
     *
     * @var array<int, array{short: string, long: string}>
     */
    private const WEEKDAYS = [
        ['short' => 'Lun', 'long' => 'lunes'],
        ['short' => 'Mar', 'long' => 'martes'],
        ['short' => 'Mié', 'long' => 'miércoles'],
        ['short' => 'Jue', 'long' => 'jueves'],
        ['short' => 'Vie', 'long' => 'viernes'],
        ['short' => 'Sáb', 'long' => 'sábado'],
        ['short' => 'Dom', 'long' => 'domingo'],
    ];

    /**
     * El mes en curso, y sólo ése: la grilla no navega a otros meses ni muestra
     * los días de los vecinos. Las celdas que sobran al principio y al final
     * viajan como null, para que la vista las deje vacías.
     *
     * @return array<string, mixed>
     */
    public static function currentMonth(): array
    {
        $today = CarbonImmutable::today(self::TIMEZONE);
        $first = $today->startOfMonth();
        $last = $today->endOfMonth();

        $activities = self::activities($first, $last);

        $weeks = [];
        // Se recorre desde el lunes de la primera semana para que cada fila caiga
        // bajo su día de la semana, pero fuera del mes no se emite ningún día.
        $cursor = $first->startOfWeek(CarbonInterface::MONDAY);
        $gridEnd = $last->endOfWeek(CarbonInterface::SUNDAY);

        while ($cursor->lessThanOrEqualTo($gridEnd)) {
            $days = [];

            for ($i = 0; $i < 7; $i++) {
                if ($cursor->month !== $first->month) {
                    $days[] = null;
                    $cursor = $cursor->addDay();

                    continue;
                }

                $date = $cursor->toDateString();

                $days[] = [
                    'date' => $date,
                    'day' => $cursor->day,
                    // El día de la semana lo resuelve el servidor: la vista de
                    // celular saltea las celdas vacías, así que la posición en la
                    // fila ya no alcanza, y no se parsea una fecha en el navegador.
                    'weekday' => $cursor->dayOfWeekIso,
                    'label' => self::dayLabel($cursor),
                    'is_today' => $date === $today->toDateString(),
                    'activities' => $activities[$date] ?? [],
                ];

                $cursor = $cursor->addDay();
            }

            $weeks[] = ['label' => self::weekLabel($days), 'days' => $days];
        }

        return [
            'month' => $first->format('Y-m'),
            'label' => self::MONTHS[$first->month].' de '.$first->year,
            'today' => $today->toDateString(),
            'weekdays' => array_column(self::WEEKDAYS, 'short'),
            'sources' => self::sources(),
            'weeks' => $weeks,
        ];
    }

    /**
     * Las actividades de la ventana, indexadas por día.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    private static function activities(CarbonImmutable $gridStart, CarbonImmutable $gridEnd): array
    {
        $days = [];
        $seen = [];

        foreach ([...self::classActivities($gridStart, $gridEnd), ...self::eventActivities($gridStart, $gridEnd)] as $activity) {
            $date = $activity['date'];
            unset($activity['date']);

            // Una misma actividad puede estar cargada en dos páginas (o quedar
            // duplicada al clonar una sección): en el día va una sola vez.
            $fingerprint = implode('|', [$date, $activity['title'], $activity['start'] ?? '', $activity['end'] ?? '', $activity['location'] ?? '']);

            if (isset($seen[$fingerprint])) {
                continue;
            }

            $seen[$fingerprint] = true;
            $days[$date][] = $activity;
        }

        foreach ($days as $date => $items) {
            usort($items, fn ($a, $b) => [$a['start'] === null, $a['start'] ?? '', $a['title']] <=> [$b['start'] === null, $b['start'] ?? '', $b['title']]);
            $days[$date] = $items;
        }

        return $days;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function classActivities(CarbonImmutable $gridStart, CarbonImmutable $gridEnd): array
    {
        $sections = Section::query()
            ->where('type', 'class_info')
            ->visible()
            ->onCalendar()
            ->whereHas('page', fn ($query) => $query->visible())
            ->with('page')
            ->orderBy('page_id')
            ->orderBy('position')
            ->get();

        $activities = [];

        foreach ($sections as $section) {
            $content = $section->content ?? [];
            $dates = Occurrences::expand($content['occurrences'] ?? [], $gridStart->toDateString(), $gridEnd->toDateString());

            foreach ($dates as $occurrence) {
                $activities[] = [
                    'date' => $occurrence['date'],
                    'key' => "cls-{$section->id}-{$occurrence['date']}-".($occurrence['start'] ?? 'x'),
                    'kind' => 'clase',
                    'title' => $occurrence['label'] ?? self::firstLine($content['heading'] ?? null) ?? $section->page->title,
                    'start' => $occurrence['start'],
                    'end' => $occurrence['end'],
                    'time_text' => self::blank($content['schedule'] ?? null),
                    'location' => self::blank($content['location'] ?? null),
                    'price' => self::blank($content['price'] ?? null),
                    'cta_label' => self::blank($content['cta_label'] ?? null),
                    'cta_url' => self::blank($content['cta_url'] ?? null),
                    'image_path' => self::blank($content['image'] ?? null),
                    'source' => self::source($section->page),
                ];
            }
        }

        return $activities;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function eventActivities(CarbonImmutable $gridStart, CarbonImmutable $gridEnd): array
    {
        // whereDate() es el único predicado seguro en los dos motores: un cast
        // 'date' se guarda como 'Y-m-d H:i:s' en SQLite y como 'Y-m-d' en MySQL,
        // así que comparar la columna con un string de fecha da distinto en cada uno.
        $events = Event::visible()
            ->onCalendar()
            ->whereNotNull('starts_at')
            ->whereDate('starts_at', '<=', $gridEnd->toDateString())
            ->ordered()
            ->get();

        $page = self::eventsPage();
        $activities = [];

        foreach ($events as $event) {
            $start = CarbonImmutable::parse($event->starts_at)->startOfDay();
            $end = $event->ends_at ? CarbonImmutable::parse($event->ends_at)->startOfDay() : $start;

            if ($end->lessThan($start)) {
                $end = $start;
            }

            if ($end->lessThan($gridStart)) {
                continue;
            }

            $cursor = $start->greaterThan($gridStart) ? $start : $gridStart;
            $last = $end->lessThan($gridEnd) ? $end : $gridEnd;

            while ($cursor->lessThanOrEqualTo($last)) {
                $activities[] = [
                    'date' => $cursor->toDateString(),
                    'key' => "ev-{$event->id}-{$cursor->toDateString()}",
                    'kind' => 'evento',
                    'title' => $event->title,
                    'start' => $event->start_time,
                    'end' => $event->end_time,
                    'time_text' => $event->date_text,
                    'location' => $event->location,
                    'price' => $event->price,
                    'cta_label' => $event->cta_label,
                    'cta_url' => $event->cta_url,
                    'image_path' => $event->image_path,
                    'source' => $page ? self::source($page) : ['slug' => 'eventos', 'title' => 'Eventos', 'url' => null],
                ];

                $cursor = $cursor->addDay();
            }
        }

        return $activities;
    }

    /**
     * Las fuentes que alimentan el calendario, en el orden del menú. Van fuera de
     * la grilla y no dependen del mes: así el color de cada una no cambia al
     * navegar de un mes a otro.
     *
     * @return array<int, array<string, ?string>>
     */
    private static function sources(): array
    {
        $sources = [];

        $pages = Page::inMenu()
            ->whereHas('sections', fn ($query) => $query->where('type', 'class_info')->visible()->onCalendar())
            ->get();

        foreach ($pages as $page) {
            $hasDates = $page->sections()
                ->where('type', 'class_info')
                ->visible()
                ->onCalendar()
                ->get()
                ->contains(fn ($section) => ! empty($section->content['occurrences'] ?? []));

            if ($hasDates) {
                $sources[] = self::source($page);
            }
        }

        if (Event::visible()->onCalendar()->whereNotNull('starts_at')->exists()) {
            $page = self::eventsPage();

            $sources[] = $page ? self::source($page) : ['slug' => 'eventos', 'title' => 'Eventos', 'url' => null];
        }

        return $sources;
    }

    /**
     * Un evento no pertenece a una página: se usa la primera del menú que los
     * liste, para que la ficha del calendario pueda enlazar a algún lado.
     */
    private static function eventsPage(): ?Page
    {
        return Page::inMenu()
            ->whereHas('sections', fn ($query) => $query->where('type', 'event_list')->visible())
            ->first();
    }

    /**
     * @return array<string, ?string>
     */
    private static function source(Page $page): array
    {
        return [
            'slug' => $page->slug,
            'title' => $page->menu_label ?? $page->title,
            'url' => '/'.$page->slug,
        ];
    }

    private static function dayLabel(CarbonImmutable $date): string
    {
        return self::WEEKDAYS[$date->dayOfWeekIso - 1]['long'].' '.$date->day.' de '.self::MONTHS[$date->month];
    }

    /**
     * El rango de la semana, contando sólo sus días del mes (las semanas de los
     * bordes tienen menos de siete). Es el encabezado de la vista de celular.
     *
     * @param  array<int, array<string, mixed>|null>  $days
     */
    private static function weekLabel(array $days): string
    {
        $numbers = array_column(array_filter($days), 'day');

        if ($numbers === []) {
            return '';
        }

        $first = (int) min($numbers);
        $last = (int) max($numbers);
        $month = self::MONTHS[CarbonImmutable::today(self::TIMEZONE)->month];

        return ($first === $last ? $first : $first.' al '.$last).' de '.$month;
    }

    private static function firstLine(mixed $value): ?string
    {
        $value = self::blank($value);

        return $value === null ? null : trim(explode("\n", $value)[0]);
    }

    private static function blank(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? $value : null;
    }
}
