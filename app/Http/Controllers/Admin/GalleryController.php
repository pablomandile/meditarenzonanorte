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
            'standalone' => str_starts_with($image['path'], ImageStorage::STANDALONE_FOLDER.'/'),
        ], MediaLibrary::images());

        return Inertia::render('Admin/Gallery/Index', ['images' => $images]);
    }

    /**
     * Sube imágenes sueltas, que no pertenecen a ninguna sección, evento ni
     * ajuste: quedan hosteadas en el sitio para usarlas afuera por su enlace
     * (una publicidad, un posteo) y también aparecen en "Elegir de galería".
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'images' => ['required', 'array', 'min:1', 'max:20'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
        ], [
            'images.*.image' => 'Uno de los archivos no es una imagen.',
            'images.*.mimes' => 'Las imágenes tienen que ser JPG, PNG, WebP o GIF.',
            'images.*.max' => 'Cada imagen puede pesar hasta 4 MB.',
        ]);

        $files = $request->file('images');

        foreach ($files as $file) {
            ImageStorage::storeStandalone($file);
        }

        return back()->with('success', count($files) === 1 ? 'Imagen subida.' : count($files).' imágenes subidas.');
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
