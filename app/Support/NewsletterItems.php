<?php

namespace App\Support;

use App\Models\Event;
use App\Models\Section;

/**
 * El puente entre lo que ya está cargado en el sitio y lo que sale en el mail.
 *
 * Una ficha de clase y un evento se guardan distinto —una es una sección con su JSON,
 * el otro una fila con sus columnas— pero en el newsletter se ven igual: un título,
 * una foto, cuándo es, dónde y cuánto sale. Acá se les da esa forma única, y la
 * plantilla del mail no tiene que saber de dónde salió cada uno.
 *
 * Nada de esto reescribe textos: el horario y la fecha ya están armados en castellano
 * por Occurrences y por Event, y se usan tal cual, con el mismo orden de preferencia
 * que el sitio público (ver PageController::content()). Si el mail dijera una fecha
 * distinta a la de la página, el problema sería peor que no mandar el mail.
 */
class NewsletterItems
{
    /**
     * Todo lo que se puede elegir, agrupado para el selector del panel.
     *
     * @return array<string, mixed>
     */
    public static function pool(): array
    {
        $classes = Section::query()
            ->where('type', 'class_info')
            ->where('visible', true)
            // Las plantillas son moldes para clonar, no clases que existan: si se
            // colaran acá, el mail anunciaría una actividad inventada.
            ->where('is_template', false)
            ->with('page')
            ->get()
            ->sortBy([
                fn (Section $a, Section $b) => $a->page->menu_order <=> $b->page->menu_order,
                fn (Section $a, Section $b) => $a->position <=> $b->position,
            ])
            ->map(fn (Section $section) => [
                'kind' => 'section',
                'id' => $section->id,
                'title' => $section->content['heading'] ?? '(sin título)',
                'hint' => self::sectionWhen($section->content ?? []) ?? '',
                'group' => $section->page->menu_label ?? $section->page->title,
            ])
            ->values()
            ->all();

        $events = Event::visible()
            ->ordered()
            ->get()
            ->map(fn (Event $event) => [
                'kind' => 'event',
                'id' => $event->id,
                'title' => $event->title,
                'hint' => $event->date_label ?? '',
                'group' => 'Eventos',
            ])
            ->values()
            ->all();

        return [
            'classes' => $classes,
            'events' => $events,
        ];
    }

    /**
     * Las referencias guardadas ({kind, id}) convertidas en lo que la plantilla dibuja.
     *
     * Lo que ya no existe se saltea en silencio: entre programar el envío y mandarlo
     * puede pasar cualquier cosa, y un mail con un agujero es mejor que un error.
     *
     * @param  array<int, array{kind: string, id: int|string}>  $items
     * @return array<int, array<string, mixed>>
     */
    public static function resolve(array $items): array
    {
        // Una consulta por tabla y no una por item: el orden lo pone $items, no la base.
        $ids = fn (string $kind) => collect($items)
            ->filter(fn ($item) => ($item['kind'] ?? null) === $kind)
            ->pluck('id')
            ->all();

        $sections = Section::query()->with('page')->findMany($ids('section'))->keyBy('id');
        $events = Event::query()->findMany($ids('event'))->keyBy('id');

        $resolved = [];

        foreach ($items as $item) {
            $model = match ($item['kind'] ?? null) {
                'section' => $sections->get($item['id']),
                'event' => $events->get($item['id']),
                default => null,
            };

            if ($model === null) {
                continue;
            }

            $resolved[] = $model instanceof Section
                ? self::fromSection($model)
                : self::fromEvent($model);
        }

        return $resolved;
    }

