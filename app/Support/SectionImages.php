<?php

namespace App\Support;

/**
 * Copia los archivos de una sección para que la copia sea dueña de los suyos:
 * reemplazar la imagen en una no tiene que borrar el archivo que la otra sigue
 * usando. Lo usan el "Clonar" del panel y la creación de la landing de un evento
 * desde la plantilla.
 */
class SectionImages
{
    /**
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    public static function duplicate(string $type, array $content): array
    {
        foreach (SectionRegistry::fields($type) as $field) {
            $key = $field['key'];

            switch ($field['type']) {
                case 'image':
                    if (isset($content[$key])) {
                        $content[$key] = ImageStorage::duplicate($content[$key]);
                    }
                    break;

                case 'images':
                    foreach ($content[$key] ?? [] as $i => $path) {
                        $content[$key][$i] = ImageStorage::duplicate($path);
                    }
                    break;

                case 'cards':
                    foreach ($content[$key] ?? [] as $i => $card) {
                        if (isset($card['image'])) {
                            $content[$key][$i]['image'] = ImageStorage::duplicate($card['image']);
                        }
                    }
                    break;
            }
        }

        return $content;
    }

    /** Borra los archivos subidos de la sección (los sembrados, seed/…, nunca). */
    public static function delete(string $type, array $content): void
    {
        foreach (SectionRegistry::imagePaths($type, $content) as $path) {
            ImageStorage::delete($path);
        }
    }
}
