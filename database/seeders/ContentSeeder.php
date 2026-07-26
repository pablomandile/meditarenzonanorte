<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Section;
use App\Models\Setting;
use App\Support\SectionRegistry;
use Illuminate\Database\Seeder;
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
    }

    private function seedFooterResources(array $cards): void
    {
        $cards = array_map(fn ($card) => [
            ...$card,
            'image' => $this->seedImagePath($card['image'] ?? null),
        ], $cards);

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
