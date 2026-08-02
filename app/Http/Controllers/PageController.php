<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Faq;
use App\Models\Page;
use App\Support\EventCalendar;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function home(): Response
    {
        return $this->render(Page::where('slug', 'home')->firstOrFail());
    }

    public function show(Page $page): Response|RedirectResponse
    {
        abort_unless($page->visible, 404);

        if ($page->slug === 'home') {
            return redirect()->route('home');
        }

        return $this->render($page);
    }

    private function render(Page $page): Response
    {
        $sections = $page->sections()->visible()->orderBy('position')->get();
        $types = $sections->pluck('type');

        $props = [
            'page' => [
                'slug' => $page->slug,
                'title' => $page->title,
                'meta_description' => $page->meta_description,
            ],
            'sections' => $sections->map(fn ($section) => [
                'id' => $section->id,
                'type' => $section->type,
                'key' => $section->key,
                'content' => $section->content ?? [],
            ])->values(),
        ];

        if ($types->contains('event_strip')) {
            $props['homeEvents'] = Event::visible()->where('show_on_home', true)->ordered()->get();
        }

        if ($types->contains('event_list')) {
            $props['events'] = Event::visible()->ordered()->get();
        }

        if ($types->contains('event_calendar')) {
            $props['calendar'] = EventCalendar::currentMonth();
        }

        $faqIds = $sections->where('type', 'faq')
            ->flatMap(fn ($section) => $section->content['faq_ids'] ?? [])
            ->unique()
            ->values();

        if ($faqIds->isNotEmpty()) {
            $props['faqs'] = Faq::visible()->whereIn('id', $faqIds)->get()
                ->keyBy('id')
                ->map(fn ($faq) => ['question' => $faq->question, 'answer' => $faq->answer]);
        }

        return Inertia::render('Public/Page', $props);
    }
}