    /**
     * Lo mismo que resolve(), pero sin saltear nada: devuelve una entrada POR CADA
     * referencia guardada, marcando la que ya no existe.
     *
     * Es lo que necesita el selector del panel. resolve() calla lo que falta porque
     * está armando un mail; acá, en cambio, hay que poder mostrar "esta ficha ya no
     * está" para que se pueda sacar de la lista a mano.
     *
     * Ojo: acá NO se filtra por visible. Una ficha que se ocultó del sitio sigue
     * existiendo, y decir que se borró sería mentira.
     *
     * @param  array<int, array{kind: string, id: int|string}>  $items
     * @return array<int, array{kind: string, id: int, title: string, missing: bool}>
     */
    public static function describe(array $items): array
    {
        $sections = Section::query()
            ->findMany(collect($items)->where('kind', 'section')->pluck('id')->all())
            ->keyBy('id');

        $events = Event::query()
            ->findMany(collect($items)->where('kind', 'event')->pluck('id')->all())
            ->keyBy('id');

        return collect($items)->map(function (array $item) use ($sections, $events) {
            $kind = $item['kind'] ?? 'section';
            $id = (int) ($item['id'] ?? 0);

            $title = $kind === 'section'
                ? ($sections->get($id)?->content['heading'] ?? null)
                : $events->get($id)?->title;

            return [
                'kind' => $kind,
                'id' => $id,
                'title' => $title ?? 'Ya no existe',
                'missing' => $title === null,
            ];
        })->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    private static function fromSection(Section $section): array
    {
        $content = $section->content ?? [];

        return [
            'kind' => 'section',
            'id' => $section->id,
            'title' => (string) ($content['heading'] ?? ''),
            'teachers' => self::teachers($content['teachers'] ?? null),
            'body' => (string) ($content['body'] ?? ''),
            'image' => self::imageUrl($content['image'] ?? null),
            'when' => self::sectionWhen($content),
            'location' => self::text($content['location'] ?? null),
            'price' => self::text($content['price'] ?? null),
            'cta_label' => self::text($content['cta_label'] ?? null) ?? 'Ver más',
            'cta_url' => self::link($content['cta_url'] ?? null) ?? self::sectionUrl($section),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function fromEvent(Event $event): array
    {
        return [
            'kind' => 'event',
            'id' => $event->id,
            'title' => (string) $event->title,
            'teachers' => null,
            'body' => (string) ($event->description ?? ''),
            'image' => self::imageUrl($event->image_path),
            'when' => self::text($event->date_label),
            'location' => self::text($event->location),
            'price' => self::text($event->price),
            'cta_label' => self::text($event->cta_label) ?? 'Ver más',
            // El mismo orden que la tarjeta del sitio: el enlace propio del afiche, si
            // no el del botón, y si no la página de eventos.
            'cta_url' => self::link($event->cta_url)
                ?? self::link($event->image_url)
                ?? url('/eventos-especiales'),
        ];
    }

    /**
     * El horario de una ficha: el texto escrito a mano y, si está vacío, el que se
     * arma con las fechas del calendario. El mismo orden que usa la página pública.
     *
     * @param  array<string, mixed>  $content
     */
    private static function sectionWhen(array $content): ?string
    {
        return filled($content['schedule'] ?? null)
            ? trim((string) $content['schedule'])
            : Occurrences::schedule($content['occurrences'] ?? []);
    }

    /** El enlace a la ficha dentro del sitio, con su ancla si la tiene. */
    private static function sectionUrl(Section $section): string
    {
        $slug = $section->page->slug ?? 'home';
        $path = $slug === 'home' ? '/' : '/'.$slug;
        $anchor = $section->content['anchor'] ?? null;

        // Sin barra antes del #, como el resto del sitio: con ella la redirección se
        // come el fragmento (está explicado en el README).
        return url($path).(filled($anchor) ? '#'.$anchor : '');
    }

    /**
     * "Ana, Luis" → "con Ana y Luis", como lo escribe la tarjeta del sitio.
     */
    private static function teachers(?string $raw): ?string
    {
        $names = array_values(array_filter(array_map('trim', explode(',', (string) $raw))));

        if ($names === []) {
            return null;
        }

        $last = array_pop($names);

        return 'con '.($names === [] ? $last : implode(', ', $names).' y '.$last);
    }

    /**
     * En un mail no existe la URL relativa: el cliente de correo no tiene de dónde
     * colgarla. Todo sale absoluto, contra APP_URL.
     */
    private static function imageUrl(?string $path): ?string
    {
        return filled($path) ? url('/storage/'.ltrim((string) $path, '/')) : null;
    }

    private static function link(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        return str_starts_with($url, 'http://') || str_starts_with($url, 'https://')
            ? $url
            : url($url);
    }

    private static function text(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
