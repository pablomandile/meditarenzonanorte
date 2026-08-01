<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSectionRequest;
use App\Models\Faq;
use App\Models\Section;
use App\Support\ImageStorage;
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

        return Inertia::render('Admin/Sections/Edit', $props);
    }

    public function update(UpdateSectionRequest $request, Section $section): RedirectResponse
    {
        $content = $request->validated()['content'] ?? [];
        $files = $request->file('files') ?? [];
        $old = $section->content ?? [];
        $owned = self::imagePaths($section->type, $old);

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

    /**
     * Every image path the section already stores. A submitted path outside this
     * set was picked from the gallery and belongs to another record, so it has
     * to be adopted instead of shared — see ImageStorage::adopt().
     *
     * @param  array<string, mixed>  $content
     * @return array<int, string>
     */
    private static function imagePaths(string $type, array $content): array
    {
        $paths = [];

        foreach (SectionRegistry::fields($type) as $field) {
            $value = $content[$field['key']] ?? null;

            switch ($field['type']) {
                case 'image':
                    $paths[] = $value;
                    break;

                case 'images':
                    $paths = [...$paths, ...array_values((array) $value)];
                    break;

                case 'cards':
                    $paths = [...$paths, ...array_column((array) $value, 'image')];
                    break;
            }
        }

        return array_values(array_filter($paths, fn ($path) => is_string($path) && $path !== ''));
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
