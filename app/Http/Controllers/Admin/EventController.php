<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EventRequest;
use App\Models\Event;
use App\Support\ImageStorage;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Events/Index', [
            'events' => Event::ordered()->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Events/Create');
    }

    public function store(EventRequest $request): RedirectResponse
    {
        $data = $request->validated();
        unset($data['image']);

        if ($request->hasFile('image')) {
            $data['image_path'] = ImageStorage::store($request->file('image'), 'events');
        }

        $data['position'] = (int) Event::max('position') + 1;

        Event::create($data);

        return redirect()->route('admin.events.index')->with('success', 'Evento creado.');
    }

    public function edit(Event $event): Response
    {
        return Inertia::render('Admin/Events/Edit', ['event' => $event]);
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

        $event->update($data);

        return redirect()->route('admin.events.index')->with('success', 'Evento guardado.');
    }

    public function destroy(Event $event): RedirectResponse
    {
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
}
