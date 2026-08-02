<?php

namespace App\Support;

/**
 * Single source of truth for section types: which fields each type has,
 * how the admin form renders them and how updates are validated.
 *
 * Field primitive types:
 *  - text, textarea, url, select: scalar strings stored in content JSON.
 *  - image: storage path string in content; replacement file arrives under files[key].
 *  - links: repeater of {label, url}.
 *  - cards: repeater of {image, title, text, url}; card image files arrive under files[key][i][image].
 *  - items: repeater of plain strings.
 *  - images: repeater of storage paths; files arrive under files[key][i].
 *  - faq_picker: ordered array of faq ids stored under content[faq_ids].
 *  - occurrences: repeater de reglas de fecha para el calendario
 *    {type: weekly|date, weekday 1..7, date, from, until, start, end, label};
 *    las resuelve App\Support\Occurrences. El horario que se lee en la tarjeta
 *    sigue siendo el texto libre de `schedule`.
 */
class SectionRegistry
{
    public const TYPES = [
        'hero' => [
            'label' => 'Portada (hero)',
            'fields' => [
                ['key' => 'image', 'type' => 'image', 'label' => 'Imagen'],
                ['key' => 'heading', 'type' => 'text', 'label' => 'Título'],
                ['key' => 'subheading', 'type' => 'text', 'label' => 'Subtítulo'],
            ],
        ],
        'page_header' => [
            'label' => 'Encabezado de página',
            'fields' => [
                ['key' => 'heading', 'type' => 'text', 'label' => 'Título'],
                ['key' => 'intro', 'type' => 'textarea', 'label' => 'Introducción'],
                ['key' => 'style', 'type' => 'select', 'label' => 'Color de fondo', 'options' => ['orange' => 'Naranja', 'sky' => 'Celeste']],
            ],
        ],
        'text_block' => [
            'label' => 'Bloque de texto',
            'fields' => [
                ['key' => 'heading', 'type' => 'text', 'label' => 'Título'],
                ['key' => 'body', 'type' => 'textarea', 'label' => 'Texto'],
                ['key' => 'image', 'type' => 'image', 'label' => 'Imagen (opcional)'],
                ['key' => 'image_side', 'type' => 'select', 'label' => 'Lado de la imagen', 'options' => ['left' => 'Izquierda', 'right' => 'Derecha']],
                ['key' => 'links', 'type' => 'links', 'label' => 'Enlaces'],
            ],
        ],
        'text_image' => [
            'label' => 'Texto con imagen',
            'fields' => [
                ['key' => 'heading', 'type' => 'text', 'label' => 'Título'],
                ['key' => 'body', 'type' => 'textarea', 'label' => 'Texto'],
                ['key' => 'image', 'type' => 'image', 'label' => 'Imagen'],
                ['key' => 'image_side', 'type' => 'select', 'label' => 'Lado de la imagen', 'options' => ['left' => 'Izquierda', 'right' => 'Derecha']],
                ['key' => 'link_label', 'type' => 'text', 'label' => 'Texto del enlace'],
                ['key' => 'link_url', 'type' => 'url', 'label' => 'URL del enlace'],
            ],
        ],
        'quote' => [
            'label' => 'Testimonio / cita',
            'fields' => [
                ['key' => 'quote', 'type' => 'textarea', 'label' => 'Cita'],
                ['key' => 'author', 'type' => 'text', 'label' => 'Autor'],
            ],
        ],
        'card_grid' => [
            'label' => 'Grilla de tarjetas',
            'fields' => [
                ['key' => 'heading', 'type' => 'text', 'label' => 'Título'],
                ['key' => 'cards', 'type' => 'cards', 'label' => 'Tarjetas'],
            ],
        ],
        'bullet_list' => [
            'label' => 'Lista de ítems',
            'fields' => [
                ['key' => 'heading', 'type' => 'text', 'label' => 'Título'],
                ['key' => 'intro', 'type' => 'textarea', 'label' => 'Introducción'],
                ['key' => 'items', 'type' => 'items', 'label' => 'Ítems'],
            ],
        ],
        'gallery' => [
            'label' => 'Galería de imágenes',
            'fields' => [
                ['key' => 'heading', 'type' => 'text', 'label' => 'Título'],
                ['key' => 'images', 'type' => 'images', 'label' => 'Imágenes'],
            ],
        ],
        'class_info' => [
            'label' => 'Información de clase',
            'fields' => [
                ['key' => 'heading', 'type' => 'text', 'label' => 'Título'],
                ['key' => 'body', 'type' => 'textarea', 'label' => 'Descripción'],
                ['key' => 'schedule', 'type' => 'text', 'label' => 'Horario'],
                ['key' => 'occurrences', 'type' => 'occurrences', 'label' => 'Fechas para el calendario'],
                ['key' => 'location', 'type' => 'text', 'label' => 'Lugar'],
                ['key' => 'price', 'type' => 'text', 'label' => 'Precio'],
                ['key' => 'cta_label', 'type' => 'text', 'label' => 'Texto del botón'],
                ['key' => 'cta_url', 'type' => 'url', 'label' => 'URL del botón'],
                ['key' => 'image', 'type' => 'image', 'label' => 'Imagen'],
                ['key' => 'cycle', 'type' => 'textarea', 'label' => 'Clases del ciclo'],
            ],
        ],
        'person' => [
            'label' => 'Maestro / persona',
            'fields' => [
                ['key' => 'role', 'type' => 'text', 'label' => 'Rol (ej. Fundador, Directora)'],
                ['key' => 'name', 'type' => 'text', 'label' => 'Nombre'],
                ['key' => 'subtitle', 'type' => 'text', 'label' => 'Cargo / subtítulo'],
                ['key' => 'body', 'type' => 'textarea', 'label' => 'Biografía'],
                ['key' => 'image', 'type' => 'image', 'label' => 'Foto'],
                ['key' => 'image_side', 'type' => 'select', 'label' => 'Lado de la foto', 'options' => ['left' => 'Izquierda', 'right' => 'Derecha']],
            ],
        ],
        'figure' => [
            'label' => 'Imagen destacada',
            'fields' => [
                ['key' => 'heading', 'type' => 'text', 'label' => 'Título'],
                ['key' => 'image', 'type' => 'image', 'label' => 'Imagen'],
                ['key' => 'caption', 'type' => 'textarea', 'label' => 'Texto al pie (opcional)'],
            ],
        ],
        'pricing' => [
            'label' => 'Planes / Abonos',
            'fields' => [
                ['key' => 'heading', 'type' => 'text', 'label' => 'Título'],
                ['key' => 'intro', 'type' => 'textarea', 'label' => 'Introducción'],
                ['key' => 'plans', 'type' => 'plans', 'label' => 'Planes / tarjetas'],
                ['key' => 'footnote', 'type' => 'textarea', 'label' => 'Nota al pie'],
                ['key' => 'cta_label', 'type' => 'text', 'label' => 'Texto del botón'],
                ['key' => 'cta_url', 'type' => 'url', 'label' => 'URL del botón'],
            ],
        ],
        'event_strip' => [
            'label' => 'Eventos destacados (home)',
            'fields' => [
                ['key' => 'heading', 'type' => 'text', 'label' => 'Título'],
            ],
        ],
        'event_list' => [
            'label' => 'Listado de eventos',
            'fields' => [
                ['key' => 'heading', 'type' => 'text', 'label' => 'Título'],
                ['key' => 'empty_text', 'type' => 'text', 'label' => 'Texto si no hay eventos'],
            ],
        ],
        'event_calendar' => [
            'label' => 'Calendario mensual',
            'fields' => [
                ['key' => 'heading', 'type' => 'text', 'label' => 'Título'],
                ['key' => 'intro', 'type' => 'textarea', 'label' => 'Introducción'],
                ['key' => 'empty_text', 'type' => 'text', 'label' => 'Texto si el mes no tiene actividades'],
            ],
        ],
        'map' => [
            'label' => 'Mapa (Google Maps)',
            'fields' => [
                ['key' => 'heading', 'type' => 'text', 'label' => 'Título'],
                ['key' => 'query', 'type' => 'text', 'label' => 'Dirección o búsqueda en Google Maps'],
            ],
        ],
        'faq' => [
            'label' => 'Preguntas frecuentes',
            'fields' => [
                ['key' => 'heading', 'type' => 'text', 'label' => 'Título'],
                ['key' => 'faq_ids', 'type' => 'faq_picker', 'label' => 'Preguntas a mostrar'],
            ],
        ],
    ];

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function fields(string $type): array
    {
        return self::TYPES[$type]['fields'] ?? [];
    }

