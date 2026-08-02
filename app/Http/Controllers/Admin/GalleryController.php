<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ImageStorage;
use App\Support\MediaLibrary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GalleryController extends Controller
{
    /**
     * Todas las imágenes del sitio con dónde se usa cada una. A diferencia del
     * selector "Elegir de galería" no se colapsan las copias de bytes idénticos:
     * acá se administran archivos, y una copia sin usar se puede borrar.
     */
    public function index(): Response
    {
        $images = array_map(fn (array $image) => [
            'path' => $image['path'],
            'url' => $image['url'],
            'name' => $image['name'],
            'seeded' => $image['seeded'],
            'size' => $image['size'],
            'used_by' => $image['used_by'],
            'deletable' => $image['deletable'] && $image['used_by'] === [],
        ], MediaLibrary::images());

        return Inertia::render('Admin/Gallery/Index', ['images' => $images]);
    }

    /**
     * Borra una imagen del disco. Nunca borra una que esté en uso: la pantalla
     * ya deshabilita el botón, y esto lo vuelve a comprobar contra la base por
     * si la vista quedó vieja (otra pestaña, o alguien editando en paralelo).
     */
    public function destroy(Request $request): RedirectResponse
    {
        $path = $request->validate(['path' => ['required', 'string', 'max:500']])['path'];

        abort_unless(ImageStorage::isPickable($path), 403);

        if (! ImageStorage::isDeletable($path)) {
            return back()->withErrors([
                'image' => 'Esa imagen viene del contenido sembrado del sitio y no se borra: el seeder la vuelve a poner.',
            ]);
        }

        $places = MediaLibrary::usage()[$path] ?? [];

        if ($places !== []) {
            return back()->withErrors([
                'image' => 'No se puede borrar «'.basename($path).'»: la está usando '.implode(' · ', $places).'.',
            ]);
        }

        ImageStorage::delete($path);

        return back()->with('success', 'Imagen eliminada.');
    }
}
