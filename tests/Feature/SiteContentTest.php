<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Section;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\ContentSeeder;
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

        foreach (['clases-semanales', 'eventos-especiales', 'gratis', 'quienes-somos', 'voluntariado'] as $slug) {
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

        (new ContentSeeder())->seedMissingSection('clases-semanales', 'banner');

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
        (new ContentSeeder())->seedMissingSection('clases-semanales', 'banner');

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

    public function test_template_section_is_seeded_hidden_so_the_public_page_ignores_it(): void
    {
        $page = Page::where('slug', 'cursos-y-retiros')->firstOrFail();
        $page->sections()->where('key', 'curso')->delete();

        (new ContentSeeder())->seedMissingSection('cursos-y-retiros', 'curso', visible: false);

        $ficha = $page->sections()->where('key', 'curso')->firstOrFail();
        $banner = $page->sections()->where('key', 'banner')->firstOrFail();

        $this->assertSame('class_info', $ficha->type);
        $this->assertFalse($ficha->visible, 'la plantilla entra oculta');
        $this->assertSame($banner->position + 1, $ficha->position);

        // Tiene los mismos campos que una ficha de clase.
        foreach (['heading', 'body', 'schedule', 'location', 'price', 'cta_label', 'cta_url'] as $field) {
            $this->assertArrayHasKey($field, $ficha->content);
        }

        // Y por estar oculta, la página pública no la renderiza.
        $keys = [];
        $this->get('/cursos-y-retiros')->assertOk()->assertInertia(function (AssertableInertia $p) use (&$keys) {
            $keys = collect($p->toArray()['props']['sections'])->pluck('key')->all();
        });

        $this->assertNotContains('curso', $keys);
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
}