    public static function label(string $type): string
    {
        return self::TYPES[$type]['label'] ?? $type;
    }

    /**
     * Every storage path stored in a section's content, sin importar el tipo:
     * el campo simple, las galerías y las imágenes de las tarjetas.
     *
     * @param  array<string, mixed>  $content
     * @return array<int, string>
     */
    public static function imagePaths(string $type, array $content): array
    {
        $paths = [];

        foreach (self::fields($type) as $field) {
            $value = $content[$field['key']] ?? null;

            switch ($field['type']) {
                case 'image':
                    $paths[] = $value;
                    break;

                case 'images':
                    $paths = [...$paths, ...array_values((array) $value)];
                    break;

                case 'cards':
                    $paths = [...$paths, ...array_column((array) $value, 'image')];
                    break;
            }
        }

        return array_values(array_filter($paths, fn ($path) => is_string($path) && $path !== ''));
    }

    /**
     * Validation rules for the `content` payload and the parallel `files` payload.
     *
     * @return array<string, mixed>
     */
    public static function rules(string $type): array
    {
        $rules = [
            'content' => ['nullable', 'array'],
            'files' => ['nullable', 'array'],
        ];

        $imageRule = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'];

        foreach (self::fields($type) as $field) {
            $key = $field['key'];

            match ($field['type']) {
                'text' => $rules["content.$key"] = ['nullable', 'string', 'max:255'],
                'textarea' => $rules["content.$key"] = ['nullable', 'string', 'max:10000'],
                'url' => $rules["content.$key"] = ['nullable', 'string', 'max:500'],
                'select' => $rules["content.$key"] = ['nullable', 'string', 'in:'.implode(',', array_keys($field['options'] ?? []))],
                'image' => [
                    $rules["content.$key"] = ['nullable', 'string', 'max:500'],
                    $rules["files.$key"] = $imageRule,
                ],
                'links' => [
                    $rules["content.$key"] = ['nullable', 'array', 'max:12'],
                    $rules["content.$key.*.label"] = ['nullable', 'string', 'max:255'],
                    $rules["content.$key.*.url"] = ['nullable', 'string', 'max:500'],
                ],
                'cards' => [
                    $rules["content.$key"] = ['nullable', 'array', 'max:12'],
                    $rules["content.$key.*.image"] = ['nullable', 'string', 'max:500'],
                    $rules["content.$key.*.title"] = ['nullable', 'string', 'max:255'],
                    $rules["content.$key.*.text"] = ['nullable', 'string', 'max:2000'],
                    $rules["content.$key.*.url"] = ['nullable', 'string', 'max:500'],
                    $rules["files.$key.*.image"] = $imageRule,
                ],
                'items' => [
                    $rules["content.$key"] = ['nullable', 'array', 'max:24'],
                    $rules["content.$key.*"] = ['nullable', 'string', 'max:500'],
                ],
                'plans' => [
                    $rules["content.$key"] = ['nullable', 'array', 'max:6'],
                    $rules["content.$key.*.name"] = ['nullable', 'string', 'max:120'],
                    $rules["content.$key.*.price"] = ['nullable', 'string', 'max:60'],
                    $rules["content.$key.*.period"] = ['nullable', 'string', 'max:60'],
                    $rules["content.$key.*.features"] = ['nullable', 'string', 'max:2000'],
                    $rules["content.$key.*.note"] = ['nullable', 'string', 'max:255'],
                    $rules["content.$key.*.highlighted"] = ['nullable', 'boolean'],
                ],
                'images' => [
                    $rules["content.$key"] = ['nullable', 'array', 'max:24'],
                    $rules["content.$key.*"] = ['nullable', 'string', 'max:500'],
                    $rules["files.$key.*"] = $imageRule,
                ],
                'faq_picker' => [
                    $rules["content.$key"] = ['nullable', 'array'],
                    $rules["content.$key.*"] = ['integer', 'exists:faqs,id'],
                ],
                // Las ocho claves tienen que estar: Laravel excluye de validated()
                // las sub-claves sin regla, así que la que falte se borraría al guardar.
                'occurrences' => [
                    $rules["content.$key"] = ['nullable', 'array', 'max:24'],
                    $rules["content.$key.*.type"] = ['nullable', 'string', 'in:weekly,date'],
                    $rules["content.$key.*.weekday"] = ['nullable', 'integer', 'between:1,7', "required_if:content.$key.*.type,weekly"],
                    $rules["content.$key.*.date"] = ['nullable', 'date_format:Y-m-d', "required_if:content.$key.*.type,date"],
                    $rules["content.$key.*.from"] = ['nullable', 'date_format:Y-m-d'],
                    $rules["content.$key.*.until"] = ['nullable', 'date_format:Y-m-d'],
                    $rules["content.$key.*.start"] = ['nullable', 'date_format:H:i'],
                    $rules["content.$key.*.end"] = ['nullable', 'date_format:H:i'],
                    $rules["content.$key.*.label"] = ['nullable', 'string', 'max:120'],
                ],
                default => null,
            };
        }

        return $rules;
    }
}
