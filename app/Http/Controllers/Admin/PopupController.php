<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Setting;
use App\Support\ImageStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * El pop-up de la portada. Vive en la tabla de ajustes (claves popup_*) porque
 * hay uno solo; lo que lo arma para las visitas es PageController::popup().
 */
class PopupController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Admin/Popup/Edit', [
            'popup' => self::values(),
            'events' => Event::visible()->ordered()->get()
                ->map(fn (Event $event) => ['id' => $event->id, 'title' => $event->title, 'date_label' => $event->date_label])
                ->values(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $current = Setting::get('popup_image_path');

        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
            'mode' => ['required', Rule::in(['once', 'until_dismissed'])],
            'kind' => ['required', Rule::in(['image', 'event'])],
            'text' => ['nullable', 'string', 'max:1000'],
            'event_id' => ['nullable', 'required_if:kind,event', 'integer', Rule::exists('events', 'id')],
            'image_path' => ['nullable', 'string'],
            'files.image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
        ], [
            'event_id.required_if' => 'Elegí el evento que va en el pop-up.',
        ]);

        // Con "Imagen y texto" tiene que quedar una imagen: la que se sube o la que ya estaba.
        $keepsImage = $request->hasFile('files.image') || ($current && filled($data['image_path'] ?? null));

        if ($data['kind'] === 'image' && ! $keepsImage) {
            return back()->withErrors(['files.image' => 'Subí la imagen del pop-up.']);
        }

        if ($request->hasFile('files.image')) {
            Setting::set('popup_image_path', ImageStorage::replace($request->file('files.image'), 'popup', $current));
        } elseif ($current && blank($data['image_path'] ?? null)) {
            ImageStorage::delete($current);
            Setting::set('popup_image_path', null);
        }

        Setting::set('popup_enabled', $request->boolean('enabled') ? '1' : null);
        Setting::set('popup_mode', $data['mode']);
        Setting::set('popup_kind', $data['kind']);
        Setting::set('popup_text', $data['text'] ?? null);
        Setting::set('popup_event_id', $data['kind'] === 'event' ? (string) $data['event_id'] : null);
        // Contenido nuevo, versión nueva: a quien había cerrado el anterior le vuelve a aparecer.
        Setting::set('popup_version', (string) now()->timestamp);

        return back()->with('success', 'Pop-up guardado.');
    }

    /** @return array<string, mixed> */
    private static function values(): array
    {
        return [
            'enabled' => Setting::get('popup_enabled') === '1',
            'mode' => Setting::get('popup_mode', 'once'),
            'kind' => Setting::get('popup_kind', 'image'),
            'text' => Setting::get('popup_text'),
            'event_id' => ($id = Setting::get('popup_event_id')) ? (int) $id : null,
            'image_path' => Setting::get('popup_image_path'),
        ];
    }
}
