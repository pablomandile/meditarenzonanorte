<?php

namespace App\Support;

use App\Models\Event;
use App\Models\Section;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

/**
 * Las imágenes que hay en el disco público y dónde se usa cada una. Es la fuente
 * única del selector "Elegir de galería" (JSON, ver MediaController) y de la
 * pantalla Galería del panel, que además necesita saber qué se puede borrar.
 */
class MediaLibrary
{
    private const EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'];

    /**
     * Todas las imágenes, las subidas primero y más recientes arriba (es lo que
     * se busca reusar) y las sembradas al final por nombre.
     *
     * $withHash lee cada archivo completo para hashearlo, y solo lo necesita
     * collapse(): con 56 imágenes son 26 MB de disco, que triplican el tiempo
     * del listado. La pantalla Galería no colapsa nada, así que no lo pide.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function images(bool $withHash = false): array
    {
        $disk = Storage::disk('public');
        $usage = self::usage();
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
                    'hash' => $withHash ? md5($disk->get($path)) : null,
                    'used_by' => $usage[$path] ?? [],
                    'deletable' => ImageStorage::isDeletable($path),
                ];
            }
        }

        usort($images, function (array $a, array $b) {
            if ($a['seeded'] !== $b['seeded']) {
                return $a['seeded'] ? 1 : -1;
            }

            return $a['seeded']
                ? strcmp($a['name'], $b['name'])
                : $b['modified'] <=> $a['modified'];
        });

        return $images;
    }

    /**
     * Una entrada por imagen distinta; hay que pasarle images(withHash: true).
     * Elegir del selector copia el archivo
     * (ImageStorage::adopt), así que la misma foto termina guardada varias veces
     * y el selector la mostraría repetida. Gana la sembrada —tiene nombre real y
     * el seeder siempre la restaura— y si no, la subida más vieja, que es la que
     * probablemente ya esté en uso.
     *
     * @param  array<int, array<string, mixed>>  $images
     * @return array<int, array<string, mixed>>
     */
    public static function collapse(array $images): array
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

        return array_values($unique);
    }

    /**
     * Ruta => lugares que la están usando, en texto para mostrar al dueño.
     *
     * @return array<string, array<int, string>>
     */
    public static function usage(): array
    {
        $usage = [];

        $add = function (?string $path, string $place) use (&$usage) {
            if (is_string($path) && $path !== '') {
                $usage[$path][] = $place;
            }
        };

        foreach (Section::with('page')->get() as $section) {
            $place = ($section->page->title ?? 'Página').' · '.$section->key;

            foreach (SectionRegistry::imagePaths($section->type, $section->content ?? []) as $path) {
                $add($path, $place);
            }
        }

        foreach (Event::all() as $event) {
            $add($event->image_path, 'Evento: '.$event->title);
        }

        $add(Setting::get('logo_path'), 'Ajustes: logo del menú');
        $add(Setting::get('footer_logo_path'), 'Ajustes: logo del pie');

        foreach (json_decode(Setting::get('footer_resources', '[]'), true) ?: [] as $card) {
            $add($card['image'] ?? null, 'Ajustes: recursos del pie');
        }

        return array_map('array_values', array_map('array_unique', $usage));
    }
}
