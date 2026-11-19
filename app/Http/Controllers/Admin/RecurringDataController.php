<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Price;
use App\Models\Section;
use App\Models\Teacher;
use App\Models\Venue;
use App\Support\ImageStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Datos recurrentes": las listas de maestr@s, lugares y precios que después se
 * eligen en los campos "Maestr@", "Lugar" y "Precio" de las fichas de clase.
 * Se agregan y se borran; a los maestr@s además se les carga descripción y foto
 * (se ven en el sitio, ver PageController::content). El orden es alfabético.
 */
class RecurringDataController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/RecurringData/Index', [
            'teachers' => Teacher::ordered()->get(['id', 'name', 'description', 'photo']),
            'venues' => Venue::ordered()->get(['id', 'name']),
            'prices' => Price::ordered()->get(['id', 'name']),
        ]);
    }

    public function storeTeacher(Request $request): RedirectResponse
    {
        Teacher::create($this->validateName($request, 'teachers'));

        return back()->with('success', 'Maestr@ agregad@.');
    }

    /**
     * Nombre, descripción y foto. La foto se sube (file) o se elige de la
     * galería (photo, una ruta), igual que en las secciones. Si cambia el nombre,
     * se cambia también en las fichas de clase que lo escriben: el vínculo entre
     * la ficha y su maestr@ es ese texto.
     */
    public function updateTeacher(Request $request, Teacher $teacher): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('teachers', 'name')->ignore($teacher)],
            'description' => ['nullable', 'string', 'max:5000'],
            'photo' => ['nullable', 'string', 'max:500'],
            'file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
        ], [
            'name.unique' => 'Ese nombre ya está en la lista.',
        ]);

        $old = $teacher->photo;

        if ($request->hasFile('file')) {
            $photo = ImageStorage::replace($request->file('file'), 'teachers', $old);
        } else {
            $photo = ImageStorage::adopt($data['photo'] ?? null, array_filter([$old]), 'teachers');

            if ($old !== null && $photo !== $old) {
                ImageStorage::delete($old);
            }
        }

        $previousName = $teacher->name;

        DB::transaction(function () use ($teacher, $data, $photo, $previousName) {
            $teacher->update([
                'name' => trim($data['name']),
                'description' => filled($data['description'] ?? null) ? $data['description'] : null,
                'photo' => $photo,
            ]);

            if ($previousName !== $teacher->name) {
                self::renameInClassCards($previousName, $teacher->name);
            }
        });

        return back()->with('success', 'Maestr@ guardad@.');
    }

    public function destroyTeacher(Teacher $teacher): RedirectResponse
    {
        // Las secciones "Maestro / persona" que lo elegían vuelven a mostrar lo
        // que tenían cargado ellas (PageController::content).
        ImageStorage::delete($teacher->photo);
        $teacher->delete();

        return back()->with('success', 'Maestr@ eliminad@.');
    }

    public function storeVenue(Request $request): RedirectResponse
    {
        Venue::create($this->validateName($request, 'venues'));

        return back()->with('success', 'Lugar agregado.');
    }

    public function destroyVenue(Venue $venue): RedirectResponse
    {
        $venue->delete();

        return back()->with('success', 'Lugar eliminado.');
    }

    public function storePrice(Request $request): RedirectResponse
    {
        Price::create($this->validateName($request, 'prices'));

        return back()->with('success', 'Precio agregado.');
    }

    public function destroyPrice(Price $price): RedirectResponse
    {
        $price->delete();

        return back()->with('success', 'Precio eliminado.');
    }

    /** Cambia el nombre en el campo "Maestr@" de las fichas que lo nombran. */
    private static function renameInClassCards(string $from, string $to): void
    {
        $key = Teacher::key($from);

        Section::where('type', 'class_info')->get()->each(function (Section $section) use ($key, $to) {
            $content = $section->content ?? [];
            $names = array_map('trim', explode(',', (string) ($content['teachers'] ?? '')));

            if (! in_array($key, array_map([Teacher::class, 'key'], $names), true)) {
                return;
            }

            $content['teachers'] = implode(', ', array_map(
                fn (string $name) => Teacher::key($name) === $key ? $to : $name,
                array_filter($names, fn ($name) => $name !== ''),
            ));

            $section->update(['content' => $content]);
        });
    }

    /**
     * @return array{name: string}
     */
    private function validateName(Request $request, string $table): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique($table, 'name')],
        ], [
            'name.unique' => 'Ese nombre ya está en la lista.',
        ]);
    }
}
