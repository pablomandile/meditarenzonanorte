<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Teacher;
use App\Support\EventCalendar;
use App\Support\Occurrences;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
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
        // La plantilla y las landings de evento no son páginas del sitio: las
        // landings tienen su dirección bajo /eventos-especiales.
        abort_unless($page->visible && $page->isRegular(), 404);

        if ($page->slug === 'home') {
            return redirect()->route('home');
        }

        return $this->render($page);
    }

    /**
     * La landing de un evento con Link interno. Se publica mientras el evento esté
     * visible y siga en Link interno; si no, da 404 a las visitas, pero quien tiene
     * sesión la sigue viendo (con una cinta que lo avisa) para poder revisarla.
     */
    public function eventLanding(string $slug): Response
    {
        $landing = Page::where('kind', Page::KIND_EVENT_LANDING)->where('slug', $slug)->with('event')->firstOrFail();
        $published = $landing->visible && $landing->event && $landing->event->visible && $landing->event->isInternal();

        abort_unless($published || auth()->check(), 404);

        return self::eventLandingResponse($landing, preview: ! $published);
    }

    /** También la usa la vista previa de la plantilla, desde el panel. */
    public static function eventLandingResponse(Page $landing, bool $preview = false): Response
    {
        return Inertia::render('Public/EventLanding', [
            'page' => [
                'slug' => $landing->slug,
                'title' => $landing->title,
                'meta_description' => $landing->meta_description,
                'kind' => $landing->kind,
                'admin_url' => auth()->check() ? $landing->adminUrl() : null,
            ],
            'preview' => $preview,
            'sections' => $landing->sections()->visible()->orderBy('position')->get()->map(fn ($section) => [
                'id' => $section->id,
                'type' => $section->type,
                'key' => $section->key,
                'content' => $section->content ?? [],
            ])->values(),
        ]);
    }

    private function render(Page $page): Response
    {
        $sections = $page->sections()->visible()->orderBy('position')->get();
        $types = $sections->pluck('type');

        // Los maestr@s de "Datos recurrentes", para las fichas (su ventanita) y
        // las secciones "Maestro / persona" que eligen uno. Una sola consulta.
        $teachers = $types->intersect(['class_info', 'free_classes', 'person'])->isNotEmpty()
            ? Teacher::all()
            : new Collection;

        $props = [
            'page' => [
                'slug' => $page->slug,
                'title' => $page->title,
                'meta_description' => $page->meta_description,
            ],
            // El bloque de fichas gratuitas no se publica como tal: en su lugar van
            // las fichas que espeja, así la vista las dibuja como cualquier otra.
            'sections' => $sections
                ->flatMap(fn ($section) => $section->type === 'free_classes' ? self::freeClasses($page) : [$section])
                ->map(fn ($section) => [
                    'id' => $section->id,
                    'type' => $section->type,
                    'key' => $section->key,
                    'content' => self::content($section, $teachers),
                ])->values(),
        ];

        if ($types->contains('event_strip')) {
            $props['homeEvents'] = Event::visible()->where('show_on_home', true)->ordered()->get();
        }

        if ($types->contains('event_list')) {
            $props['events'] = Event::visible()->ordered()->get();
        }

        if ($types->contains('event_calendar')) {
            $props['calendar'] = EventCalendar::forMonth(request()->query('mes'));
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
     * Las fichas de clase con "Gratuito" tildado de las demás páginas publicadas,
     * en el orden del menú y, dentro de cada página, en el suyo. Se leen del
     * original cada vez: no hay copia que mantener, y destildarla la saca de acá.
     *
     * @return \Illuminate\Support\Collection<int, Section>
     */
    private static function freeClasses(Page $page)
    {
        return Section::query()
            ->select('sections.*')
            ->join('pages', 'pages.id', '=', 'sections.page_id')
            ->where('sections.type', 'class_info')
            ->where('sections.visible', true)
            ->where('sections.is_template', false)
            ->where('sections.page_id', '!=', $page->id)
            ->where('pages.visible', true)
            ->where('pages.kind', Page::KIND_PAGE)
            ->orderByRaw('pages.menu_order is null')
            ->orderBy('pages.menu_order')
            ->orderBy('pages.id')
            ->orderBy('sections.position')
            ->get()
            ->filter(fn (Section $section) => ($section->content['free'] ?? false) === true)
            ->values();
    }

    /**
     * Una sección "Maestro / persona" con un maestr@ elegido muestra el nombre, la
     * descripción y la foto de "Datos recurrentes". Si al maestr@ le falta la
     * descripción o la foto, queda la de la sección; si ya no existe, la sección
     * entera vuelve a lo suyo.
     *
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    private static function withTeacher(array $content, Collection $teachers): array
    {
        $teacher = $teachers->firstWhere('id', $content['teacher_id'] ?? null);

        if (! $teacher) {
            return $content;
        }

        return [
            ...$content,
            'name' => $teacher->name,
            'body' => filled($teacher->description) ? $teacher->description : ($content['body'] ?? null),
            'image' => filled($teacher->photo) ? $teacher->photo : ($content['image'] ?? null),
        ];
    }

    /**
     * @return array<string, array{name: string, description: ?string, photo: ?string}>|\stdClass
     */
    private static function teacherProfiles(mixed $names, Collection $teachers): array|\stdClass
    {
        $byName = $teachers
            ->filter(fn (Teacher $teacher) => $teacher->hasProfile())
            ->keyBy(fn (Teacher $teacher) => Teacher::key($teacher->name));

        $profiles = [];

        foreach (explode(',', is_string($names) ? $names : '') as $name) {
            $teacher = $byName->get(Teacher::key($name));

            if ($teacher) {
                $profiles[Teacher::key($name)] = [
                    'name' => $teacher->name,
                    'description' => $teacher->description,
                    'photo' => $teacher->photo,
                ];
            }
        }

        // Vacío va como objeto: un [] de JSON no es un mapa.
        return $profiles ?: new \stdClass;
    }

    /**
     * El contenido que recibe la vista. En las fichas de clase, el "Horario" se
     * arma con las "Fechas para el calendario" cuando está vacío: así no hay que
     * escribir la misma cosa dos veces. Lo guardado no se toca — sólo se completa
     * lo que se publica.
     *
     * @return array<string, mixed>
     */
    private static function content(Section $section, Collection $teachers): array
    {
        $content = $section->content ?? [];

        if ($section->type === 'person') {
            $content = self::withTeacher($content, $teachers);
        }

        // Los maestr@s de la ficha que tienen descripción o foto, por nombre
        // normalizado: al tocar su nombre se abre una ventanita con eso.
        if ($section->type === 'class_info') {
            $content['teacher_profiles'] = self::teacherProfiles($content['teachers'] ?? null, $teachers);
        }

        if ($section->type === 'class_info' && blank($content['schedule'] ?? null)) {
            $content['schedule'] = Occurrences::schedule($content['occurrences'] ?? []);
        }

        // Una ficha gratuita sin precio escrito dice que es gratuita.
        if ($section->type === 'class_info') {
            $content['price'] = Section::publishedPrice($content);
        }

        // Pasada la última fecha (al día siguiente, en hora argentina), la ficha se
        // ve como terminada: afiche en ocre y el botón de inscripción apagado.
        if ($section->type === 'class_info') {
            $content['finished'] = Occurrences::finished(
                $content['occurrences'] ?? [],
                CarbonImmutable::today(EventCalendar::TIMEZONE)->toDateString(),
            );
        }

        return $content;
    }
}
