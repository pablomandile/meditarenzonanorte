<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Section;
use App\Models\Setting;
use App\Support\EventCalendar;
use App\Support\Occurrences;
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
                'content' => self::content($section),
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

        // El pop-up sale solo en la portada. Se arma en Admin\PopupController.
        if ($page->slug === 'home') {
            $props['popup'] = self::popup();
        }

        return Inertia::render('Public/Page', $props);
    }

    /**
     * Lo que necesita SitePopup.vue, o null si está apagado o si el evento elegido
     * ya no se publica: un pop-up que lleva a un evento oculto no tiene sentido.
     *
     * @return array<string, mixed>|null
     */
    private static function popup(): ?array
    {
        if (Setting::get('popup_enabled') !== '1') {
            return null;
        }

        $kind = Setting::get('popup_kind', 'image');
        $event = null;

        if ($kind === 'event') {
            $event = Event::visible()->find(Setting::get('popup_event_id'));

            if (! $event) {
                return null;
            }
        } elseif (! Setting::get('popup_image_path')) {
            return null;
        }

        return [
            'version' => Setting::get('popup_version', '0'),
            'mode' => Setting::get('popup_mode', 'once'),
            'kind' => $kind,
            'text' => Setting::get('popup_text'),
            'image_path' => Setting::get('popup_image_path'),
            'event' => $event,
        ];
    }

    /**
     * El contenido que recibe la vista. En las fichas de clase, el "Horario" se
     * arma con las "Fechas para el calendario" cuando está vacío: así no hay que
     * escribir la misma cosa dos veces. Lo guardado no se toca — sólo se completa
     * lo que se publica.
     *
     * @return array<string, mixed>
     */
    private static function content(Section $section): array
    {
        $content = $section->content ?? [];

        if ($section->type === 'class_info' && blank($content['schedule'] ?? null)) {
            $content['schedule'] = Occurrences::schedule($content['occurrences'] ?? []);
        }

        return $content;
    }
}
