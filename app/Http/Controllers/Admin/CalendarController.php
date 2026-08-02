<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Elige qué eventos aparecen en el calendario público. Las clases semanales no
 * pasan por acá: entran solas con las "Fechas para el calendario" de cada ficha.
 */
class CalendarController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Calendar/Index', [
            'events' => Event::visible()->ordered()->get(),
        ]);
    }

    public function toggle(Request $request, Event $event): RedirectResponse
    {
        // El valor viaja explícito (y no se invierte el actual) para que el
        // marcado optimista del panel no se desincronice con dos clics seguidos.
        $show = $request->validate(['show' => ['required', 'boolean']])['show'];

        $event->update(['show_on_calendar' => $show]);

        return back();
    }

    public function bulk(Request $request): RedirectResponse
    {
        $show = $request->validate(['show' => ['required', 'boolean']])['show'];

        // visible() es imprescindible: los eventos ocultos no se listan, así que
        // destildar todo no puede pisarles la marca. Y sin fecha de inicio no hay
        // día donde ubicarlos, por eso quedan afuera.
        Event::visible()->whereNotNull('starts_at')->update(['show_on_calendar' => $show]);

        return back()->with('success', $show ? 'Todos los eventos van al calendario.' : 'Se quitaron los eventos del calendario.');
    }
}
