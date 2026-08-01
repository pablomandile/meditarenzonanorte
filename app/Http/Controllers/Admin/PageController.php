<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Support\SectionRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    /**
     * Hiding a page drops it from the nav (Page::inMenu filters by visible) and
     * makes its public URL 404. The home page is off limits: it is the site root
     * and never shows in the nav, so hiding or moving it makes no sense.
     */
    public function toggle(Page $page): RedirectResponse
    {
        abort_if($page->slug === 'home', 403);

        $page->update(['visible' => ! $page->visible]);

        return back()->with('success', $page->visible
            ? 'Página visible. Volvió al menú del sitio.'
            : 'Página oculta. Salió del menú del sitio.');
    }

    /**
     * Swaps menu_order with the neighbouring page, which is the order the public
     * nav uses. Home is excluded so it always stays first and keeps order 0.
     */
    public function move(Request $request, Page $page): RedirectResponse
    {
        abort_if($page->slug === 'home', 403);

        $direction = $request->validate(['direction' => ['required', 'in:up,down']])['direction'];

        $sibling = Page::where('slug', '!=', 'home')
            ->when($direction === 'up',
                fn ($q) => $q->where('menu_order', '<', $page->menu_order)->orderByDesc('menu_order'),
                fn ($q) => $q->where('menu_order', '>', $page->menu_order)->orderBy('menu_order'),
            )
            ->first();

        if ($sibling) {
            [$page->menu_order, $sibling->menu_order] = [$sibling->menu_order, $page->menu_order];
            $page->save();
            $sibling->save();
        }

        return back();
    }
}
