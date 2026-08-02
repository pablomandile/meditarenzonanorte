<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Section;
use App\Support\Occurrences;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Elige qué aparece en el calendario público: las fichas de clase (clases, cursos
 * y retiros, gratis) y los eventos, cada uno con su tilde.
 *
 * Sólo se listan las que podrían aparecer, es decir las visibles en una página
 * visible: lo que está oculto en el sitio tampoco está en el calendario.
 */
class CalendarController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Calendar/Index', [
            'cards' => $this->cards(),
            'events' => Event::visible()->ordered()->get(),
        ]);
    }

    /**
     * Las fichas de clase que pueden llegar al calendario, agrupadas por página en
     * el orden del menú, con las fechas ya resumidas para leerlas de un vistazo.
     *
     * @return array<int, array<string, mixed>>
     */
    private function cards(): array
    {
        return Section::query()
            ->where('type', 'class_info')
            ->visible()
            ->whereHas('page', fn ($query) => $query->visible())
            ->with('page')
            ->get()
            ->sortBy([fn ($a, $b) => $a->page->menu_order <=> $b->page->menu_order, fn ($a, $b) => $a->position <=> $b->position])
            ->map(fn (Section $section) => [
                'id' => $section->id,
                'page' => $section->page->menu_label ?? $section->page->title,
                'title' => $this->cardTitle($section),
                'dates' => Occurrences::schedule($section->content['occurrences'] ?? []) ?? '',
                'show_on_calendar' => $section->show_on_calendar,
                'edit_url' => "/admin/sections/{$section->id}/edit",
            ])
            ->values()
            ->all();
    }

    private function cardTitle(Section $section): string
    {
        $heading = trim((string) ($section->content['heading'] ?? ''));

        return $heading === '' ? $section->key : trim(explode("\n", $heading)[0]);
    }

    public function toggleSection(Request $request, Section $section): RedirectResponse
    {
        // El valor viaja explícito (y no se invierte el actual) para que el
        // marcado optimista del panel no se desincronice con dos clics seguidos.
        $show = $request->validate(['show' => ['required', 'boolean']])['show'];

        $section->update(['show_on_calendar' => $show]);

        return back();
    }

    public function toggleEvent(Request $request, Event $event): RedirectResponse
    {
        $show = $request->validate(['show' => ['required', 'boolean']])['show'];

        $event->update(['show_on_calendar' => $show]);

        return back();
    }

    public function bulk(Request $request): RedirectResponse
    {
        $show = $request->validate(['show' => ['required', 'boolean']])['show'];

        // Los guardas son los mismos que en el listado: lo oculto no se lista, así
        // que destildar todo no puede pisarle la marca. Y sin fecha no hay día
        // donde ubicar un evento, por eso queda afuera.
        Section::query()
            ->where('type', 'class_info')
            ->visible()
            ->whereHas('page', fn ($query) => $query->visible())
            ->update(['show_on_calendar' => $show]);

        Event::visible()->whereNotNull('starts_at')->update(['show_on_calendar' => $show]);

        return back()->with('success', $show ? 'Todo va al calendario.' : 'Se vació el calendario.');
    }
}
