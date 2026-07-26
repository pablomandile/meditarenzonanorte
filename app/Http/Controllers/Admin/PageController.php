<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Support\SectionRegistry;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function index(): Response
    {
        $pages = Page::withCount('sections')->orderBy('menu_order')->get()
            ->map(fn ($page) => [
                'id' => $page->id,
                'slug' => $page->slug,
                'title' => $page->title,
                'menu_label' => $page->menu_label,
                'visible' => $page->visible,
                'sections_count' => $page->sections_count,
            ]);

        return Inertia::render('Admin/Pages/Index', ['pages' => $pages]);
    }

    public function show(Page $page): Response
    {
        return Inertia::render('Admin/Pages/Show', [
            'page' => [
                'id' => $page->id,
                'slug' => $page->slug,
                'title' => $page->title,
            ],
            'sections' => $page->sections->map(fn ($section) => [
                'id' => $section->id,
                'type' => $section->type,
                'type_label' => SectionRegistry::label($section->type),
                'key' => $section->key,
                'title' => Str::limit(
                    str_replace("\n", ' ', $section->content['heading'] ?? $section->content['quote'] ?? ''),
                    70,
                ) ?: null,
                'position' => $section->position,
                'visible' => $section->visible,
            ])->values(),
        ]);
    }
}
