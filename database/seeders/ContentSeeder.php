<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Section;
use App\Models\Setting;
use App\Support\SectionRegistry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Seeds the site with the content cloned from https://meditarenrosario.org/
 * (data files under database/seeders/data, images under database/seeders/images).
 * Seed images are copied to storage/app/public/seed and referenced as seed/<path>.
 */
class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->copyImages();

        $faqIds = $this->seedFaqs();
        $this->seedEvents();
        $this->seedSettings();
        $this->seedPages($faqIds);
    }

    private function copyImages(): void
    {
        $imagesDir = database_path('seeders/images');

        foreach (File::allFiles($imagesDir) as $file) {
            $relative = str_replace('\\', '/', $file->getRelativePathname());
            Storage::disk('public')->put('seed/'.$relative, $file->getContents());
        }
    }

    private function seedImagePath(?string $path): ?string
    {
        return $path ? 'seed/'.$path : null;
    }

    /**
     * @return array<int, int> data index => faq id
     */
    private function seedFaqs(): array
    {
        $ids = [];

        foreach (require database_path('seeders/data/faqs.php') as $index => $faq) {
            $model = Faq::updateOrCreate(
                ['question' => $faq['question']],
                ['answer' => $faq['answer'], 'position' => $index + 1, 'visible' => true],
            );

            $ids[$index] = $model->id;
        }

        return $ids;
    }

    private function seedEvents(): void
    {
        foreach (require database_path('seeders/data/events.php') as $event) {
            $image = $event['image'] ?? null;
            unset($event['image']);

            Event::updateOrCreate(
                ['title' => $event['title']],
                [...$event, 'image_path' => $this->seedImagePath($image), 'visible' => true],
            );
        }
    }

    private function seedSettings(): void
    {
        $settings = require database_path('seeders/data/settings.php');

        $logo = $settings['logo'] ?? null;
        unset($settings['logo']);

        foreach ($settings as $key => $value) {
            Setting::set($key, $value);
        }

        Setting::set('logo_path', $this->seedImagePath($logo));
    }

    /**
     * @param  array<int, int>  $faqIds
     */
    private function seedPages(array $faqIds): void
    {
        foreach (require database_path('seeders/data/content.php') as $slug => $pageData) {
            $this->upsertPage($slug, $pageData, $faqIds);
        }
    }

    /**
     * Seed (or refresh) a single page by slug without touching the others.
     * Used by targeted seeders (e.g. AbonosSeeder) so a production deploy can
     * add one page without overwriting the owner's edits on the rest.
     */
    public function seedSinglePage(string $slug): void
    {
        $this->copyImages();

        $pages = require database_path('seeders/data/content.php');

        if (! isset($pages[$slug])) {
            return;
        }

        $faqIds = Faq::orderBy('position')->pluck('id')->values()->all();

        $this->upsertPage($slug, $pages[$slug], $faqIds);
    }

    /**
     * Adds one section from the data file to a page that does not have it yet.
     * Unlike seedSinglePage() it never rewrites what the owner already edited:
     * the block is inserted right below the section that precedes it in the data
     * file, the rest shifts down, and running it again does nothing.
     *
     * $visible = false para plantillas: el bloque entra oculto y la página
     * pública no lo muestra hasta que el dueño termina de completarlo, igual que
     * hace el clonado de secciones del panel.
     */
    public function seedMissingSection(string $slug, string $key, bool $visible = true): void
    {
        $pages = require database_path('seeders/data/content.php');
        $page = Page::where('slug', $slug)->first();

        if (! $page || ! isset($pages[$slug])) {
            return;
        }

        $sections = $pages[$slug]['sections'];
        $index = collect($sections)->search(fn ($section) => $section['key'] === $key);

        if ($index === false || Section::where('page_id', $page->id)->where('key', $key)->exists()) {
            return;
        }

        $this->copyImages();

        $previousKey = $sections[$index - 1]['key'] ?? null;
        $previous = $previousKey
            ? Section::where('page_id', $page->id)->where('key', $previousKey)->first()
            : null;

        $position = $previous ? $previous->position + 1 : 1;

        $content = $this->prepareContent(
            $sections[$index]['type'],
            $sections[$index]['content'],
            Faq::orderBy('position')->pluck('id')->values()->all(),
        );

        DB::transaction(function () use ($page, $sections, $index, $key, $position, $content, $visible) {
            Section::where('page_id', $page->id)
                ->where('position', '>=', $position)
                ->increment('position');

            Section::create([
                'page_id' => $page->id,
                'type' => $sections[$index]['type'],
                'key' => $key,
                'position' => $position,
                'visible' => $visible,
                'content' => $content,
            ]);
        });
    }

    /**
     * Agrega al pie una tarjeta de recursos del archivo de datos que todavía no esté.
     *
     * Los recursos del pie no son una sección sino el ajuste footer_resources, así
     * que seedMissingSection() no sirve para esto. Igual que aquel, nunca reescribe
     * lo que el dueño ya editó: si ya hay una tarjeta con esa url no hace nada, las
     * que están quedan como están y la nueva va al final.
     */
    public function seedMissingFooterResource(string $url): void
    {
        $cards = json_decode(Setting::get('footer_resources', '[]'), true) ?: [];

        foreach ($cards as $card) {
            if (($card['url'] ?? null) === $url) {
                return;
            }
        }

        $pages = require database_path('seeders/data/content.php');
        $recursos = collect($pages['home']['sections'])->firstWhere('key', 'recursos');
        $nueva = collect($recursos['content']['cards'] ?? [])->firstWhere('url', $url);

        if (! $nueva) {
            return;
        }

        $this->copyImages();

        $this->saveFooterResources([
            ...$cards,
            [...$nueva, 'image' => $this->seedImagePath($nueva['image'] ?? null)],
        ]);
    }

    /**
     * @param  array<int, int>  $faqIds
     */
    private function upsertPage(string $slug, array $pageData, array $faqIds): void
    {
        $page = Page::updateOrCreate(
            ['slug' => $slug],
            [
                'title' => $pageData['title'],
                'menu_label' => $pageData['menu_label'],
                'menu_order' => $pageData['menu_order'],
                'meta_description' => $pageData['meta_description'] ?? null,
                'visible' => true,
            ],
        );

        $position = 0;

        foreach ($pageData['sections'] as $sectionData) {
            // The resources block lives in the global footer, not as a home section.
            if ($slug === 'home' && $sectionData['key'] === 'recursos') {
                $this->seedFooterResources($sectionData['content']['cards'] ?? []);

                continue;
            }

            $content = $this->prepareContent($sectionData['type'], $sectionData['content'], $faqIds);

            Section::updateOrCreate(
                ['page_id' => $page->id, 'key' => $sectionData['key']],
                [
                    'type' => $sectionData['type'],
                    'position' => ++$position,
                    'visible' => true,
                    'content' => $content,
                ],
            );
        }
    }

    private function seedFooterResources(array $cards): void
    {
        $this->saveFooterResources(array_map(fn ($card) => [
            ...$card,
            'image' => $this->seedImagePath($card['image'] ?? null),
        ], $cards));
    }

    /** Espera las tarjetas con la imagen ya resuelta a su ruta de storage. */
    private function saveFooterResources(array $cards): void
    {
        Setting::set('footer_resources', json_encode($cards, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function prepareContent(string $type, array $content, array $faqIds): array
    {
        foreach (SectionRegistry::fields($type) as $field) {
            $key = $field['key'];

            switch ($field['type']) {
                case 'image':
                    $content[$key] = $this->seedImagePath($content[$key] ?? null);
                    break;

                case 'cards':
                    $content[$key] = array_map(fn ($card) => [
                        ...$card,
                        'image' => $this->seedImagePath($card['image'] ?? null),
                    ], $content[$key] ?? []);
                    break;

                case 'images':
                    $content[$key] = array_map(
                        fn ($path) => $this->seedImagePath($path),
                        $content[$key] ?? [],
                    );
                    break;

                case 'faq_picker':
                    $content[$key] = array_values(array_map(
                        fn ($ref) => $faqIds[$ref],
                        $content['faq_refs'] ?? $content[$key] ?? [],
                    ));
                    unset($content['faq_refs']);
                    break;
            }
        }

        return $content;
    }
}
