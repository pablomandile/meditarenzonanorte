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

        foreach (SectionRegistry::fields($section->type) as $field) {
            $key = $field['key'];

            switch ($field['type']) {
                case 'image':
                    if (isset($files[$key])) {
                        $content[$key] = ImageStorage::replace($files[$key], 'sections', $old[$key] ?? null);
                    }
                    break;

                case 'cards':
                    foreach ($content[$key] ?? [] as $i => $card) {
                        if (isset($files[$key][$i]['image'])) {
                            $content[$key][$i]['image'] = ImageStorage::replace(
                                $files[$key][$i]['image'],
                                'sections',
                                $old[$key][$i]['image'] ?? null,
                            );
                        }
                    }
                    $content[$key] = array_values(array_filter($content[$key] ?? [], fn ($card) => array_filter($card ?? [])));
                    break;

                case 'images':
                    foreach ($files[$key] ?? [] as $i => $file) {
                        $content[$key][$i] = ImageStorage::replace($file, 'sections', $old[$key][$i] ?? null);
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

                case 'faq_picker':
                    $content[$key] = array_values(array_map('intval', $content[$key] ?? []));
                    break;
            }
        }

        $section->update(['content' => $content]);

        return back()->with('success', 'Sección guardada.');
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
