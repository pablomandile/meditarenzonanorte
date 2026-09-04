<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Section;
use App\Models\Setting;
use App\Models\User;
use App\Support\EventCalendar;
use App\Support\SiteMeta;
use Carbon\Carbon;
use Database\Seeders\AppKadampaSeeder;
use Database\Seeders\CalendarioFechasSeeder;
use Database\Seeders\CalendarioSeeder;
use Database\Seeders\ContentSeeder;
use Database\Seeders\VoluntariadoPortadaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class SiteContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->seed(ContentSeeder::class);
    }

    private function admin(): User
    {
        return User::factory()->create();
    }

    public function test_all_public_pages_render_with_their_sections(): void
    {
        // Fragmento sin acentos y propio del contenido de la home: Inertia escapa
        // los acentos en el JSON y el <title> depende de APP_NAME (distinto en CI).
        $this->get('/')->assertOk()->assertSee('Actividades semanales');

        foreach (['clases-semanales', 'eventos-especiales', 'gratis', 'quienes-somos', 'voluntariado', 'calendario'] as $slug) {
            $this->get("/$slug")->assertOk();
        }

        // Inertia serializes props with unicode escapes, so assertions use accent-free fragments.
        $this->get('/clases-semanales')->assertSee('de 19 a 20.15 hs');
        $this->get('/quienes-somos')->assertSee('El budismo kadampa es una tradici');
    }

    public function test_hidden_page_returns_404_and_leaves_the_menu(): void
    {
        $page = Page::where('slug', 'voluntariado')->firstOrFail();

        $this->assertContains('voluntariado', $this->navSlugs());

        $page->update(['visible' => false]);

        $this->get('/voluntariado')->assertNotFound();
        $this->assertNotContains('voluntariado', $this->navSlugs());
    }

    /**
     * Slugs of the nav shared with every page, in menu order.
     *
     * @return array<int, string>
     */
    private function navSlugs(): array
    {
        $nav = [];

        $this->get('/')->assertInertia(function (AssertableInertia $page) use (&$nav) {
            $nav = collect($page->toArray()['props']['nav'])->pluck('slug')->all();
        });

        return $nav;
    }

    public function test_admin_can_hide_a_page_from_the_admin_list(): void
    {
        $admin = $this->admin();
        $page = Page::where('slug', 'abonos')->firstOrFail();

        $this->actingAs($admin)->patch("/admin/pages/{$page->id}/toggle")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertFalse($page->fresh()->visible);
        $this->assertNotContains('abonos', $this->navSlugs());
        $this->get('/abonos')->assertNotFound();

        $this->actingAs($admin)->patch("/admin/pages/{$page->id}/toggle");

        $this->assertTrue($page->fresh()->visible);
        $this->assertContains('abonos', $this->navSlugs());
    }

    public function test_admin_can_reorder_pages_and_the_nav_follows(): void
    {
        $admin = $this->admin();
        $clases = Page::where('slug', 'clases-semanales')->firstOrFail();
        $eventos = Page::where('slug', 'eventos-especiales')->firstOrFail();

        $this->assertSame(['clases-semanales', 'eventos-especiales'], array_slice($this->navSlugs(), 0, 2));

        $this->actingAs($admin)->patch("/admin/pages/{$eventos->id}/move", ['direction' => 'up'])
            ->assertRedirect();

        $this->assertEquals($clases->menu_order, $eventos->fresh()->menu_order);
        $this->assertEquals($eventos->menu_order, $clases->fresh()->menu_order);
        $this->assertSame(['eventos-especiales', 'clases-semanales'], array_slice($this->navSlugs(), 0, 2));

        // The first page in the menu cannot climb any further.
        $this->actingAs($admin)->patch("/admin/pages/{$eventos->id}/move", ['direction' => 'up']);

        $this->assertSame(['eventos-especiales', 'clases-semanales'], array_slice($this->navSlugs(), 0, 2));
    }

    public function test_home_page_cannot_be_hidden_or_moved(): void
    {
        $admin = $this->admin();
        $home = Page::where('slug', 'home')->firstOrFail();

        $this->actingAs($admin)->patch("/admin/pages/{$home->id}/toggle")->assertForbidden();
        $this->actingAs($admin)->patch("/admin/pages/{$home->id}/move", ['direction' => 'down'])->assertForbidden();

        $this->assertTrue($home->fresh()->visible);
        $this->assertSame(0, $home->fresh()->menu_order);
    }

    public function test_page_reorder_and_toggle_require_authentication(): void
    {
        $page = Page::where('slug', 'abonos')->firstOrFail();

        $this->patch("/admin/pages/{$page->id}/toggle")->assertRedirect('/login');
        $this->patch("/admin/pages/{$page->id}/move", ['direction' => 'up'])->assertRedirect('/login');

        $this->assertTrue($page->fresh()->visible);
    }

    public function test_hidden_section_disappears_from_the_public_page(): void
    {
        $section = Section::whereHas('page', fn ($q) => $q->where('slug', 'home'))
            ->where('key', 'testimonio')->firstOrFail();

        $this->get('/')->assertSee('Mariel Aguirre');

        $section->update(['visible' => false]);

        $this->get('/')->assertDontSee('Mariel Aguirre');
    }

    private function reviewsSection(): Section
    {
        return Section::whereHas('page', fn ($q) => $q->where('slug', 'home'))
            ->where('key', 'testimonio')->firstOrFail();
    }

    public function test_reviews_section_publishes_every_review(): void
    {
        $this->reviewsSection()->update(['content' => ['reviews' => [
            ['quote' => 'Primera reseña.', 'author' => 'Ana', 'rating' => 5],
            ['quote' => 'Segunda reseña.', 'author' => 'Beto', 'rating' => 4],
            ['quote' => 'Tercera reseña.', 'author' => 'Carla', 'rating' => 3],
        ]]]);

        $this->get('/')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('sections.'.$this->sectionIndex('testimonio').'.content.reviews', [
                ['quote' => 'Primera reseña.', 'author' => 'Ana', 'rating' => 5],
                ['quote' => 'Segunda reseña.', 'author' => 'Beto', 'rating' => 4],
                ['quote' => 'Tercera reseña.', 'author' => 'Carla', 'rating' => 3],
            ]));
    }

    /** La posición de una sección dentro de las props públicas de la home. */
    private function sectionIndex(string $key): int
    {
        return Section::whereHas('page', fn ($q) => $q->where('slug', 'home'))
            ->visible()->orderBy('position')->pluck('key')->search($key);
    }

    public function test_saving_reviews_drops_empty_rows_and_stores_the_rating_as_an_integer(): void
    {
        $section = $this->reviewsSection();

        $this->actingAs($this->admin())
            ->put("/admin/sections/{$section->id}", ['content' => ['reviews' => [
                ['quote' => 'Vale.', 'author' => 'Ana', 'rating' => '4'],
                // Sin texto no es una reseña: se descarta aunque tenga autor.
                ['quote' => '   ', 'author' => 'Beto', 'rating' => '5'],
                // Sin puntaje explícito quedan las 5 estrellas de siempre.
                ['quote' => 'También vale.', 'author' => null, 'rating' => null],
            ]]])
            ->assertRedirect();

        $this->assertSame([
            ['quote' => 'Vale.', 'author' => 'Ana', 'rating' => 4],
            ['quote' => 'También vale.', 'author' => null, 'rating' => 5],
        ], $section->fresh()->content['reviews']);
    }

    public function test_review_rating_outside_one_to_five_is_rejected(): void
    {
        $section = $this->reviewsSection();

        $this->actingAs($this->admin())
            ->put("/admin/sections/{$section->id}", ['content' => ['reviews' => [
                ['quote' => 'Vale.', 'author' => 'Ana', 'rating' => 9],
            ]]])
            ->assertSessionHasErrors('content.reviews.0.rating');
    }

    public function test_missing_section_seeder_inserts_the_cover_without_touching_edited_sections(): void
    {
        $page = Page::where('slug', 'clases-semanales')->firstOrFail();

        // Estado "producción": la portada todavía no existe y el dueño ya editó
        // el resto de la página desde el panel.
        $page->sections()->where('key', 'banner')->delete();

        $edited = $page->sections()->where('key', 'clase-principal')->firstOrFail();
        $edited->update([
            'content' => [...$edited->content, 'image' => 'sections/subida-a-mano.png'],
            'visible' => false,
            'position' => 7,
        ]);

        (new ContentSeeder)->seedMissingSection('clases-semanales', 'banner');

        $banner = $page->sections()->where('key', 'banner')->firstOrFail();
        $titulo = $page->sections()->where('key', 'titulo')->firstOrFail();

        $this->assertSame('hero', $banner->type);
        $this->assertTrue($banner->visible);
        $this->assertSame($titulo->position + 1, $banner->position);
        $this->assertSame('seed/eventos-especiales/vista-julio-rosario-kelsang-panchen7.jpg', $banner->content['image']);

        // La sección editada conserva imagen, visibilidad y su lugar (corrido uno).
        $edited->refresh();
        $this->assertSame('sections/subida-a-mano.png', $edited->content['image']);
        $this->assertFalse($edited->visible);
        $this->assertSame(8, $edited->position);

        // Repetible: no duplica ni reordena de nuevo.
        (new ContentSeeder)->seedMissingSection('clases-semanales', 'banner');

        $this->assertSame(1, $page->sections()->where('key', 'banner')->count());
        $this->assertSame(8, $edited->fresh()->position);
    }

    public function test_reordering_cards_changes_their_order_on_the_public_page(): void
    {
        $admin = $this->admin();
        $section = Section::whereHas('page', fn ($q) => $q->where('slug', 'home'))
            ->where('key', 'actividades-semanales')->firstOrFail();

        $cards = $section->content['cards'];
        $this->assertGreaterThanOrEqual(2, count($cards));

        [$first, $second] = [$cards[0]['title'], $cards[1]['title']];
        $this->assertSame([$first, $second], $this->homeCardTitles('actividades-semanales'));

        // El panel manda el array ya reordenado.
        [$cards[0], $cards[1]] = [$cards[1], $cards[0]];

        $this->actingAs($admin)->put("/admin/sections/{$section->id}", [
            'content' => [...$section->content, 'cards' => $cards],
        ])->assertRedirect();

        $fresh = $section->fresh()->content['cards'];
        $this->assertSame($second, $fresh[0]['title']);
        $this->assertSame($first, $fresh[1]['title']);

        // Y el orden que recibe el componente público es el nuevo.
        $this->assertSame([$second, $first], $this->homeCardTitles('actividades-semanales'));
    }

    /**
     * Card titles of a home card_grid, in the order CardGridSection.vue renders.
     *
     * @return array<int, string>
     */
    private function homeCardTitles(string $key): array
    {
        $titles = [];

        $this->get('/')->assertInertia(function (AssertableInertia $page) use ($key, &$titles) {
            $section = collect($page->toArray()['props']['sections'])->firstWhere('key', $key);
            $titles = collect($section['content']['cards'])->pluck('title')->all();
        });

        return $titles;
    }

    public function test_replacing_an_image_after_reordering_cards_deletes_the_right_file(): void
    {
        $admin = $this->admin();
        $section = Section::whereHas('page', fn ($q) => $q->where('slug', 'home'))
            ->where('key', 'actividades-semanales')->firstOrFail();

        // Cada tarjeta con su propia imagen subida, para que ambas sean borrables.
        $cards = $section->content['cards'];

        $this->actingAs($admin)->put("/admin/sections/{$section->id}", [
            'content' => [...$section->content, 'cards' => $cards],
            'files' => [
                'cards' => [
                    0 => ['image' => UploadedFile::fake()->image('primera.jpg', 400, 300)],
                    1 => ['image' => UploadedFile::fake()->image('segunda.jpg', 400, 300)],
                ],
            ],
        ]);

        $cards = $section->fresh()->content['cards'];
        [$imageA, $imageB] = [$cards[0]['image'], $cards[1]['image']];

        // Se invierten y, en el mismo guardado, se reemplaza la imagen de la que
        // quedó primera (la que traía $imageB).
        [$cards[0], $cards[1]] = [$cards[1], $cards[0]];

        $this->actingAs($admin)->put("/admin/sections/{$section->id}", [
            'content' => [...$section->content, 'cards' => $cards],
            'files' => ['cards' => [0 => ['image' => UploadedFile::fake()->image('nueva.jpg', 400, 300)]]],
        ])->assertRedirect();

        $fresh = $section->fresh()->content['cards'];

        // Se borró la imagen de esa tarjeta, no la que ocupaba antes el índice 0.
        Storage::disk('public')->assertMissing($imageB);
        Storage::disk('public')->assertExists($imageA);
        $this->assertSame($imageA, $fresh[1]['image']);
        $this->assertNotSame($imageB, $fresh[0]['image']);
        Storage::disk('public')->assertExists($fresh[0]['image']);
    }

    public function test_gallery_requires_authentication(): void
    {
        $this->get('/admin/gallery')->assertRedirect('/login');
        $this->delete('/admin/gallery', ['path' => 'sections/x.png'])->assertRedirect('/login');
    }

    public function test_gallery_lists_images_and_says_where_each_one_is_used(): void
    {
        $admin = $this->admin();
        $section = Section::whereHas('page', fn ($q) => $q->where('slug', 'home'))
            ->where('key', 'fundador')->firstOrFail();

        $this->actingAs($admin)->put("/admin/sections/{$section->id}", [
            'content' => $section->content,
            'files' => ['image' => UploadedFile::fake()->image('en-uso.jpg', 400, 300)],
        ]);

        $used = $section->fresh()->content['image'];
        $images = collect($this->actingAs($admin)->get('/admin/gallery')->assertOk()->viewData('page')['props']['images']);

        $entry = $images->firstWhere('path', $used);
        $this->assertNotNull($entry);
        $this->assertFalse($entry['deletable']);
        $this->assertNotEmpty($entry['used_by']);
        $this->assertStringContainsString('fundador', $entry['used_by'][0]);

        // Las sembradas figuran y tampoco se pueden borrar.
        $seeded = $images->firstWhere('path', 'seed/shared/K_Panchen.webp');
        $this->assertNotNull($seeded);
        $this->assertTrue($seeded['seeded']);
        $this->assertFalse($seeded['deletable']);
    }

    public function test_gallery_marks_unused_images_so_the_filter_can_list_them(): void
    {
        $admin = $this->admin();
        Storage::disk('public')->put('sections/sin-uso.png', 'x');

        $images = collect($this->actingAs($admin)->get('/admin/gallery')->assertOk()->viewData('page')['props']['images']);

        // El filtro "Sin uso" se arma con used_by vacío, que es lo que manda el server.
        $sinUso = $images->filter(fn ($i) => $i['used_by'] === []);

        $this->assertTrue($sinUso->contains('path', 'sections/sin-uso.png'));
        $this->assertNotEmpty($images->firstWhere('path', 'sections/sin-uso.png'));
        $this->assertTrue($sinUso->every(fn ($i) => $i['used_by'] === []));
        $this->assertTrue($images->reject(fn ($i) => $i['used_by'] === [])->isNotEmpty());
    }

    public function test_gallery_lists_every_copy_while_the_picker_collapses_them(): void
    {
        $admin = $this->admin();

        // Dos archivos distintos con los mismos bytes, como los que deja adopt().
        Storage::disk('public')->put('sections/copia-a.png', 'mismos-bytes');
        Storage::disk('public')->put('sections/copia-b.png', 'mismos-bytes');

        $gallery = collect($this->actingAs($admin)->get('/admin/gallery')->viewData('page')['props']['images'])
            ->whereIn('path', ['sections/copia-a.png', 'sections/copia-b.png']);

        $picker = collect($this->actingAs($admin)->get('/admin/media')->json('images'))
            ->whereIn('path', ['sections/copia-a.png', 'sections/copia-b.png']);

        $this->assertCount(2, $gallery, 'la galería administra archivos: muestra las dos copias');
        $this->assertCount(1, $picker, 'el selector muestra una sola entrada por imagen');
    }

    public function test_gallery_deletes_an_image_that_nobody_uses(): void
    {
        $admin = $this->admin();
        Storage::disk('public')->put('sections/huerfana.png', 'x');

        $this->actingAs($admin)->delete('/admin/gallery', ['path' => 'sections/huerfana.png'])
            ->assertRedirect()
            ->assertSessionHas('success');

        Storage::disk('public')->assertMissing('sections/huerfana.png');
    }

    public function test_gallery_refuses_to_delete_an_image_in_use(): void
    {
        $admin = $this->admin();
        $section = Section::whereHas('page', fn ($q) => $q->where('slug', 'home'))
            ->where('key', 'fundador')->firstOrFail();

        $this->actingAs($admin)->put("/admin/sections/{$section->id}", [
            'content' => $section->content,
            'files' => ['image' => UploadedFile::fake()->image('en-uso.jpg', 400, 300)],
        ]);

        $used = $section->fresh()->content['image'];

        $this->actingAs($admin)->delete('/admin/gallery', ['path' => $used])
            ->assertRedirect()
            ->assertSessionHasErrors('image');

        Storage::disk('public')->assertExists($used);
        $this->assertSame($used, $section->fresh()->content['image']);
    }

    public function test_gallery_refuses_to_delete_a_seeded_image_and_rejects_paths_outside_the_folders(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->delete('/admin/gallery', ['path' => 'seed/shared/K_Panchen.webp'])
            ->assertRedirect()
            ->assertSessionHasErrors('image');

        Storage::disk('public')->assertExists('seed/shared/K_Panchen.webp');

        $this->actingAs($admin)->delete('/admin/gallery', ['path' => '../../.env'])->assertForbidden();
    }

    public function test_media_library_requires_authentication(): void
    {
        $this->get('/admin/media')->assertRedirect('/login');
    }

    public function test_media_library_lists_uploaded_and_seeded_images(): void
    {
        $admin = $this->admin();
        $section = Section::whereHas('page', fn ($q) => $q->where('slug', 'home'))
            ->where('key', 'fundador')->firstOrFail();

        $this->actingAs($admin)->put("/admin/sections/{$section->id}", [
            'content' => $section->content,
            'files' => ['image' => UploadedFile::fake()->image('recien-subida.jpg', 800, 600)],
        ]);

        $images = $this->actingAs($admin)->get('/admin/media')->assertOk()->json('images');
        $paths = array_column($images, 'path');

        $this->assertContains($section->fresh()->content['image'], $paths);
        $this->assertContains('seed/shared/K_Panchen.webp', $paths);

        // Lo subido va antes que las imágenes sembradas del sitio.
        $this->assertFalse($images[0]['seeded']);
    }

    public function test_picking_an_image_from_the_gallery_copies_it_so_two_sections_never_share_the_file(): void
    {
        $admin = $this->admin();
        $source = Section::whereHas('page', fn ($q) => $q->where('slug', 'home'))
            ->where('key', 'fundador')->firstOrFail();
        $target = Section::whereHas('page', fn ($q) => $q->where('slug', 'clases-semanales'))
            ->where('key', 'maestro')->firstOrFail();

        $this->actingAs($admin)->put("/admin/sections/{$source->id}", [
            'content' => $source->content,
            'files' => ['image' => UploadedFile::fake()->image('compartida.jpg', 800, 600)],
        ]);

        $shared = $source->fresh()->content['image'];

        // El panel manda la ruta elegida en content, sin archivo adjunto.
        $this->actingAs($admin)->put("/admin/sections/{$target->id}", [
            'content' => [...$target->content, 'image' => $shared],
        ])->assertRedirect();

        $adopted = $target->fresh()->content['image'];

        $this->assertNotSame($shared, $adopted);
        $this->assertStringStartsWith('sections/', $adopted);
        Storage::disk('public')->assertExists($adopted);
        Storage::disk('public')->assertExists($shared);
        $this->assertSame($shared, $source->fresh()->content['image']);

        // Y reemplazar la imagen de la copia no borra el archivo del original.
        $this->actingAs($admin)->put("/admin/sections/{$target->id}", [
            'content' => $target->fresh()->content,
            'files' => ['image' => UploadedFile::fake()->image('otra.jpg', 400, 300)],
        ]);

        Storage::disk('public')->assertExists($shared);
        Storage::disk('public')->assertMissing($adopted);
    }

    public function test_picking_a_seeded_image_shares_its_path_instead_of_copying(): void
    {
        $admin = $this->admin();
        $section = Section::whereHas('page', fn ($q) => $q->where('slug', 'clases-semanales'))
            ->where('key', 'maestro')->firstOrFail();

        $this->actingAs($admin)->put("/admin/sections/{$section->id}", [
            'content' => [...$section->content, 'image' => 'seed/home/18.jpg'],
        ])->assertRedirect();

        $this->assertSame('seed/home/18.jpg', $section->fresh()->content['image']);
    }

    public function test_section_update_rejects_an_image_path_outside_the_gallery_folders(): void
    {
        $admin = $this->admin();
        $section = Section::whereHas('page', fn ($q) => $q->where('slug', 'clases-semanales'))
            ->where('key', 'maestro')->firstOrFail();

        $this->actingAs($admin)->put("/admin/sections/{$section->id}", [
            'content' => [...$section->content, 'image' => '../../.env'],
        ])->assertRedirect();

        $this->assertNull($section->fresh()->content['image']);
    }

    public function test_the_template_cards_are_seeded_hidden_locked_and_ignored_by_the_public_page(): void
    {
        // Las dos páginas con fichas de clase clonables tienen su plantilla.
        foreach (['cursos-y-retiros', 'clases-semanales'] as $slug) {
            $page = Page::where('slug', $slug)->firstOrFail();

            $plantilla = $page->sections()->where('key', 'plantilla')->firstOrFail();
            $banner = $page->sections()->where('key', 'banner')->firstOrFail();

            $this->assertSame('class_info', $plantilla->type, $slug);
            $this->assertTrue($plantilla->is_template, "$slug: es plantilla");
            $this->assertFalse($plantilla->visible, "$slug: entra oculta");
            $this->assertFalse($plantilla->show_on_calendar, "$slug: no va al calendario");
            $this->assertSame($banner->position + 1, $plantilla->position, "$slug: queda justo debajo de la portada");

            // Tiene los mismos campos que una ficha de clase: es de donde se clona.
            foreach (['heading', 'body', 'schedule', 'location', 'price', 'cta_label', 'cta_url'] as $field) {
                $this->assertArrayHasKey($field, $plantilla->content, "$slug.$field");
            }

            // Ni el saving() del modelo la deja publicar.
            $plantilla->update(['visible' => true, 'show_on_calendar' => true]);
            $this->assertFalse($plantilla->fresh()->visible, $slug);
            $this->assertFalse($plantilla->fresh()->show_on_calendar, $slug);

            // Y por estar oculta, la página pública no la renderiza.
            $keys = [];
            $this->get('/'.$slug)->assertOk()->assertInertia(function (AssertableInertia $p) use (&$keys) {
                $keys = collect($p->toArray()['props']['sections'])->pluck('key')->all();
            });
            $this->assertNotContains('plantilla', $keys, $slug);

            // Si se borra de la base, el seeder del deploy la vuelve a poner, oculta.
            $plantilla->delete();
            (new ContentSeeder)->seedMissingSection($slug, 'plantilla');

            $repuesta = $page->sections()->where('key', 'plantilla')->firstOrFail();
            $this->assertTrue($repuesta->is_template, $slug);
            $this->assertFalse($repuesta->visible, $slug);
        }
    }

    public function test_admin_requires_authentication(): void
    {
        $this->get('/admin/pages')->assertRedirect('/login');
        $this->get('/register')->assertNotFound();
    }

    public function test_admin_can_toggle_and_reorder_sections(): void
    {
        $admin = $this->admin();
        $home = Page::where('slug', 'home')->firstOrFail();
        [$first, $second] = $home->sections()->orderBy('position')->take(2)->get();

        $this->actingAs($admin)->patch("/admin/sections/{$first->id}/toggle")->assertRedirect();
        $this->assertFalse($first->fresh()->visible);

        $this->actingAs($admin)->patch("/admin/sections/{$second->id}/move", ['direction' => 'up'])->assertRedirect();
        $this->assertEquals($first->position, $second->fresh()->position);
        $this->assertEquals($second->position, $first->fresh()->position);
    }

    public function test_admin_can_update_section_text_and_replace_its_image(): void
    {
        $admin = $this->admin();
        $section = Section::whereHas('page', fn ($q) => $q->where('slug', 'home'))
            ->where('key', 'fundador')->firstOrFail();

        $content = $section->content;
        $content['heading'] = 'El fundador (editado)';

        $this->actingAs($admin)
            ->put("/admin/sections/{$section->id}", [
                'content' => $content,
                'files' => ['image' => UploadedFile::fake()->image('nuevo.jpg', 800, 600)],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $fresh = $section->fresh();
        $this->assertEquals('El fundador (editado)', $fresh->content['heading']);
        $this->assertStringStartsWith('sections/', $fresh->content['image']);
        Storage::disk('public')->assertExists($fresh->content['image']);
    }

    public function test_admin_can_clone_a_section_below_the_original(): void
    {
        $admin = $this->admin();
        $home = Page::where('slug', 'home')->firstOrFail();
        $section = $home->sections()->where('key', 'fundador')->firstOrFail();

        // Imagen subida desde el panel: la copia debe quedarse con su propio archivo.
        $this->actingAs($admin)
            ->put("/admin/sections/{$section->id}", [
                'content' => $section->content,
                'files' => ['image' => UploadedFile::fake()->image('original.jpg')],
            ])
            ->assertRedirect();

        $original = $section->fresh();
        $next = $home->sections()->where('position', '>', $original->position)->orderBy('position')->firstOrFail();

        $this->actingAs($admin)
            ->post("/admin/sections/{$original->id}/duplicate")
            ->assertRedirect()
            ->assertSessionHas('success');

        $copy = Section::where('page_id', $home->id)->where('key', 'fundador-copia')->firstOrFail();

        $this->assertFalse($copy->visible);
        $this->assertEquals($original->type, $copy->type);
        $this->assertEquals($original->content['heading'], $copy->content['heading']);
        $this->assertEquals($original->position + 1, $copy->position);
        $this->assertEquals($next->position + 1, $next->fresh()->position);

        // Oculta no cambia la home; al mostrarla el bloque aparece dos veces.
        $before = substr_count($this->get('/')->getContent(), 'El fundador');
        $this->assertGreaterThan(0, $before);

        $copy->update(['visible' => true]);
        $this->assertEquals($before * 2, substr_count($this->get('/')->getContent(), 'El fundador'));

        // El archivo se duplicó: reemplazarlo en la copia no rompe la original.
        $this->assertNotEquals($original->content['image'], $copy->content['image']);
        Storage::disk('public')->assertExists($copy->content['image']);

        $this->actingAs($admin)
            ->put("/admin/sections/{$copy->id}", [
                'content' => $copy->content,
                'files' => ['image' => UploadedFile::fake()->image('reemplazo.jpg')],
            ])
            ->assertRedirect();

        Storage::disk('public')->assertExists($original->fresh()->content['image']);

        // Clonar una copia numera desde la key original en vez de encadenar sufijos.
        $this->actingAs($admin)->post("/admin/sections/{$copy->id}/duplicate")->assertRedirect();
        $this->assertDatabaseHas('sections', ['page_id' => $home->id, 'key' => 'fundador-copia-2']);
    }

    public function test_section_update_rejects_oversized_image(): void
    {
        $admin = $this->admin();
        $section = Section::whereHas('page', fn ($q) => $q->where('slug', 'home'))
            ->where('key', 'fundador')->firstOrFail();

        $this->actingAs($admin)
            ->put("/admin/sections/{$section->id}", [
                'content' => $section->content,
                'files' => ['image' => UploadedFile::fake()->image('grande.jpg')->size(5000)],
            ])
            ->assertSessionHasErrors('files.image');
    }

    public function test_admin_can_delete_a_section_and_the_images_it_uploaded(): void
    {
        $admin = $this->admin();
        $home = Page::where('slug', 'home')->firstOrFail();
        $section = $home->sections()->where('key', 'fundador')->firstOrFail();

        $this->actingAs($admin)->put("/admin/sections/{$section->id}", [
            'content' => $section->content,
            'files' => ['image' => UploadedFile::fake()->image('foto.jpg')],
        ])->assertRedirect();

        $image = $section->fresh()->content['image'];
        Storage::disk('public')->assertExists($image);

        $this->actingAs($admin)->delete("/admin/sections/{$section->id}")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('sections', ['id' => $section->id]);
        Storage::disk('public')->assertMissing($image);
        $this->get('/')->assertOk()->assertDontSee('El fundador');
    }

    public function test_deleting_a_section_needs_a_session(): void
    {
        $section = Section::whereHas('page', fn ($q) => $q->where('slug', 'home'))
            ->where('key', 'fundador')->firstOrFail();

        $this->delete("/admin/sections/{$section->id}")->assertRedirect('/login');
        $this->assertDatabaseHas('sections', ['id' => $section->id]);
    }

    public function test_the_template_card_cannot_be_deleted_or_shown(): void
    {
        $admin = $this->admin();
        $plantilla = Section::whereHas('page', fn ($q) => $q->where('slug', 'cursos-y-retiros'))
            ->where('key', 'plantilla')->firstOrFail();

        $this->actingAs($admin)->delete("/admin/sections/{$plantilla->id}")->assertForbidden();
        $this->assertDatabaseHas('sections', ['id' => $plantilla->id]);

        $this->actingAs($admin)->patch("/admin/sections/{$plantilla->id}/toggle")->assertForbidden();
        $this->assertFalse($plantilla->fresh()->visible);
    }

    public function test_cloning_the_template_gives_a_normal_hidden_card(): void
    {
        $admin = $this->admin();
        $page = Page::where('slug', 'cursos-y-retiros')->firstOrFail();
        $plantilla = $page->sections()->where('key', 'plantilla')->firstOrFail();

        $this->actingAs($admin)->post("/admin/sections/{$plantilla->id}/duplicate")
            ->assertRedirect()->assertSessionHas('success');

        $copia = $page->sections()->where('key', 'plantilla-copia')->firstOrFail();

        $this->assertSame('class_info', $copia->type);
        $this->assertFalse($copia->is_template, 'la copia ya no es plantilla');
        $this->assertFalse($copia->visible, 'entra oculta, como cualquier clon');
        $this->assertSame($plantilla->content['heading'], $copia->content['heading']);

        // Y la copia sí se puede mostrar y eliminar.
        $this->actingAs($admin)->patch("/admin/sections/{$copia->id}/toggle")->assertRedirect();
        $this->assertTrue($copia->fresh()->visible);

        $this->actingAs($admin)->delete("/admin/sections/{$copia->id}")->assertRedirect();
        $this->assertDatabaseMissing('sections', ['id' => $copia->id]);
    }

    public function test_admin_can_create_event_shown_on_home_strip(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post('/admin/events', [
                'title' => 'Retiro de meditacion anual',
                'date_text' => 'Domingo 1 de noviembre',
                'starts_at' => '2026-11-01',
                'visible' => true,
                'show_on_home' => true,
                'image' => UploadedFile::fake()->image('retiro.jpg'),
            ])
            ->assertRedirect(route('admin.events.index'));

        $event = Event::where('title', 'Retiro de meditacion anual')->firstOrFail();
        $this->assertTrue($event->show_on_home);
        Storage::disk('public')->assertExists($event->image_path);

        $this->get('/')->assertSee('Retiro de meditacion anual');

        $event->update(['visible' => false]);
        $this->get('/')->assertDontSee('Retiro de meditacion anual');
    }

    public function test_editing_a_faq_updates_every_page_that_shows_it(): void
    {
        $admin = $this->admin();
        $faq = Faq::where('question', '¿Se necesita experiencia?')->firstOrFail();

        $this->actingAs($admin)
            ->put("/admin/faqs/{$faq->id}", [
                'question' => '¿Necesito experiencia previa para meditar?',
                'answer' => $faq->answer,
                'visible' => true,
            ])
            ->assertRedirect();

        $this->get('/gratis')->assertSee('Necesito experiencia previa para meditar');
        $this->get('/voluntariado')->assertSee('Necesito experiencia previa para meditar');
    }

    public function test_admin_can_update_settings_and_logo(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post('/admin/settings', [
                'site_name' => 'Meditación Kadampa Rosario',
                'phone_display' => '341 0000000',
                'email' => 'nuevo@correo.com',
                'footer_resources' => json_decode(Setting::get('footer_resources', '[]'), true),
                'files' => ['logo' => UploadedFile::fake()->image('logo.png', 300, 300)],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertEquals('341 0000000', Setting::get('phone_display'));
        $this->assertStringStartsWith('settings/', Setting::get('logo_path'));

        $this->get('/')->assertSee('341 0000000');
    }

    public function test_footer_logo_is_a_separate_file_and_falls_back_to_the_menu_one(): void
    {
        $admin = $this->admin();

        // Sin logo propio, el pie usa el del menú.
        $this->actingAs($admin)->post('/admin/settings', [
            'files' => ['logo' => UploadedFile::fake()->image('menu.png', 300, 300)],
        ])->assertRedirect();

        $menu = Setting::get('logo_path');
        $this->assertNull(Setting::get('footer_logo_path'));
        $this->assertSame($menu, $this->footerLogo());

        // Con logo propio, cada uno es su archivo.
        $this->actingAs($admin)->post('/admin/settings', [
            'logo_path' => $menu,
            'files' => ['footer_logo' => UploadedFile::fake()->image('pie.png', 400, 200)],
        ])->assertRedirect();

        $footer = Setting::get('footer_logo_path');
        $this->assertNotNull($footer);
        $this->assertNotSame($menu, $footer);
        $this->assertSame($menu, Setting::get('logo_path'));
        Storage::disk('public')->assertExists($menu);
        Storage::disk('public')->assertExists($footer);
        $this->assertSame($footer, $this->footerLogo());

        // Quitarlo borra el archivo y vuelve al del menú, sin tocar ese.
        $this->actingAs($admin)->post('/admin/settings', [
            'logo_path' => $menu,
            'footer_logo_path' => '',
        ])->assertRedirect();

        $this->assertNull(Setting::get('footer_logo_path'));
        Storage::disk('public')->assertMissing($footer);
        Storage::disk('public')->assertExists($menu);
        $this->assertSame($menu, $this->footerLogo());
    }

    public function test_admin_can_edit_the_search_description_of_a_page(): void
    {
        $admin = $this->admin();
        $page = Page::where('slug', 'voluntariado')->firstOrFail();

        // Viene con la descripción raspada del sitio original.
        $props = $this->actingAs($admin)->get("/admin/pages/{$page->id}")->assertOk()->viewData('page')['props'];

        $this->assertSame($page->meta_description, $props['page']['meta_description']);
        $this->assertSame('Voluntariado', $props['page']['title']);
        $this->assertNotEmpty($props['page']['site_name']);
        $this->assertSame(url('/voluntariado'), $props['page']['url']);

        $nueva = 'Sumate como voluntario al centro de meditación kadampa de Zona Norte.';

        $this->actingAs($admin)->patch("/admin/pages/{$page->id}", ['title' => $page->title, 'meta_description' => $nueva])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame($nueva, $page->fresh()->meta_description);

        // Y sale publicada en el <head>, que es para lo que se edita.
        $html = $this->get('/voluntariado')->assertOk()->getContent();
        $this->assertStringContainsString('<meta name="description" content="'.e($nueva).'">', $html);
        $this->assertStringContainsString('<meta property="og:description" content="'.e($nueva).'">', $html);

        // Vaciarla la deja en null y el <head> no emite descripción.
        $this->actingAs($admin)->patch("/admin/pages/{$page->id}", ['title' => $page->title, 'meta_description' => ''])->assertRedirect();

        $this->assertNull($page->fresh()->meta_description);
        $this->assertStringNotContainsString('name="description"', $this->get('/voluntariado')->getContent());

        // Más largo que la columna se rechaza, y no toca lo guardado.
        $this->actingAs($admin)->patch("/admin/pages/{$page->id}", ['title' => $page->title, 'meta_description' => str_repeat('a', 501)])
            ->assertSessionHasErrors('meta_description');

        $this->assertNull($page->fresh()->meta_description);
    }

    public function test_admin_can_rename_a_page_and_its_menu_label(): void
    {
        $admin = $this->admin();
        $page = Page::where('slug', 'gratis')->firstOrFail();

        $this->actingAs($admin)->patch("/admin/pages/{$page->id}", [
            'title' => 'Actividades gratuitas',
            'menu_label' => 'Gratis y abierto',
        ])->assertRedirect()->assertSessionHas('success');

        $page->refresh();
        $this->assertSame('Actividades gratuitas', $page->title);
        $this->assertSame('Gratis y abierto', $page->menu_label);
        // El slug no se toca: los enlaces siguen valiendo.
        $this->assertSame('gratis', $page->slug);

        // El título sale en el <head> y el nombre nuevo, en el menú del sitio.
        $html = $this->get('/gratis')->assertOk()->getContent();
        $this->assertStringContainsString('<title inertia>Actividades gratuitas - ', $html);

        $this->get('/')->assertInertia(fn (AssertableInertia $p) => $p->where(
            'nav',
            fn ($nav) => collect($nav)->firstWhere('slug', 'gratis')['label'] === 'Gratis y abierto',
        ));

        // Vaciar el nombre de menú saca la página del menú, pero su dirección sigue.
        $this->actingAs($admin)->patch("/admin/pages/{$page->id}", [
            'title' => 'Actividades gratuitas',
            'menu_label' => '',
        ])->assertRedirect();

        $this->assertNull($page->fresh()->menu_label);
        $this->assertNotContains('gratis', $this->navSlugs());
        $this->get('/gratis')->assertOk();

        // El título es obligatorio.
        $this->actingAs($admin)->patch("/admin/pages/{$page->id}", ['title' => ''])
            ->assertSessionHasErrors('title');
    }

    public function test_home_does_not_accept_a_menu_label(): void
    {
        $admin = $this->admin();
        $home = Page::where('slug', 'home')->firstOrFail();

        $this->actingAs($admin)->patch("/admin/pages/{$home->id}", [
            'title' => 'Inicio',
            'menu_label' => 'Inicio',
        ])->assertRedirect();

        $this->assertSame('Inicio', $home->fresh()->title);
        $this->assertNull($home->fresh()->menu_label);
        $this->assertNotContains('home', $this->navSlugs());
    }

    public function test_editing_a_page_needs_a_session(): void
    {
        $page = Page::where('slug', 'voluntariado')->firstOrFail();
        $original = $page->meta_description;

        $this->patch("/admin/pages/{$page->id}", ['title' => 'Colado', 'meta_description' => 'colado'])->assertRedirect('/login');

        $this->assertSame($original, $page->fresh()->meta_description);
        $this->assertSame('Voluntariado', $page->fresh()->title);
    }

    public function test_the_head_carries_the_link_preview_tags_rendered_by_the_server(): void
    {
        // WhatsApp y las redes no ejecutan JavaScript, así que estas etiquetas tienen
        // que estar en el HTML del servidor, no en el <Head> de Inertia.
        Setting::set('site_name', 'Meditación Kadampa en Zona Norte');

        $html = $this->get('/voluntariado')->assertOk()->getContent();

        $this->assertStringContainsString(
            '<title inertia>Voluntariado - Meditación Kadampa en Zona Norte</title>',
            $html,
        );
        $this->assertStringContainsString('<meta property="og:site_name" content="Meditación Kadampa en Zona Norte">', $html);
        $this->assertStringContainsString('<meta property="og:title" content="Voluntariado - Meditación Kadampa en Zona Norte">', $html);
        $this->assertStringContainsString('<meta property="og:url" content="'.url('/voluntariado').'">', $html);
        $this->assertStringContainsString('<meta property="og:type" content="website">', $html);

        // La descripción de la página, que antes sólo viajaba en los props.
        $description = Page::where('slug', 'voluntariado')->firstOrFail()->meta_description;
        $this->assertStringContainsString('<meta name="description" content="'.e($description).'">', $html);
        $this->assertStringContainsString('<meta property="og:description" content="'.e($description).'">', $html);

        // La imagen es la portada de la página, en URL absoluta.
        $hero = Section::whereHas('page', fn ($query) => $query->where('slug', 'voluntariado'))
            ->where('type', 'hero')->firstOrFail()->content['image'];

        $this->assertStringContainsString('<meta property="og:image" content="'.url('/storage/'.$hero).'">', $html);
        $this->assertStringContainsString('<meta name="twitter:card" content="summary_large_image">', $html);
    }

    public function test_the_site_name_comes_from_the_panel_and_not_from_app_name(): void
    {
        config(['app.name' => 'Nombre viejo del .env']);

        Setting::set('site_name', 'Nombre nuevo del panel');
        $this->assertSame('Nombre nuevo del panel', SiteMeta::siteName());
        $this->assertStringContainsString('Nombre nuevo del panel', $this->get('/')->getContent());
        $this->assertStringNotContainsString('Nombre viejo del .env', $this->get('/')->getContent());

        // Y llega al cliente por el prop compartido, que es de donde app.ts lo toma.
        $this->get('/')->assertInertia(fn (AssertableInertia $page) => $page->where('name', 'Nombre nuevo del panel'));

        // Sin el ajuste cargado, APP_NAME queda como respaldo.
        Setting::set('site_name', null);
        $this->assertSame('Nombre viejo del .env', SiteMeta::siteName());
    }

    public function test_a_page_without_a_cover_falls_back_to_the_logo_for_the_preview(): void
    {
        Section::whereHas('page', fn ($query) => $query->where('slug', 'gratis'))
            ->where('type', 'hero')->delete();

        $html = $this->get('/gratis')->assertOk()->getContent();

        $this->assertStringContainsString('<meta property="og:image" content="'.url('/storage/'.Setting::get('logo_path')).'">', $html);

        // Y sin logo tampoco se inventa una imagen.
        Setting::set('logo_path', null);
        $html = $this->get('/gratis')->assertOk()->getContent();

        $this->assertStringNotContainsString('og:image', $html);
        $this->assertStringContainsString('<meta name="twitter:card" content="summary">', $html);
    }

    public function test_the_favicon_is_the_footer_logo_and_falls_back_to_the_menu_one(): void
    {
        $menu = Setting::get('logo_path');
        $this->assertNotNull($menu);

        // Sin logo del pie, el icono es el del menú (que es lo que había antes).
        Setting::set('footer_logo_path', null);
        $this->get('/')->assertOk()->assertSee('<link rel="icon" type="image/png" href="/storage/'.$menu.'">', false);

        // Con logo del pie cargado, gana ese: suele ser el isotipo cuadrado.
        Setting::set('footer_logo_path', 'settings/isotipo.webp');

        $this->get('/')->assertOk()
            ->assertSee('<link rel="icon" type="image/webp" href="/storage/settings/isotipo.webp">', false)
            ->assertSee('<link rel="apple-touch-icon" href="/storage/settings/isotipo.webp">', false)
            ->assertDontSee($menu, false);

        // El tipo sale de la extensión, no está fijo en png.
        $this->assertSame('image/webp', Setting::favicon()['type']);

        Setting::set('footer_logo_path', 'settings/isotipo.png');
        $this->assertSame('image/png', Setting::favicon()['type']);

        Setting::set('footer_logo_path', 'settings/isotipo.jpeg');
        $this->assertSame('image/jpeg', Setting::favicon()['type']);

        // Una extensión que no reconocemos no inventa un tipo.
        Setting::set('footer_logo_path', 'settings/isotipo.avif');
        $this->assertNull(Setting::favicon()['type']);

        // Y sin ningún logo no se emite el enlace.
        Setting::set('footer_logo_path', null);
        Setting::set('logo_path', null);

        $this->assertNull(Setting::favicon());
        $this->get('/')->assertOk()->assertDontSee('rel="icon"', false);
    }

    /** Ruta del logo que el pie está mostrando, leída de los props compartidos. */
    private function footerLogo(): ?string
    {
        $logo = null;

        $this->get('/')->assertInertia(function (AssertableInertia $page) use (&$logo) {
            $settings = $page->toArray()['props']['settings'];
            $logo = $settings['footer_logo_path'] ?? $settings['logo_path'] ?? null;
        });

        return $logo;
    }

    // ---------------------------------------------------------------- calendario

    /** El prop del calendario, tal como lo recibe EventCalendarSection.vue. */
    private function calendar(): array
    {
        $data = [];

        $this->get('/calendario')->assertOk()->assertInertia(function (AssertableInertia $page) use (&$data) {
            $data = $page->toArray()['props']['calendar'];
        });

        return $data;
    }

    /**
     * Los títulos de cada día del mes, indexados por fecha (sólo los que tienen algo).
     *
     * @return array<string, array<int, string>>
     */
    private function calendarDays(): array
    {
        $days = [];

        foreach ($this->calendar()['weeks'] as $week) {
            foreach (array_filter($week['days']) as $day) {
                if ($day['activities']) {
                    $days[$day['date']] = array_column($day['activities'], 'title');
                }
            }
        }

        return $days;
    }

    public function test_calendar_page_renders_a_monday_first_month_grid_in_spanish(): void
    {
        $this->travelTo(Carbon::parse('2026-08-15 12:00', EventCalendar::TIMEZONE));

        $calendar = $this->calendar();

        $this->assertSame('2026-08', $calendar['month']);
        $this->assertSame('agosto de 2026', $calendar['label']);
        $this->assertSame('2026-08-15', $calendar['today']);
        $this->assertSame('Lun', $calendar['weekdays'][0]);
        $this->assertSame('Dom', $calendar['weekdays'][6]);

        // Sin navegación: el calendario es siempre el mes en curso.
        $this->assertArrayNotHasKey('prev', $calendar);
        $this->assertArrayNotHasKey('next', $calendar);

        // Las filas siguen teniendo 7 celdas para que cada día caiga bajo su
        // columna, pero las de los meses vecinos van vacías.
        foreach ($calendar['weeks'] as $week) {
            $this->assertCount(7, $week['days']);
        }

        // Agosto de 2026 empieza sábado: las cinco primeras celdas están vacías.
        $first = $calendar['weeks'][0]['days'];
        $this->assertSame([null, null, null, null, null], array_slice($first, 0, 5));
        $this->assertSame('2026-08-01', $first[5]['date']);
        $this->assertSame('sábado 1 de agosto', $first[5]['label']);
        $this->assertSame(6, $first[5]['weekday']);
        $this->assertSame('1 al 2 de agosto', $calendar['weeks'][0]['label']);

        // Y ninguna celda del mes vecino se cuela, ni con actividades.
        $dates = collect($calendar['weeks'])->flatMap(fn ($week) => array_filter($week['days']))->pluck('date');
        $this->assertSame('2026-08-01', $dates->first());
        $this->assertSame('2026-08-31', $dates->last());
        $this->assertCount(31, $dates);

        // El día de hoy queda marcado una sola vez en todo el mes.
        $todays = collect($calendar['weeks'])->flatMap(fn ($week) => array_filter($week['days']))->where('is_today', true);
        $this->assertCount(1, $todays);
        $this->assertSame('2026-08-15', $todays->first()['date']);
    }

    public function test_weekly_class_lands_on_every_matching_day_of_the_month(): void
    {
        $this->travelTo(Carbon::parse('2026-08-15 12:00', EventCalendar::TIMEZONE));

        $days = $this->calendarDays();

        // 'Miércoles de 19 a 20.15 hs' → todos los miércoles de agosto.
        foreach (['2026-08-05', '2026-08-12', '2026-08-19', '2026-08-26'] as $date) {
            $this->assertContains('Clases semanales', $days[$date] ?? [], "Falta la clase del $date");
        }

        // Y en ningún otro día.
        foreach ($days as $date => $titles) {
            if (! in_array($date, ['2026-08-05', '2026-08-12', '2026-08-19', '2026-08-26'], true)) {
                $this->assertNotContains('Clases semanales', $titles, "Sobra la clase del $date");
            }
        }

        // Con la hora estructurada, no el texto libre.
        $wednesday = collect($this->calendar()['weeks'])
            ->flatMap(fn ($week) => array_filter($week['days']))
            ->firstWhere('date', '2026-08-05');
        $class = collect($wednesday['activities'])->firstWhere('title', 'Clases semanales');

        $this->assertSame('19:00', $class['start']);
        $this->assertSame('20:15', $class['end']);
        $this->assertSame('clase', $class['kind']);
        $this->assertSame('clases-semanales', $class['source']['slug']);
    }

    public function test_hidden_class_section_disappears_from_the_calendar(): void
    {
        $this->travelTo(Carbon::parse('2026-08-15 12:00', EventCalendar::TIMEZONE));

        $this->assertContains('Clases semanales', $this->calendarDays()['2026-08-05']);

        Section::whereHas('page', fn ($query) => $query->where('slug', 'clases-semanales'))
            ->where('key', 'clase-principal')
            ->firstOrFail()
            ->update(['visible' => false]);

        $days = $this->calendarDays();

        $this->assertNotContains('Clases semanales', $days['2026-08-05'] ?? []);
        // Las meditaciones de los martes siguen: se ocultó una ficha, no la página.
        $this->assertContains('Meditaciones guiadas', $days['2026-08-04'] ?? []);
    }

    public function test_hidden_page_disappears_from_the_calendar(): void
    {
        $this->travelTo(Carbon::parse('2026-08-15 12:00', EventCalendar::TIMEZONE));

        $this->assertContains('gratis', array_column($this->calendar()['sources'], 'slug'));

        Page::where('slug', 'gratis')->firstOrFail()->update(['visible' => false]);

        $calendar = $this->calendar();

        $this->assertNotContains('gratis', array_column($calendar['sources'], 'slug'));
        $this->assertContains('clases-semanales', array_column($calendar['sources'], 'slug'));
    }

    public function test_event_only_reaches_the_calendar_when_it_is_marked_for_it(): void
    {
        $this->travelTo(Carbon::parse('2026-08-15 12:00', EventCalendar::TIMEZONE));

        $event = Event::create([
            'title' => 'Charla abierta',
            'starts_at' => '2026-08-20',
            'start_time' => '17:00',
            'end_time' => '19:00',
            'location' => 'Rosario',
            'visible' => true,
            'show_on_calendar' => false,
        ]);

        $this->assertNotContains('Charla abierta', $this->calendarDays()['2026-08-20'] ?? []);

        $event->update(['show_on_calendar' => true]);

        $days = $this->calendarDays();
        $this->assertContains('Charla abierta', $days['2026-08-20']);

        $activity = collect($this->calendar()['weeks'])
            ->flatMap(fn ($week) => array_filter($week['days']))
            ->firstWhere('date', '2026-08-20')['activities'];
        $charla = collect($activity)->firstWhere('title', 'Charla abierta');

        $this->assertSame('evento', $charla['kind']);
        $this->assertSame('17:00', $charla['start']);
        $this->assertSame('19:00', $charla['end']);
    }

    public function test_multi_day_event_occupies_every_day_between_its_dates(): void
    {
        $this->travelTo(Carbon::parse('2026-08-15 12:00', EventCalendar::TIMEZONE));

        Event::create([
            'title' => 'Retiro de fin de semana',
            'starts_at' => '2026-08-28',
            'ends_at' => '2026-08-30',
            'visible' => true,
            'show_on_calendar' => true,
        ]);

        $days = $this->calendarDays();

        foreach (['2026-08-28', '2026-08-29', '2026-08-30'] as $date) {
            $this->assertContains('Retiro de fin de semana', $days[$date] ?? [], "Falta el retiro del $date");
        }

        $this->assertNotContains('Retiro de fin de semana', $days['2026-08-27'] ?? []);
        $this->assertNotContains('Retiro de fin de semana', $days['2026-08-31'] ?? []);
    }

    public function test_the_calendar_is_always_the_current_month_and_ignores_any_query_string(): void
    {
        $this->travelTo(Carbon::parse('2026-08-15 12:00', EventCalendar::TIMEZONE));

        // No se puede pedir otro mes: un ?mes= viejo (o cualquier parámetro) se
        // ignora y la página responde el mes en curso, sin 422 ni redirección.
        foreach (['?mes=2026-09', '?mes=2025-03', '?mes=basura', '?cualquier=cosa'] as $query) {
            $data = [];

            $this->get('/calendario'.$query)->assertOk()->assertInertia(function (AssertableInertia $page) use (&$data) {
                $data = $page->toArray()['props']['calendar'];
            });

            $this->assertSame('2026-08', $data['month'], "$query debería seguir mostrando agosto");
        }

        // Y al cambiar el mes, el calendario acompaña.
        $this->travelTo(Carbon::parse('2026-09-10 12:00', EventCalendar::TIMEZONE));

        $september = $this->calendar();
        $this->assertSame('2026-09', $september['month']);
        $this->assertSame('septiembre de 2026', $september['label']);
        $this->assertSame('2026-09-10', $september['today']);

        // Las clases semanales sembradas no tienen vigencia, así que siguen ahí.
        $this->assertContains('Clases semanales', $this->calendarDays()['2026-09-02'] ?? []);
    }

    public function test_calendar_uses_the_argentine_day_and_not_utc(): void
    {
        // 31/8 a las 22 en Argentina ya es 1/9 en UTC: el calendario abriría en
        // septiembre si el mes se calculara con la zona de la aplicación.
        $this->travelTo(Carbon::parse('2026-08-31 22:00', EventCalendar::TIMEZONE));

        $calendar = $this->calendar();

        $this->assertSame('2026-08', $calendar['month']);
        $this->assertSame('2026-08-31', $calendar['today']);

        $todays = collect($calendar['weeks'])->flatMap(fn ($week) => array_filter($week['days']))->where('is_today', true);
        $this->assertSame(['2026-08-31'], $todays->pluck('date')->all());
    }

    public function test_admin_can_save_the_calendar_dates_of_a_class_and_they_reach_the_public_page(): void
    {
        $this->travelTo(Carbon::parse('2026-08-15 12:00', EventCalendar::TIMEZONE));

        $admin = $this->admin();
        $section = Section::whereHas('page', fn ($query) => $query->where('slug', 'clases-semanales'))
            ->where('key', 'clase-principal')
            ->firstOrFail();

        $this->actingAs($admin)->put("/admin/sections/{$section->id}", [
            'content' => [
                ...$section->content,
                'occurrences' => [
                    // El día llega como texto desde el formulario.
                    ['type' => 'weekly', 'weekday' => '1', 'start' => '19:00', 'end' => '20:15'],
                    // Fila del todo vacía (un payload viejo o sembrado): se descarta.
                    [],
                ],
            ],
        ])->assertRedirect()->assertSessionHas('success');

        $stored = $section->fresh()->content['occurrences'];

        $this->assertCount(1, $stored);
        $this->assertSame(1, $stored[0]['weekday']);
        $this->assertSame('19:00', $stored[0]['start']);
        $this->assertNull($stored[0]['date']);

        // Y el horario que se lee en la tarjeta no se tocó.
        $this->assertSame('Miércoles de 19 a 20.15 hs', $section->fresh()->content['schedule']);

        // Ahora la clase cae los lunes.
        $days = $this->calendarDays();
        $this->assertContains('Clases semanales', $days['2026-08-03'] ?? []);
        $this->assertNotContains('Clases semanales', $days['2026-08-05'] ?? []);
    }

    public function test_admin_can_save_a_one_off_date_for_a_course(): void
    {
        $this->travelTo(Carbon::parse('2026-08-15 12:00', EventCalendar::TIMEZONE));

        $admin = $this->admin();
        $section = Section::whereHas('page', fn ($query) => $query->where('slug', 'cursos-y-retiros'))
            ->where('key', 'curso')
            ->firstOrFail();

        $section->update(['visible' => true]);

        $this->actingAs($admin)->put("/admin/sections/{$section->id}", [
            'content' => [
                ...$section->content,
                'occurrences' => [
                    ['type' => 'date', 'date' => '2026-08-22', 'until' => '2026-08-23', 'start' => '10:00', 'end' => '17:00', 'label' => 'Retiro de agosto'],
                ],
            ],
        ])->assertRedirect();

        $days = $this->calendarDays();

        // El label manda sobre el título de la ficha, y el rango ocupa los dos días.
        $this->assertContains('Retiro de agosto', $days['2026-08-22'] ?? []);
        $this->assertContains('Retiro de agosto', $days['2026-08-23'] ?? []);
        $this->assertNotContains('Retiro de agosto', $days['2026-08-24'] ?? []);
    }

    public function test_calendar_dates_are_validated(): void
    {
        $admin = $this->admin();
        $section = Section::whereHas('page', fn ($query) => $query->where('slug', 'clases-semanales'))
            ->where('key', 'clase-principal')
            ->firstOrFail();

        $put = fn (array $row) => $this->actingAs($admin)
            ->put("/admin/sections/{$section->id}", ['content' => [...$section->content, 'occurrences' => [$row]]]);

        $put(['type' => 'weekly', 'weekday' => 9])->assertSessionHasErrors('content.occurrences.0.weekday');
        $put(['type' => 'weekly', 'weekday' => 3, 'start' => '25:00'])->assertSessionHasErrors('content.occurrences.0.start');
        $put(['type' => 'date'])->assertSessionHasErrors('content.occurrences.0.date');
        $put(['type' => 'date', 'date' => '22/08/2026'])->assertSessionHasErrors('content.occurrences.0.date');
        $put(['type' => 'cada rato', 'weekday' => 3])->assertSessionHasErrors('content.occurrences.0.type');

        // Una fila a medio llenar se rechaza con un mensaje en castellano, en lugar
        // de desaparecer sin avisar (el formulario tiene un tacho para descartarla).
        $put(['type' => 'weekly', 'start' => '19:00'])
            ->assertSessionHasErrors(['content.occurrences.0.weekday' => 'Elegí el día de la semana en cada fecha del calendario.']);

        $this->assertCount(1, $section->fresh()->content['occurrences']);
    }

    public function test_hidden_template_card_keeps_the_calendar_clean(): void
    {
        $this->travelTo(Carbon::parse('2026-08-15 12:00', EventCalendar::TIMEZONE));

        // La plantilla de cursos-y-retiros se siembra sin fechas justamente para
        // que no publique un horario de relleno.
        $section = Section::whereHas('page', fn ($query) => $query->where('slug', 'cursos-y-retiros'))
            ->where('key', 'curso')
            ->firstOrFail();

        $this->assertSame([], $section->content['occurrences']);
        $this->assertNotContains('cursos-y-retiros', array_column($this->calendar()['sources'], 'slug'));
    }

    public function test_the_same_activity_loaded_twice_appears_once_per_day(): void
    {
        $this->travelTo(Carbon::parse('2026-08-15 12:00', EventCalendar::TIMEZONE));

        // 'gratis' repite las meditaciones de los jueves que ya tiene
        // clases-semanales, con el mismo horario y lugar.
        $thursday = $this->calendarDays()['2026-08-06'] ?? [];

        $this->assertSame(['Meditaciones guiadas'], $thursday);
    }

    public function test_gratis_offers_two_different_schedules_and_not_the_same_one_twice(): void
    {
        $this->travelTo(Carbon::parse('2026-08-15 12:00', EventCalendar::TIMEZONE));

        $schedules = [];
        $this->get('/gratis')->assertOk()->assertInertia(function (AssertableInertia $page) use (&$schedules) {
            $schedules = collect($page->toArray()['props']['sections'])
                ->where('type', 'class_info')
                ->pluck('content.schedule')
                ->all();
        });

        // Dos fichas, con horarios distintos: antes las dos decían lo mismo.
        $this->assertCount(2, $schedules);
        $this->assertSame(count($schedules), count(array_unique($schedules)));

        // Y la de los sábados cae en el calendario en su propio día.
        $days = $this->calendarDays();

        foreach (['2026-08-01', '2026-08-08', '2026-08-15', '2026-08-22', '2026-08-29'] as $saturday) {
            $this->assertContains('Meditaciones guiadas', $days[$saturday] ?? [], "Falta la meditación del $saturday");
        }

        // El sábado no comparte horario con nada, así que no se deduplica con nada.
        $this->assertSame(['Meditaciones guiadas'], $days['2026-08-01']);
    }

    public function test_admin_calendar_screen_lists_the_class_cards_with_their_dates(): void
    {
        $admin = $this->admin();

        $cards = collect($this->actingAs($admin)->get('/admin/calendar')->assertOk()->viewData('page')['props']['cards']);

        // Están las tres fuentes, no sólo los eventos.
        $this->assertEqualsCanonicalizing(
            ['Clases semanales', 'Gratis', 'Cursos y Retiros'],
            $cards->pluck('page')->unique()->values()->all(),
        );

        // Y las fechas vienen resumidas para leerlas sin abrir la ficha.
        $clase = $cards->firstWhere('title', 'Clases semanales');
        $this->assertSame('Miércoles de 19 a 20.15 hs', $clase['dates']);
        $this->assertTrue($clase['show_on_calendar']);

        // Una ficha sin fechas se lista igual, pero avisando que no puede aparecer.
        $sinFechas = $cards->firstWhere('dates', '');
        $this->assertNotNull($sinFechas);

        // Lo oculto no se lista: tampoco puede llegar al calendario.
        Section::whereHas('page', fn ($query) => $query->where('slug', 'clases-semanales'))
            ->where('key', 'clase-principal')
            ->firstOrFail()
            ->update(['visible' => false]);

        Page::where('slug', 'gratis')->firstOrFail()->update(['visible' => false]);

        $cards = collect($this->actingAs($admin)->get('/admin/calendar')->viewData('page')['props']['cards']);

        $this->assertNotContains('Clases semanales', $cards->pluck('title'));
        $this->assertNotContains('Gratis', $cards->pluck('page'));
    }

    public function test_admin_can_take_a_class_card_off_the_calendar(): void
    {
        $this->travelTo(Carbon::parse('2026-08-15 12:00', EventCalendar::TIMEZONE));

        $admin = $this->admin();
        $section = Section::whereHas('page', fn ($query) => $query->where('slug', 'clases-semanales'))
            ->where('key', 'clase-principal')
            ->firstOrFail();

        $this->assertContains('Clases semanales', $this->calendarDays()['2026-08-05']);

        $this->actingAs($admin)->patch("/admin/calendar/sections/{$section->id}", ['show' => false])->assertRedirect();

        $this->assertFalse($section->fresh()->show_on_calendar);
        $this->assertNotContains('Clases semanales', $this->calendarDays()['2026-08-05'] ?? []);
        // Sigue publicada en su página: sólo salió del calendario.
        $this->assertTrue($section->fresh()->visible);
        $this->get('/clases-semanales')->assertOk()->assertSee('de 19 a 20.15 hs');

        $this->actingAs($admin)->patch("/admin/calendar/sections/{$section->id}", ['show' => true])->assertRedirect();

        $this->assertContains('Clases semanales', $this->calendarDays()['2026-08-05']);
    }

    public function test_admin_calendar_screen_lists_visible_events_and_toggles_them(): void
    {
        $admin = $this->admin();

        $listed = Event::create(['title' => 'Charla abierta', 'starts_at' => '2026-08-20', 'visible' => true]);
        $hidden = Event::create(['title' => 'Evento oculto', 'starts_at' => '2026-08-21', 'visible' => false, 'show_on_calendar' => true]);
        $undated = Event::create(['title' => 'Sin fecha', 'visible' => true]);

        $titles = collect($this->actingAs($admin)->get('/admin/calendar')->assertOk()->viewData('page')['props']['events'])
            ->pluck('title');

        $this->assertContains('Charla abierta', $titles);
        $this->assertContains('Sin fecha', $titles);
        $this->assertNotContains('Evento oculto', $titles);

        $this->actingAs($admin)->patch("/admin/calendar/events/{$listed->id}", ['show' => true])->assertRedirect();
        $this->assertTrue($listed->fresh()->show_on_calendar);

        $this->actingAs($admin)->patch("/admin/calendar/events/{$listed->id}", ['show' => false])->assertRedirect();
        $this->assertFalse($listed->fresh()->show_on_calendar);
    }

    public function test_the_master_toggle_covers_the_cards_and_the_events_at_once(): void
    {
        $admin = $this->admin();

        $listed = Event::create(['title' => 'Charla abierta', 'starts_at' => '2026-08-20', 'visible' => true]);
        $hidden = Event::create(['title' => 'Evento oculto', 'starts_at' => '2026-08-21', 'visible' => false, 'show_on_calendar' => true]);
        $undated = Event::create(['title' => 'Sin fecha', 'visible' => true]);

        // Una ficha oculta: no se lista, así que el masivo no debe tocarla.
        $hiddenCard = Section::whereHas('page', fn ($query) => $query->where('slug', 'cursos-y-retiros'))
            ->where('key', 'curso')
            ->firstOrFail();
        $hiddenCard->update(['visible' => false]);

        // Destildar todo: alcanza a las fichas y a los eventos listados.
        $this->actingAs($admin)->patch('/admin/calendar', ['show' => false])->assertRedirect()->assertSessionHas('success');

        $this->assertSame(0, Section::where('type', 'class_info')->visible()->onCalendar()->count());
        $this->assertFalse($listed->fresh()->show_on_calendar);
        // Y no le pisa la marca a lo que no se lista.
        $this->assertTrue($hidden->fresh()->show_on_calendar);
        $this->assertTrue($hiddenCard->fresh()->show_on_calendar);
        $this->assertFalse($undated->fresh()->show_on_calendar);

        // Marcar todo, de vuelta.
        $this->actingAs($admin)->patch('/admin/calendar', ['show' => true])->assertRedirect();

        $this->assertTrue($listed->fresh()->show_on_calendar);
        $this->assertGreaterThan(0, Section::where('type', 'class_info')->visible()->onCalendar()->count());
        $this->assertFalse($undated->fresh()->show_on_calendar);
    }

    public function test_admin_calendar_screen_needs_a_session(): void
    {
        $event = Event::create(['title' => 'Charla abierta', 'starts_at' => '2026-08-20', 'visible' => true]);
        // Una ficha real, no la plantilla: esa está siempre fuera del calendario.
        $section = Section::where('type', 'class_info')->where('is_template', false)->firstOrFail();

        $this->get('/admin/calendar')->assertRedirect('/login');
        $this->patch('/admin/calendar', ['show' => true])->assertRedirect('/login');
        $this->patch("/admin/calendar/events/{$event->id}", ['show' => true])->assertRedirect('/login');
        $this->patch("/admin/calendar/sections/{$section->id}", ['show' => false])->assertRedirect('/login');

        $this->assertFalse($event->fresh()->show_on_calendar);
        $this->assertTrue($section->fresh()->show_on_calendar);
    }

    public function test_event_form_stores_the_new_calendar_fields_and_rejects_an_inverted_range(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/events', [
            'title' => 'Retiro de primavera',
            'starts_at' => '2026-09-25',
            'ends_at' => '2026-09-27',
            'start_time' => '10:00',
            'end_time' => '17:00',
            'visible' => true,
            'show_on_calendar' => true,
        ])->assertRedirect();

        $event = Event::where('title', 'Retiro de primavera')->firstOrFail();

        $this->assertSame('2026-09-27', $event->ends_at->toDateString());
        $this->assertSame('10:00', $event->start_time);
        $this->assertSame('17:00', $event->end_time);
        $this->assertTrue($event->show_on_calendar);

        $this->actingAs($admin)->put("/admin/events/{$event->id}", [
            'title' => 'Retiro de primavera',
            'starts_at' => '2026-09-25',
            'ends_at' => '2026-09-20',
        ])->assertSessionHasErrors('ends_at');

        $this->actingAs($admin)->put("/admin/events/{$event->id}", [
            'title' => 'Retiro de primavera',
            'starts_at' => '2026-09-25',
            'start_time' => '17:00',
            'end_time' => '10:00',
        ])->assertSessionHasErrors('end_time');
    }

    public function test_voluntariado_gets_its_header_and_cover_without_touching_the_rest(): void
    {
        $page = Page::where('slug', 'voluntariado')->firstOrFail();

        // Estado "producción": las dos secciones no existen y el resto está editado.
        $page->sections()->whereIn('key', ['titulo', 'banner'])->delete();

        $intro = $page->sections()->where('key', 'intro')->firstOrFail();
        $intro->update(['content' => [...$intro->content, 'heading' => 'Editado por el dueño'], 'visible' => false]);

        $galeria = $page->sections()->where('key', 'galeria')->firstOrFail();
        $galeria->update(['content' => [...$galeria->content, 'images' => ['sections/subida-a-mano.png']]]);

        $this->seed(VoluntariadoPortadaSeeder::class);

        // Entran primero el encabezado y después la portada, arriba de todo.
        $this->assertSame(
            ['titulo', 'banner', 'intro', 'galeria'],
            $page->sections()->orderBy('position')->pluck('key')->take(4)->all(),
        );

        $titulo = $page->sections()->where('key', 'titulo')->firstOrFail();
        $banner = $page->sections()->where('key', 'banner')->firstOrFail();

        $this->assertSame('page_header', $titulo->type);
        $this->assertSame('VOLUNTARIADO', $titulo->content['heading']);
        $this->assertSame('hero', $banner->type);
        $this->assertStringStartsWith('seed/', $banner->content['image']);
        $this->assertTrue($titulo->visible);
        $this->assertTrue($banner->visible);

        // Y lo editado quedó intacto, sólo corrido dos lugares.
        $this->assertSame('Editado por el dueño', $intro->fresh()->content['heading']);
        $this->assertFalse($intro->fresh()->visible);
        $this->assertSame(['sections/subida-a-mano.png'], $galeria->fresh()->content['images']);

        // Repetible: no duplica ni vuelve a correr las posiciones.
        $positions = $page->sections()->orderBy('position')->pluck('position', 'key')->all();
        $this->seed(VoluntariadoPortadaSeeder::class);

        $this->assertSame($positions, $page->sections()->orderBy('position')->pluck('position', 'key')->all());
        $this->assertSame(1, $page->sections()->where('key', 'banner')->count());

        // La página sigue respondiendo con las secciones nuevas.
        $keys = [];
        $this->get('/voluntariado')->assertOk()->assertInertia(function (AssertableInertia $p) use (&$keys) {
            $keys = collect($p->toArray()['props']['sections'])->pluck('key')->all();
        });

        $this->assertSame(['titulo', 'banner'], array_slice($keys, 0, 2));
    }

    public function test_footer_gets_the_app_card_without_touching_the_other_resources(): void
    {
        // Estado "produccion": las tres tarjetas de siempre, con una editada a mano.
        $previas = collect(json_decode(Setting::get('footer_resources', '[]'), true))
            ->reject(fn ($card) => $card['url'] === 'https://kadampa.org/app')
            ->values()
            ->all();

        $previas[1]['text'] = 'lo cambio el dueno';
        Setting::set('footer_resources', json_encode($previas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $this->seed(AppKadampaSeeder::class);

        $cards = json_decode(Setting::get('footer_resources', '[]'), true);

        $this->assertCount(4, $cards);
        $this->assertSame(['EL BUDISMO KADAMPA', 'COMO TRANSFORMAR TU VIDA', 'BUDISMO MODERNO'], [
            $cards[0]['title'],
            str_replace('Ó', 'O', $cards[1]['title']),
            $cards[2]['title'],
        ]);
        $this->assertSame('lo cambio el dueno', $cards[1]['text']);

        // La nueva va al final, con la imagen copiada a storage.
        $this->assertSame('https://kadampa.org/app', $cards[3]['url']);
        $this->assertSame('seed/shared/logo-qr-app.webp', $cards[3]['image']);
        $this->assertStringContainsString('APP DE MEDITACI', $cards[3]['title']);
        $this->assertTrue(Storage::disk('public')->exists('seed/shared/logo-qr-app.webp'));

        // Repetible: no la duplica ni pisa lo editado.
        $this->seed(AppKadampaSeeder::class);

        $repetido = json_decode(Setting::get('footer_resources', '[]'), true);

        $this->assertCount(4, $repetido);
        $this->assertSame('lo cambio el dueno', $repetido[1]['text']);

        // Y el pie de cualquier pagina la recibe.
        $this->get('/')->assertOk()->assertInertia(
            fn (AssertableInertia $page) => $page->where('settings.footer_resources.3.url', 'https://kadampa.org/app'),
        );
    }

    public function test_class_schedule_is_built_from_the_calendar_dates_when_it_is_empty(): void
    {
        $admin = $this->admin();
        $section = Section::whereHas('page', fn ($query) => $query->where('slug', 'clases-semanales'))
            ->where('key', 'clase-principal')
            ->firstOrFail();

        /** El horario que recibe la vista pública de esa ficha. */
        $published = function () {
            $text = null;

            $this->get('/clases-semanales')->assertOk()->assertInertia(function (AssertableInertia $page) use (&$text) {
                $text = collect($page->toArray()['props']['sections'])->firstWhere('key', 'clase-principal')['content']['schedule'];
            });

            return $text;
        };

        // Con texto a mano, se publica ese.
        $this->assertSame('Miércoles de 19 a 20.15 hs', $published());

        // Vaciándolo, se arma con las fechas del calendario (miércoles 19:00-20:15).
        $this->actingAs($admin)->put("/admin/sections/{$section->id}", [
            'content' => [...$section->content, 'schedule' => ''],
        ])->assertRedirect();

        $this->assertNull($section->fresh()->content['schedule']);
        $this->assertSame('Miércoles de 19 a 20.15 hs', $published());

        // Y dos días con el mismo horario se dicen en una sola frase.
        $this->actingAs($admin)->put("/admin/sections/{$section->id}", [
            'content' => [
                ...$section->content,
                'schedule' => '',
                'occurrences' => [
                    ['type' => 'weekly', 'weekday' => 2, 'start' => '18:00', 'end' => '18:30'],
                    ['type' => 'weekly', 'weekday' => 4, 'start' => '18:00', 'end' => '18:30'],
                ],
            ],
        ])->assertRedirect();

        $this->assertSame('Martes y jueves de 18 a 18.30 hs', $published());

        // Sin fechas ni texto no se inventa nada.
        $this->actingAs($admin)->put("/admin/sections/{$section->id}", [
            'content' => [...$section->content, 'schedule' => '', 'occurrences' => []],
        ])->assertRedirect();

        $this->assertNull($published());
    }

    public function test_the_section_form_shows_the_schedule_that_would_be_published(): void
    {
        $admin = $this->admin();
        $section = Section::whereHas('page', fn ($query) => $query->where('slug', 'cursos-y-retiros'))
            ->where('key', 'curso')
            ->firstOrFail();

        // La plantilla de cursos no tiene fechas cargadas: la pista lo dice.
        $hints = $this->actingAs($admin)->get("/admin/sections/{$section->id}/edit")
            ->assertOk()->viewData('page')['props']['hints'];

        $this->assertStringContainsString('Fechas para el calendario', $hints['schedule']);

        // Con una fecha fija cargada, la pista muestra el texto exacto.
        $section->update(['content' => [...$section->content, 'occurrences' => [
            ['type' => 'date', 'weekday' => null, 'date' => '2026-08-08', 'from' => null, 'until' => null, 'start' => '16:00', 'end' => '19:00', 'label' => null],
        ]]]);

        $hints = $this->actingAs($admin)->get("/admin/sections/{$section->id}/edit")
            ->viewData('page')['props']['hints'];

        $this->assertStringContainsString('Sábado 8 de agosto de 16 a 19 hs', $hints['schedule']);
    }

    public function test_class_card_can_have_an_anchor_to_link_straight_to_it(): void
    {
        $admin = $this->admin();
        $page = Page::where('slug', 'cursos-y-retiros')->firstOrFail();
        $section = $page->sections()->where('key', 'curso')->firstOrFail();

        $this->actingAs($admin)->put("/admin/sections/{$section->id}", [
            'content' => [...$section->content, 'anchor' => 'retiro-de-agosto'],
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame('retiro-de-agosto', $section->fresh()->content['anchor']);

        /**
         * El ancla que recibe la vista. El atributo id lo pinta Vue en el navegador,
         * así que en el HTML del servidor sólo está el prop — el id renderizado y el
         * salto se verifican con el navegador.
         */
        $publicada = function () {
            $valor = null;

            $this->get('/cursos-y-retiros')->assertOk()->assertInertia(function (AssertableInertia $page) use (&$valor) {
                $valor = collect($page->toArray()['props']['sections'])->firstWhere('key', 'curso')['content']['anchor'] ?? null;
            });

            return $valor;
        };

        $section->fresh()->update(['visible' => true]);
        $this->assertSame('retiro-de-agosto', $publicada());

        // Un número suelto también sirve, que es el caso más simple.
        $this->actingAs($admin)->put("/admin/sections/{$section->id}", [
            'content' => [...$section->content, 'anchor' => '4'],
        ])->assertRedirect();

        $this->assertSame('4', $section->fresh()->content['anchor']);
        $this->assertSame('4', $publicada());

        // Lo que no sirve dentro de una URL se rechaza.
        foreach (['con espacios', 'con#numeral', 'acentuadó', '-empieza-con-guion', 'barra/adentro'] as $malo) {
            $this->actingAs($admin)->put("/admin/sections/{$section->id}", [
                'content' => [...$section->content, 'anchor' => $malo],
            ])->assertSessionHasErrors('content.anchor');
        }

        $this->assertSame('4', $section->fresh()->content['anchor']);

        // Y se puede vaciar: el campo es opcional.
        $this->actingAs($admin)->put("/admin/sections/{$section->id}", [
            'content' => [...$section->content, 'anchor' => ''],
        ])->assertRedirect();

        $this->assertNull($section->fresh()->content['anchor']);
    }

    public function test_two_sections_of_a_page_cannot_share_an_anchor(): void
    {
        $admin = $this->admin();
        $page = Page::where('slug', 'cursos-y-retiros')->firstOrFail();

        $primera = $page->sections()->where('key', 'curso')->firstOrFail();
        $primera->update(['content' => [...$primera->content, 'anchor' => 'retiro']]);

        // Otra sección de la misma página no puede repetirla: el navegador salta a
        // la primera y la segunda queda inalcanzable sin aviso.
        $segunda = $page->sections()->where('key', 'detalles-clase')->firstOrFail();

        $this->actingAs($admin)->put("/admin/sections/{$segunda->id}", [
            'content' => [...$segunda->content, 'anchor' => 'retiro'],
        ])->assertSessionHasErrors('content.anchor');

        // Guardar la misma sección con su propia ancla no se choca consigo misma.
        $this->actingAs($admin)->put("/admin/sections/{$primera->id}", [
            'content' => [...$primera->content, 'anchor' => 'retiro'],
        ])->assertRedirect()->assertSessionHasNoErrors();

        // Y en otra página el mismo ancla es válido.
        $otra = Section::whereHas('page', fn ($query) => $query->where('slug', 'clases-semanales'))
            ->where('key', 'clase-principal')
            ->firstOrFail();

        $this->actingAs($admin)->put("/admin/sections/{$otra->id}", [
            'content' => [...$otra->content, 'anchor' => 'retiro'],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('retiro', $otra->fresh()->content['anchor']);
    }

    public function test_class_card_can_name_one_or_more_teachers(): void
    {
        $admin = $this->admin();

        /** El campo tal como lo recibe la vista de esa ficha. */
        $published = function (string $slug, string $key) {
            $value = null;

            $this->get('/'.$slug)->assertOk()->assertInertia(function (AssertableInertia $page) use ($key, &$value) {
                $value = collect($page->toArray()['props']['sections'])->firstWhere('key', $key)['content']['teachers'] ?? null;
            });

            return $value;
        };

        // Sembrado: el maestro salió del título y vive en su propio campo.
        $this->assertSame('Kelsang Panchen', $published('clases-semanales', 'clase-principal'));
        $this->get('/clases-semanales')->assertOk()->assertSee('Libertad emocional');

        $section = Section::whereHas('page', fn ($query) => $query->where('slug', 'cursos-y-retiros'))
            ->where('key', 'curso')
            ->firstOrFail();

        // Varios, separados por coma, con espacios de más.
        $this->actingAs($admin)->put("/admin/sections/{$section->id}", [
            'content' => [...$section->content, 'teachers' => 'Kelsang Dema ,  Gen Kelsang Chikuo '],
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame('Kelsang Dema ,  Gen Kelsang Chikuo', $section->fresh()->content['teachers']);

        // Y se puede vaciar: el campo es opcional.
        $this->actingAs($admin)->put("/admin/sections/{$section->id}", [
            'content' => [...$section->content, 'teachers' => ''],
        ])->assertRedirect();

        $this->assertNull($section->fresh()->content['teachers']);

        // Más de 255 no entra (es un campo de texto del registro).
        $this->actingAs($admin)->put("/admin/sections/{$section->id}", [
            'content' => [...$section->content, 'teachers' => str_repeat('a', 256)],
        ])->assertSessionHasErrors('content.teachers');
    }

    public function test_class_card_can_carry_the_classes_of_the_cycle(): void
    {
        $admin = $this->admin();
        $section = Section::whereHas('page', fn ($query) => $query->where('slug', 'clases-semanales'))
            ->where('key', 'clase-principal')
            ->firstOrFail();

        // El campo es nuevo, así que las fichas sembradas no lo tienen: sin él la
        // ficha se muestra igual, sólo que el afiche no gira.
        $this->assertArrayNotHasKey('cycle', $section->content);
        $this->get('/clases-semanales')->assertOk();

        // Con un renglón en blanco en medio y un salto al final, que es como queda
        // un textarea escrito a mano.
        $this->actingAs($admin)->put("/admin/sections/{$section->id}", [
            'content' => [...$section->content, 'cycle' => "Clase 1 - Qué es la meditación\n\nClase 2 - La mente apacible\n"],
        ])->assertRedirect()->assertSessionHas('success');

        // TrimStrings recorta el salto del final; el resto se guarda tal cual y las
        // líneas vacías las descarta lines() al renderizar.
        $stored = $section->fresh()->content['cycle'];
        $this->assertSame("Clase 1 - Qué es la meditación\n\nClase 2 - La mente apacible", $stored);

        // Y llega a la página, que es donde el componente lo parte en renglones.
        $content = [];
        $this->get('/clases-semanales')->assertOk()->assertInertia(function (AssertableInertia $page) use (&$content) {
            $content = collect($page->toArray()['props']['sections'])->firstWhere('key', 'clase-principal')['content'];
        });

        $this->assertSame($stored, $content['cycle']);
    }

    public function test_event_date_text_is_built_from_its_date_and_time(): void
    {
        $this->travelTo(Carbon::parse('2026-08-15 12:00', EventCalendar::TIMEZONE));

        $cases = [
            // [starts_at, ends_at, start_time, end_time, esperado]
            ['2026-08-29', null, '10:00', '17:30', 'Sábado 29 de agosto de 10 a 17.30 hs'],
            ['2026-08-08', null, '17:00', '19:00', 'Sábado 8 de agosto de 17 a 19 hs'],
            ['2026-08-08', null, '17:00', null, 'Sábado 8 de agosto a las 17 hs'],
            ['2026-08-08', null, null, null, 'Sábado 8 de agosto'],
            ['2026-08-28', '2026-08-30', '10:00', '17:30', 'Del 28 al 30 de agosto de 10 a 17.30 hs'],
            ['2026-08-30', '2026-09-01', null, null, 'Del 30 de agosto al 1 de septiembre'],
            // Otro año: se aclara, porque "8 de agosto" solo sería ambiguo.
            ['2027-08-08', null, '17:00', null, 'Domingo 8 de agosto de 2027 a las 17 hs'],
        ];

        foreach ($cases as [$start, $end, $from, $to, $expected]) {
            $event = new Event(['starts_at' => $start, 'ends_at' => $end, 'start_time' => $from, 'end_time' => $to]);

            $this->assertSame($expected, $event->date_auto, "Fecha mal armada para $start");
            $this->assertSame($expected, $event->date_label);
        }

        // Sin fecha de inicio no hay nada que armar.
        $this->assertNull((new Event(['title' => 'Sin fecha']))->date_auto);

        // Y el texto a mano manda sobre el automático.
        $manual = new Event(['starts_at' => '2026-08-29', 'start_time' => '10:00', 'date_text' => 'Consultar horario']);
        $this->assertSame('Sábado 29 de agosto a las 10 hs', $manual->date_auto);
        $this->assertSame('Consultar horario', $manual->date_label);
    }

    public function test_the_public_pages_show_the_built_date_when_there_is_no_manual_text(): void
    {
        $this->travelTo(Carbon::parse('2026-08-15 12:00', EventCalendar::TIMEZONE));

        $event = Event::create([
            'title' => 'Charla abierta',
            'starts_at' => '2026-08-20',
            'start_time' => '17:00',
            'end_time' => '19:00',
            'visible' => true,
            'show_on_home' => true,
        ]);

        $label = null;
        $this->get('/')->assertOk()->assertInertia(function (AssertableInertia $page) use (&$label) {
            $label = collect($page->toArray()['props']['homeEvents'])->firstWhere('title', 'Charla abierta')['date_label'];
        });

        $this->assertSame('Jueves 20 de agosto de 17 a 19 hs', $label);

        // Y en el calendario, el texto armado es el que se usa cuando falta la hora.
        $event->update(['date_text' => 'Jueves 20, a confirmar']);

        $this->get('/')->assertOk()->assertInertia(function (AssertableInertia $page) use (&$label) {
            $label = collect($page->toArray()['props']['homeEvents'])->firstWhere('title', 'Charla abierta')['date_label'];
        });

        $this->assertSame('Jueves 20, a confirmar', $label);
    }

    public function test_event_can_have_a_different_url_for_its_image_than_for_its_button(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/events', [
            'title' => 'Retiro de primavera',
            'starts_at' => '2026-09-25',
            'cta_label' => 'INSCRIPCIÓN',
            'cta_url' => 'https://forms.gle/inscripcion',
            'image_url' => 'https://instagram.com/p/afiche',
            'visible' => true,
            'show_on_home' => true,
        ])->assertRedirect();

        $event = Event::where('title', 'Retiro de primavera')->firstOrFail();

        $this->assertSame('https://instagram.com/p/afiche', $event->image_url);
        $this->assertSame('https://forms.gle/inscripcion', $event->cta_url);

        // Y las dos llegan a la tira de la home, que es la que las usa.
        $home = [];
        $this->get('/')->assertOk()->assertInertia(function (AssertableInertia $page) use (&$home) {
            $home = collect($page->toArray()['props']['homeEvents'])->firstWhere('title', 'Retiro de primavera');
        });

        $this->assertSame('https://instagram.com/p/afiche', $home['image_url']);
        $this->assertSame('https://forms.gle/inscripcion', $home['cta_url']);

        // Vaciarla la deja en null, y ahí la imagen vuelve a seguir al botón.
        $this->actingAs($admin)->put("/admin/events/{$event->id}", [
            'title' => 'Retiro de primavera',
            'starts_at' => '2026-09-25',
            'cta_url' => 'https://forms.gle/inscripcion',
            'image_url' => '',
        ])->assertRedirect();

        $this->assertNull($event->fresh()->image_url);
    }

    public function test_calendario_seeder_publishes_the_page_without_touching_the_classes(): void
    {
        Page::where('slug', 'calendario')->firstOrFail()->delete();

        // Producción: las fichas están editadas y con horarios propios, así que el
        // seeder no puede cargarles fechas del archivo de datos (serían otras).
        $clase = Section::whereHas('page', fn ($query) => $query->where('slug', 'clases-semanales'))
            ->where('key', 'clase-principal')
            ->firstOrFail();

        $clase->update([
            'content' => [...$clase->content, 'occurrences' => [], 'schedule' => 'Lunes de agosto de 19:00 a 20:30 hs'],
            'visible' => false,
            'position' => 9,
        ]);

        $this->assertNotContains('calendario', $this->navSlugs());

        $this->seed(CalendarioSeeder::class);

        $page = Page::where('slug', 'calendario')->firstOrFail();

        $this->assertContains('calendario', $this->navSlugs());
        $this->assertSame(['titulo', 'calendario'], $page->sections()->orderBy('position')->pluck('key')->all());
        $this->assertSame('event_calendar', $page->sections()->where('key', 'calendario')->firstOrFail()->type);

        // La ficha editada quedó intacta: mismo horario, sin fechas inventadas,
        // y conserva su visibilidad y su posición.
        $fresh = $clase->fresh();
        $this->assertSame([], $fresh->content['occurrences']);
        $this->assertSame('Lunes de agosto de 19:00 a 20:30 hs', $fresh->content['schedule']);
        $this->assertFalse($fresh->visible);
        $this->assertSame(9, $fresh->position);
    }

    public function test_production_dates_seeder_fills_only_the_empty_cards(): void
    {
        $this->travelTo(Carbon::parse('2026-08-15 12:00', EventCalendar::TIMEZONE));

        $clase = Section::whereHas('page', fn ($query) => $query->where('slug', 'clases-semanales'))
            ->where('key', 'clase-principal')
            ->firstOrFail();

        // Estado de producción: el horario es otro y el campo está vacío.
        $clase->update(['content' => [
            ...$clase->content,
            'schedule' => 'Lunes de agosto de 19:00 a 20:30 hs',
            'occurrences' => [],
        ]]);

        // Y una ficha que el dueño ya completó a mano, que no se debe pisar.
        $gratuitas = Section::whereHas('page', fn ($query) => $query->where('slug', 'clases-semanales'))
            ->where('key', 'meditaciones-gratuitas')
            ->firstOrFail();
        $gratuitas->update(['content' => [...$gratuitas->content, 'occurrences' => [
            ['type' => 'weekly', 'weekday' => 6, 'date' => null, 'from' => null, 'until' => null, 'start' => '09:00', 'end' => '10:00', 'label' => null],
        ]]]);

        $this->seed(CalendarioFechasSeeder::class);

        $fresh = $clase->fresh()->content;

        $this->assertCount(1, $fresh['occurrences']);
        $this->assertSame(1, $fresh['occurrences'][0]['weekday']);
        $this->assertSame('19:00', $fresh['occurrences'][0]['start']);
        $this->assertSame('20:30', $fresh['occurrences'][0]['end']);
        $this->assertSame('2026-08-31', $fresh['occurrences'][0]['until']);
        // El texto de la tarjeta sigue intacto.
        $this->assertSame('Lunes de agosto de 19:00 a 20:30 hs', $fresh['schedule']);

        // La ficha ya cargada quedó como estaba.
        $this->assertSame(6, $gratuitas->fresh()->content['occurrences'][0]['weekday']);

        // La clase cae los lunes de agosto.
        $days = $this->calendarDays();
        $this->assertContains('Clases semanales', $days['2026-08-03'] ?? []);
        $this->assertContains('Clases semanales', $days['2026-08-24'] ?? []);

        // Y en septiembre ya no, por la vigencia hasta el 31 de agosto.
        $this->travelTo(Carbon::parse('2026-09-07 12:00', EventCalendar::TIMEZONE));
        $this->assertNotContains('Clases semanales', $this->calendarDays()['2026-09-07'] ?? []);

        // Repetirlo no cambia nada.
        $this->seed(CalendarioFechasSeeder::class);
        $this->assertCount(1, $clase->fresh()->content['occurrences']);
        $this->assertSame(6, $gratuitas->fresh()->content['occurrences'][0]['weekday']);
    }

    public function test_production_dates_seeder_skips_cards_that_do_not_exist(): void
    {
        // Las fichas clonadas (curso-copia…) existen en producción pero no en el
        // seed, así que el seeder tiene que saltearlas sin romperse.
        Section::whereHas('page', fn ($query) => $query->where('slug', 'cursos-y-retiros'))
            ->where('key', 'curso')
            ->firstOrFail()
            ->delete();

        $this->seed(CalendarioFechasSeeder::class);

        $this->assertSame(0, Section::whereHas('page', fn ($query) => $query->where('slug', 'cursos-y-retiros'))
            ->where('key', 'curso-copia')->count());
    }

    public function test_a_class_without_calendar_dates_simply_does_not_show_up(): void
    {
        $this->travelTo(Carbon::parse('2026-08-15 12:00', EventCalendar::TIMEZONE));

        Section::where('type', 'class_info')->get()->each(
            fn ($section) => $section->update(['content' => [...$section->content, 'occurrences' => []]]),
        );

        $calendar = $this->calendar();

        $this->assertSame([], $calendar['sources']);
        $this->assertSame([], $this->calendarDays());
        // La grilla sigue ahí, con su mes y su aviso de vacío.
        $this->assertSame('agosto de 2026', $calendar['label']);
        $this->assertCount(6, $calendar['weeks']);
    }
}
