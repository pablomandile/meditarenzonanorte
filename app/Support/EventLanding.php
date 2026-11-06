<?php

namespace App\Support;

use App\Models\Event;
use App\Models\Page;
use App\Models\Section;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * La landing de los eventos con "Link interno".
 *
 * Hay una plantilla genérica (una Page kind=event_template) que se edita desde el
 * panel, y cada evento recibe una COPIA independiente al pasar a Link interno:
 * cambiar la plantilla después no toca las landings ya creadas.
 */
class EventLanding
{
    /** La carpeta de las imágenes de muestra, dentro de seeders/images y de seed/. */
    private const IMAGES = 'landing-evento';

    /**
     * La plantilla. Si todavía no existe (un deploy sin correr el seeder, o los
     * tests) se crea con el contenido de muestra, así crear una landing nunca falla.
     */
    public static function template(): Page
    {
        return Page::where('kind', Page::KIND_EVENT_TEMPLATE)->first() ?? self::seedTemplate();
    }

    /**
     * Crea la plantilla con el contenido de database/seeders/data/event-landing.php.
     * Repetible: si ya existe no la toca, para no pisar lo que el dueño editó.
     */
    public static function seedTemplate(): Page
    {
        $existing = Page::where('kind', Page::KIND_EVENT_TEMPLATE)->first();

        if ($existing) {
            return $existing;
        }

        self::copySeedImages();

        $data = require database_path('seeders/data/event-landing.php');
        $replacements = [
            '{site_name}' => (string) Setting::get('site_name', ''),
            '{address}' => (string) Setting::get('address', ''),
        ];

        return DB::transaction(function () use ($data, $replacements) {
            $page = Page::create([
                'slug' => Page::EVENT_TEMPLATE_SLUG,
                'kind' => Page::KIND_EVENT_TEMPLATE,
                'title' => $data['title'],
                'menu_label' => null,
                'menu_order' => 0,
                'visible' => false,
            ]);

            foreach ($data['sections'] as $position => $section) {
                $content = $section['content'];

                foreach (SectionRegistry::fields($section['type']) as $field) {
                    $value = $content[$field['key']] ?? null;

                    if ($field['type'] === 'image' && is_string($value)) {
                        $content[$field['key']] = 'seed/'.$value;
                    } elseif (is_string($value)) {
                        $content[$field['key']] = strtr($value, $replacements) ?: null;
                    }
                }

                $page->sections()->create([
                    'type' => $section['type'],
                    'key' => $section['key'],
                    'position' => $position + 1,
                    'visible' => true,
                    'content' => $content,
                ]);
            }

            return $page;
        });
    }

    private static function copySeedImages(): void
    {
        $dir = database_path('seeders/images/'.self::IMAGES);

        if (! File::isDirectory($dir)) {
            return;
        }

        foreach (File::files($dir) as $file) {
            $target = 'seed/'.self::IMAGES.'/'.$file->getFilename();

            if (! Storage::disk('public')->exists($target)) {
                Storage::disk('public')->put($target, $file->getContents());
            }
        }
    }

    /**
     * La landing del evento, copiada de la plantilla: mismas secciones, mismo
     * orden y mismo estado visible/oculto, con sus propias copias de las imágenes
     * subidas. Lo que el evento ya tiene cargado (título, fecha, lugar, precio)
     * se completa solo para no tipearlo dos veces.
     */
    public static function createFor(Event $event): Page
    {
        if ($existing = $event->landing()->first()) {
            return $existing;
        }

        $template = self::template();

        return DB::transaction(function () use ($event, $template) {
            $page = Page::create([
                'slug' => self::uniqueSlug($event->title),
                'kind' => Page::KIND_EVENT_LANDING,
                'event_id' => $event->id,
                'title' => $event->title,
                'menu_label' => null,
                'menu_order' => 0,
                'visible' => true,
                'meta_description' => filled($event->description) ? Str::limit(trim(preg_replace('/\s+/', ' ', $event->description)), 157) : null,
            ]);

            foreach ($template->sections()->get() as $section) {
                $content = SectionImages::duplicate($section->type, $section->content ?? []);

                Section::create([
                    'page_id' => $page->id,
                    'type' => $section->type,
                    'key' => $section->key,
                    'position' => $section->position,
                    'visible' => $section->visible,
                    'content' => self::prefill($section->type, $content, $event),
                ]);
            }

            return $page;
        });
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    private static function prefill(string $type, array $content, Event $event): array
    {
        $date = $event->date_label;
        $location = filled($event->location) ? $event->location : null;

        switch ($type) {
            case 'landing_hero':
                $content['heading'] = $event->title;
                $content['heading_accent'] = null;
                $content['date'] = $date ?? $content['date'] ?? null;
                $content['place'] = $location ?? $content['place'] ?? null;
                break;

            case 'landing_booking':
                $content['intro'] = implode(' · ', array_filter([$event->title, $date]));
                if (filled($event->price)) {
                    $content['prices'] = [['note' => null, 'amount' => $event->price]];
                }
                if ($location) {
                    $content['address'] = $location;
                }
                break;
        }

        return $content;
    }

    /** "Retiro de verano" → "retiro-de-verano", o "-2", "-3"… si ya hay una página así. */
    private static function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'evento';
        $slug = $base;
        $n = 1;

        while (Page::where('slug', $slug)->exists()) {
            $slug = $base.'-'.++$n;
        }

        return $slug;
    }

    /** Borra la landing con sus secciones y las imágenes que se subieron para ella. */
    public static function delete(Page $landing): void
    {
        DB::transaction(function () use ($landing) {
            foreach ($landing->sections()->get() as $section) {
                SectionImages::delete($section->type, $section->content ?? []);
                $section->delete();
            }

            $landing->delete();
        });
    }
}
