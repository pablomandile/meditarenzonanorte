<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EventRequest;
use App\Models\Event;
use App\Models\Venue;
use App\Support\EventLanding;
use App\Support\ImageStorage;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Events/Index', [
            'events' => Event::ordered()->with('landing:id,event_id,slug')->get()
                ->map(fn (Event $event) => [...$event->toArray(), 'landing' => self::landing($event)]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Events/Create', ['venues' => Venue::ordered()->pluck('name')]);
    }

    public function store(EventRequest $request): RedirectResponse
    {
        $data = $request->validated();
        unset($data['image']);

        if ($request->hasFile('image')) {
            $data['image_path'] = ImageStorage::store($request->file('image'), 'events');
        }

        $data['position'] = (int) Event::max('position') + 1;

        $event = Event::create(self::withoutLinkFields($data));
        $created = self::syncLink($event, $data);

        return $created
            ? redirect()->route('admin.pages.show', $event->landing)->with('success', 'Evento creado. Esta es su landing: editá las secciones.')
            : redirect()->route('admin.events.index')->with('success', 'Evento creado.');
    }

    public function edit(Event $event): Response
    {
        return Inertia::render('Admin/Events/Edit', [
            'event' => [...$event->toArray(), 'landing' => self::landing($event)],
            // Los lugares de "Datos recurrentes", para elegir en el campo Lugar.
            'venues' => Venue::ordered()->pluck('name'),
        ]);
    }

    public function update(EventRequest $request, Event $event): RedirectResponse
    {
        $data = $request->validated();
        unset($data['image']);

        if ($request->hasFile('image')) {
            $data['image_path'] = ImageStorage::replace($request->file('image'), 'events', $event->image_path);
        } elseif (array_key_exists('image_path', $data) && $data['image_path'] === null && $event->image_path) {
            ImageStorage::delete($event->image_path);
        }

        $event->update(self::withoutLinkFields($data));
        $created = self::syncLink($event, $data);

        return $created
            ? redirect()->route('admin.pages.show', $event->landing)->with('success', 'Evento guardado. Se creó su landing: editá las secciones.')
            : redirect()->route('admin.events.index')->with('success', 'Evento guardado.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        if ($landing = $event->landing()->first()) {
            EventLanding::delete($landing);
        }

        ImageStorage::delete($event->image_path);
        $event->delete();

        return redirect()->route('admin.events.index')->with('success', 'Evento eliminado.');
    }

    public function toggle(Event $event): RedirectResponse
    {
        $event->update(['visible' => ! $event->visible]);

        return back()->with('success', $event->visible ? 'Evento visible.' : 'Evento oculto.');
    }

    public function toggleHome(Event $event): RedirectResponse
    {
        $event->update(['show_on_home' => ! $event->show_on_home]);

        return back()->with('success', $event->show_on_home ? 'Evento destacado en la home.' : 'Evento quitado de la home.');
    }

    /**
     * Los enlaces se guardan aparte, en syncLink(), porque con Link interno los
     * decide la landing y no el formulario.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function withoutLinkFields(array $data): array
    {
        unset($data['link_type'], $data['cta_url'], $data['image_url']);

        return $data;
    }

    /**
     * Link externo: las URLs del formulario, como siempre.
     *
     * Link interno: el evento lleva a su landing (se crea copiando la plantilla si
     * todavía no la tiene). Se guarda su dirección en cta_url y se vacía image_url,
     * y así la tarjeta, el listado, el pop-up, el calendario y el newsletter —que
     * ya leen esos campos— llevan a la landing sin cambiar nada en ellos.
     *
     * Volver a externo no borra la landing: deja de publicarse y queda guardada.
     *
     * @param  array<string, mixed>  $data
     * @return bool true si se acaba de crear la landing
     */
    private static function syncLink(Event $event, array $data): bool
    {
        $type = ($data['link_type'] ?? null) === Event::LINK_INTERNAL ? Event::LINK_INTERNAL : Event::LINK_EXTERNAL;
        $landing = $event->landing()->first();

        if ($type === Event::LINK_EXTERNAL) {
            $ctaUrl = $data['cta_url'] ?? null;

            // La dirección de la landing que quedó del modo interno no es una URL
            // que haya elegido nadie: sin landing publicada, llevaría a un 404.
            if ($landing && $ctaUrl === $landing->publicPath()) {
                $ctaUrl = null;
            }

            $event->update(['link_type' => $type, 'cta_url' => $ctaUrl, 'image_url' => $data['image_url'] ?? null]);

            return false;
        }

        $created = ! $landing;
        $landing ??= EventLanding::createFor($event);

        $event->update([
            'link_type' => $type,
            'cta_url' => $landing->publicPath(),
            'image_url' => null,
            'cta_label' => filled($event->cta_label) ? $event->cta_label : 'Más información',
        ]);

        $event->setRelation('landing', $landing);

        return $created;
    }

    /**
     * @return array{id: int, url: string, published: bool}|null
     */
    private static function landing(Event $event): ?array
    {
        $landing = $event->landing;

        return $landing ? [
            'id' => $landing->id,
            'url' => $landing->publicPath(),
            'published' => $event->isInternal() && $event->visible,
        ] : null;
    }
}
