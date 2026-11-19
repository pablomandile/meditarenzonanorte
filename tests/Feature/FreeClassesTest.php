<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Section;
use App\Models\User;
use App\Support\EventCalendar;
use Carbon\Carbon;
use Database\Seeders\ContentSeeder;
use Database\Seeders\GratisEspejoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * La casilla "Gratuito" de las fichas de clase: la ficha tildada aparece también
 * en /gratis, leída del original, en el lugar del bloque 'free_classes'.
 */
class FreeClassesTest extends TestCase
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

    private function ficha(string $slug = 'clases-semanales', string $key = 'clase-principal'): Section
    {
        return Section::whereHas('page', fn ($q) => $q->where('slug', $slug))->where('key', $key)->firstOrFail();
    }

    private function markFree(Section $section, bool $free = true, array $extra = []): void
    {
        $section->update(['content' => [...$section->content, 'free' => $free, ...$extra]]);
    }

    /** @return array<int, array<string, mixed>> */
    private function gratisSections(): array
    {
        $sections = [];
        $this->get('/gratis')->assertOk()->assertInertia(function (AssertableInertia $page) use (&$sections) {
            $sections = $page->toArray()['props']['sections'];
        });

        return $sections;
    }

    public function test_the_checkbox_is_saved_as_a_boolean(): void
    {
        $admin = $this->admin();
        $ficha = $this->ficha();

        $this->actingAs($admin)->get("/admin/sections/{$ficha->id}/edit")->assertInertia(
            fn (AssertableInertia $page) => $page->where(
                'fields',
                fn ($fields) => collect($fields)->firstWhere('key', 'free')['type'] === 'checkbox',
            ),
        );

        // Del FormData llega "1"/"0".
        $this->actingAs($admin)->put("/admin/sections/{$ficha->id}", ['content' => [...$ficha->content, 'free' => '1']])
            ->assertRedirect()->assertSessionHas('success');
        $this->assertTrue($ficha->fresh()->content['free']);

        $this->actingAs($admin)->put("/admin/sections/{$ficha->id}", ['content' => [...$ficha->content, 'free' => '0']])
            ->assertRedirect();
        $this->assertFalse($ficha->fresh()->content['free']);

        $this->actingAs($admin)->put("/admin/sections/{$ficha->id}", ['content' => [...$ficha->content, 'free' => 'quizás']])
            ->assertSessionHasErrors('content.free');
    }

    public function test_a_free_card_from_another_page_shows_up_in_gratis_where_the_block_is(): void
    {
        $ficha = $this->ficha();

        // Destildada: no está.
        $this->assertNotContains($ficha->id, array_column($this->gratisSections(), 'id'));

        $this->markFree($ficha);
        $sections = $this->gratisSections();
        $ids = array_column($sections, 'id');

        $this->assertContains($ficha->id, $ids);
        // El bloque en sí no se publica: en su lugar van las fichas.
        $this->assertNotContains('free_classes', array_column($sections, 'type'));

        $mirror = collect($sections)->firstWhere('id', $ficha->id);
        $this->assertSame('class_info', $mirror['type']);
        $this->assertSame($ficha->content['heading'], $mirror['content']['heading']);

        // Va después de las dos fichas propias de Gratis, donde está el bloque.
        $own = [$this->ficha('gratis', 'oferta-miercoles-jueves')->id, $this->ficha('gratis', 'oferta-sabados')->id];
        $this->assertSame([...$own, $ficha->id], array_slice($ids, 1, 3));

        // Editar el original cambia lo que se ve en Gratis.
        $ficha->update(['content' => [...$ficha->fresh()->content, 'heading' => 'Título nuevo']]);
        $this->assertSame('Título nuevo', collect($this->gratisSections())->firstWhere('id', $ficha->id)['content']['heading']);

        // Destildarla la saca.
        $this->markFree($ficha->fresh(), false);
        $this->assertNotContains($ficha->id, array_column($this->gratisSections(), 'id'));
    }

    public function test_hidden_cards_templates_and_hidden_pages_are_not_mirrored(): void
    {
        $ficha = $this->ficha();
        $this->markFree($ficha);
        $ficha->update(['visible' => false]);
        $this->assertNotContains($ficha->id, array_column($this->gratisSections(), 'id'));

        $template = Section::whereHas('page', fn ($q) => $q->where('slug', 'cursos-y-retiros'))->where('is_template', true)->firstOrFail();
        $this->markFree($template);
        $this->assertNotContains($template->id, array_column($this->gratisSections(), 'id'));

        $other = $this->ficha();
        $other->update(['visible' => true]);
        Page::where('slug', 'clases-semanales')->update(['visible' => false]);
        $this->assertNotContains($other->id, array_column($this->gratisSections(), 'id'));
    }

    public function test_the_cards_of_gratis_itself_are_not_duplicated(): void
    {
        $own = $this->ficha('gratis', 'oferta-sabados');
        $this->markFree($own);

        $ids = array_column($this->gratisSections(), 'id');

        $this->assertSame(1, count(array_keys($ids, $own->id)));
    }

    public function test_a_free_card_without_price_publishes_gratuito(): void
    {
        $ficha = $this->ficha();
        $this->markFree($ficha, true, ['price' => null]);

        $this->get('/clases-semanales')->assertInertia(fn (AssertableInertia $page) => $page->where(
            'sections',
            fn ($sections) => collect($sections)->firstWhere('id', $ficha->id)['content']['price'] === 'Gratuito',
        ));

        // Con precio escrito, manda el escrito.
        $this->markFree($ficha->fresh(), true, ['price' => 'Aporte voluntario']);
        $this->assertSame('Aporte voluntario', collect($this->gratisSections())->firstWhere('id', $ficha->id)['content']['price']);
    }

    public function test_the_calendar_does_not_duplicate_a_free_card(): void
    {
        $this->travelTo(Carbon::parse('2026-08-15 12:00', EventCalendar::TIMEZONE));
        $ficha = $this->ficha();
        $ficha->update(['show_on_calendar' => true]);

        $count = fn () => collect(EventCalendar::forMonth('2026-08')['weeks'])
            ->flatMap(fn ($week) => array_filter($week['days']))
            ->flatMap(fn ($day) => $day['activities'])
            ->filter(fn ($activity) => str_starts_with($activity['key'], "cls-{$ficha->id}-"))
            ->count();

        $before = $count();
        $this->assertGreaterThan(0, $before);

        $this->markFree($ficha->fresh());
        $this->assertSame($before, $count());
    }

    public function test_the_block_has_no_form_and_shows_as_automatic_in_the_admin(): void
    {
        $admin = $this->admin();
        $gratis = Page::where('slug', 'gratis')->firstOrFail();
        $block = Section::where('page_id', $gratis->id)->where('type', 'free_classes')->firstOrFail();

        $this->actingAs($admin)->get("/admin/sections/{$block->id}/edit")->assertRedirect(route('admin.pages.show', $gratis));

        $this->actingAs($admin)->get(route('admin.pages.show', $gratis))->assertInertia(fn (AssertableInertia $page) => $page->where(
            'sections',
            fn ($sections) => collect($sections)->firstWhere('id', $block->id)['auto'] === true,
        ));
    }

    public function test_the_seeder_adds_the_block_to_an_existing_gratis_page_once(): void
    {
        $gratis = Page::where('slug', 'gratis')->firstOrFail();
        Section::where('page_id', $gratis->id)->where('type', 'free_classes')->delete();

        $this->seed(GratisEspejoSeeder::class);
        $this->seed(GratisEspejoSeeder::class);

        $keys = Section::where('page_id', $gratis->id)->orderBy('position')->pluck('key')->all();

        $this->assertSame(1, count(array_keys($keys, 'gratuitas-de-otras-paginas')));
        $this->assertSame('gratuitas-de-otras-paginas', $keys[array_search('oferta-sabados', $keys) + 1]);
    }
}
