<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\MediaLibrary;
use Illuminate\Http\JsonResponse;

class MediaController extends Controller
{
    /**
     * Las imágenes ya cargadas al sitio, para que un formulario pueda reusar una
     * en lugar de volver a subirla. Alimenta el diálogo "Elegir de galería" de
     * los campos de imagen; la pantalla Galería usa GalleryController.
     */
    public function index(): JsonResponse
    {
        $images = array_map(
            fn (array $image) => [
                'path' => $image['path'],
                'url' => $image['url'],
                'name' => $image['name'],
                'seeded' => $image['seeded'],
                'size' => $image['size'],
            ],
            MediaLibrary::collapse(MediaLibrary::images(withHash: true)),
        );

        return response()->json(['images' => $images]);
    }
}
