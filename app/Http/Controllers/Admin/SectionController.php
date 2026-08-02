<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSectionRequest;
use App\Models\Faq;
use App\Models\Section;
use App\Support\ImageStorage;
use App\Support\Occurrences;
use App\Support\SectionRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SectionController extends Controller
{
    public function edit(Section $section): Response
    {
        $props = [
            'section' => [
                'id' => $section->id,
                'type' => $section->type,
                'type_label' => SectionRegistry::label($section->type),
                'key' => $section->key,
                'visible' => $section->visible,
                'content' => $section->content ?? [],
            ],
            'fields' => SectionRegistry::fields($section->type),
            'page' => [
                'id' => $section->page->id,
                'title' => $section->page->title,
                'slug' => $section->page->slug,
            ],
        ];

        if ($section->type === 'faq') {
            $props['faqPool'] = Faq::ordered()->get(['id', 'question', 'visible']);
        }

        // Aclaraciones por campo, debajo del input. Hoy sólo el horario, que se
        // arma con las fechas del calendario si se deja vacío.
        if ($section->type === 'class_info') {
            $auto = Occurrences::schedule($section->content['occurrences'] ?? []);

            $props['hints'] = [
                // Las llaves son necesarias: sin ellas PHP toma la comilla de cierre
                // como parte del nombre de la variable (UTF-8 vale en identificadores).
                'schedule' => $auto
                    ? "Vacío se publica “{$auto}”, armado con las fechas de abajo. Escribí algo sólo si querés otro texto."
                    : 'Cargá las “Fechas para el calendario” de abajo y el horario se arma solo.',
            ];
        }

        return Inertia::render('Admin/Sections/Edit', $props);
    }

    public function update(UpdateSectionRequest $request, Section $section): RedirectResponse
    {
        $content = $request->validated()['content'] ?? [];
        $files = $request->file('files') ?? [];
        $old = $section->content ?? [];
        $owned = SectionRegistry::imagePaths($section->type, $old);

        foreach (SectionRegistry::fields($section->type) as $field) {
            $key = $field['key'];

            switch ($field['type']) {
                case 'image':
                    if (isset($files[$key])) {
                        $content[$key] = ImageStorage::replace($files[$key], 'sections', $old[$key] ?? null);
                    } else {
                        $content[$key] = ImageStorage::adopt($content[$key] ?? null, $owned, 'sections');
                    }
                    break;

                case 'cards':
                    foreach ($content[$key] ?? [] as $i => $card) {
                        if (isset($files[$key][$i]['image'])) {
                            $content[$key][$i]['image'] = ImageStorage::replace(
                                $files[$key][$i]['image'],
                                'sections',
                                self::replacedPath($card['image'] ?? null, $owned),
                            );
                        } elseif (isset($card['image'])) {
                            $content[$key][$i]['image'] = ImageStorage::adopt($card['image'], $owned, 'sections');
                        }
                    }
                    $content[$key] = array_values(array_filter($content[$key] ?? [], fn ($card) => array_filter($card ?? [])));
                    break;

                case 'images':
                    foreach ($files[$key] ?? [] as $i => $file) {
                        $content[$key][$i] = ImageStorage::replace(
                            $file,
                            'sections',
                            self::replacedPath($content[$key][$i] ?? null, $owned),
                        );
                    }
                    foreach ($content[$key] ?? [] as $i => $path) {
                        if (! isset($files[$key][$i])) {
                            $content[$key][$i] = ImageStorage::adopt($path, $owned, 'sections');
                        }
                    }
                    $content[$key] = array_values(array_filter($content[$key] ?? []));
                    break;

                case 'links':
                    $content[$key] = array_values(array_filter($content[$key] ?? [], fn ($link) => array_filter($link ?? [])));
                    break;

                case 'items':
                    $content[$key] = array_values(array_filter(
                        $content[$key] ?? [],
                        fn ($item) => $item !== null && trim((string) $item) !== '',
                    ));
                    break;

                case 'plans':
                    $content[$key] = array_values(array_filter(
                        $content[$key] ?? [],
                        fn ($plan) => trim((string) ($plan['name'] ?? '')) !== '' || trim((string) ($plan['price'] ?? '')) !== '',
                    ));
                    break;

                case 'faq_picker':
                    $content[$key] = array_values(array_map('intval', $content[$key] ?? []));
                    break;

                case 'occurrences':
                    $content[$key] = array_values(array_map(fn ($row) => [
                        'type' => ($row['type'] ?? null) === 'date' ? 'date' : 'weekly',
                        // El formulario manda el día como "3"; guardarlo entero deja
                        // el JSON estable y comparable.
                        'weekday' => is_numeric($row['weekday'] ?? null) ? (int) $row['weekday'] : null,
                        'date' => self::blankToNull($row['date'] ?? null),
                        'from' => self::blankToNull($row['from'] ?? null),
                        'until' => self::blankToNull($row['until'] ?? null),
                        'start' => self::blankToNull($row['start'] ?? null),
                        'end' => self::blankToNull($row['end'] ?? null),
                        'label' => self::blankToNull($row['label'] ?? null),
                    ], array_filter(
                        $content[$key] ?? [],
                        fn ($row) => ($row['type'] ?? null) === 'date'
                            ? trim((string) ($row['date'] ?? '')) !== ''
                            : trim((string) ($row['weekday'] ?? '')) !== '',
                    )));
                    break;
            }
        }

        $section->update(['content' => $content]);

        return back()->with('success', 'Sección guardada.');
    }

    /**
     * The file an upload replaces, taken from the path the form submits for that
     * card/image instead of from its index: cards can be reordered, so the index
     * may now point at another card's image and would delete the wrong file.
     * Only paths the section already owns are deletable — a path just picked
     * from the gallery still belongs to somebody else.
     *
     * @param  array<int, string>  $owned
     */
    private static function replacedPath(?string $submitted, array $owned): ?string
    {
        return in_array($submitted, $owned, true) ? $submitted : null;
    }

    /** Los campos vacíos del formulario llegan como '' y se guardan como null. */
    private static function blankToNull(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    /**
     * Clones a section right below the original, hidden so the public page does
     * not show it twice until the copy is edited and made visible.
     */
    public function duplicate(Section $section): RedirectResponse
    {
        DB::transaction(function () use ($section) {
            Section::where('page_id', $section->page_id)
                ->where('position', '>', $section->position)
                ->increment('position');

            Section::create([
                'page_id' => $section->page_id,
                'type' => $section->type,
                'key' => self::copyKey($section),
                'position' => $section->position + 1,
                'visible' => false,
                'content' => self::duplicateImages($section->type, $section->content ?? []),
            ]);
        });

        return back()->with('success', 'Sección clonada. La copia quedó oculta, justo debajo del original.');
    }

    /**
     * "hero" → "hero-copia" → "hero-copia-2". Cloning a copy numbers it from the
     * original key instead of piling up suffixes.
     */
    private static function copyKey(Section $section): string
    {
        $base = preg_replace('/-copia(-\d+)?$/', '', $section->key).'-copia';
        $key = $base;
        $n = 1;

        while (Section::where('page_id', $section->page_id)->where('key', $key)->exists()) {
            $key = $base.'-'.++$n;
        }

        return $key;
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    private static function duplicateImages(string $type, array $content): array
    {
        foreach (SectionRegistry::fields($type) as $field) {
            $key = $field['key'];

            switch ($field['type']) {
                case 'image':
                    if (isset($content[$key])) {
                        $content[$key] = ImageStorage::duplicate($content[$key]);
                    }
                    break;

                case 'images':
                    foreach ($content[$key] ?? [] as $i => $path) {
                        $content[$key][$i] = ImageStorage::duplicate($path);
                    }
                    break;

                case 'cards':
                    foreach ($content[$key] ?? [] as $i => $card) {
                        if (isset($card['image'])) {
                            $content[$key][$i]['image'] = ImageStorage::duplicate($card['image']);
                        }
                    }
                    break;
            }
        }

        return $content;
    }

    public function toggle(Section $section): RedirectResponse
    {
        $section->update(['visible' => ! $section->visible]);

        return back()->with('success', $section->visible ? 'Sección visible.' : 'Sección oculta.');
    }

    public function move(Request $request, Section $section): RedirectResponse
    {
        $direction = $request->validate(['direction' => ['required', 'in:up,down']])['direction'];

        $sibling = Section::where('page_id', $section->page_id)
            ->when($direction === 'up',
                fn ($q) => $q->where('position', '<', $section->position)->orderByDesc('position'),
                fn ($q) => $q->where('position', '>', $section->position)->orderBy('position'),
            )
            ->first();

        if ($sibling) {
            [$section->position, $sibling->position] = [$sibling->position, $section->position];
            $section->save();
            $sibling->save();
        }

        return back();
    }
}
