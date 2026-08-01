<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ImageStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    private const EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'];

    /**
     * Every image already on the site, so a form can reuse one instead of
     * uploading it again. Fed to the gallery dialog of the image fields.
     *
     * Files with identical bytes collapse into a single entry: picking from the
     * gallery copies the file (see ImageStorage::adopt), so the same photo ends
     * up stored several times and the grid would show it repeated.
     */
    public function index(): JsonResponse
    {
        $disk = Storage::disk('public');
        $images = [];

        foreach (ImageStorage::PICKABLE_PREFIXES as $prefix) {
            $folder = rtrim($prefix, '/');

            foreach ($disk->allFiles($folder) as $path) {
                if (! in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), self::EXTENSIONS, true)) {
                    continue;
                }

                $images[] = [
                    'path' => $path,
                    'url' => $disk->url($path),
                    'name' => basename($path),
                    'seeded' => $folder === 'seed',
                    'size' => $disk->size($path),
                    'modified' => $disk->lastModified($path),
                    'hash' => md5($disk->get($path)),
                ];
            }
        }

        return response()->json(['images' => $this->collapse($images)]);
    }

    /**
     * Keeps one entry per distinct image: the seeded copy wins (it has a real
     * name and the seeder always restores it), otherwise the oldest upload,
     * which is the one other sections are most likely already pointing at.
     *
     * @param  array<int, array<string, mixed>>  $images
     * @return array<int, array<string, mixed>>
     */
    private function collapse(array $images): array
    {
        $unique = [];

        foreach ($images as $image) {
            $current = $unique[$image['hash']] ?? null;

            if ($current === null
                || (! $current['seeded'] && $image['seeded'])
                || (! $current['seeded'] && $image['modified'] < $current['modified'])) {
                $unique[$image['hash']] = $image;
            }
        }

        $images = array_values($unique);

        // Lo subido primero y lo más reciente arriba: es lo que se busca reusar.
        // Las imágenes del sitio (seed) van al final, ordenadas por nombre.
        usort($images, function (array $a, array $b) {
            if ($a['seeded'] !== $b['seeded']) {
                return $a['seeded'] ? 1 : -1;
            }

            return $a['seeded']
                ? strcmp($a['name'], $b['name'])
                : $b['modified'] <=> $a['modified'];
        });

        return array_map(fn (array $image) => [
            'path' => $image['path'],
            'url' => $image['url'],
            'name' => $image['name'],
            'seeded' => $image['seeded'],
            'size' => $image['size'],
        ], $images);
    }
}
