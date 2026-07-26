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
        $page->update(['visible' => false]);

        $this->get('/voluntariado')->assertNotFound();
    }

    public function test_hidden_section_disappears_from_the_public_page(): void
    {
        $section = Section::whereHas('page', fn ($q) => $q->where('slug', 'home'))
            ->where('key', 'testimonio')->firstOrFail();

        $this->get('/')->assertSee('Mariel Aguirre');

        $section->update(['visible' => false]);

        $this->get('/')->assertDontSee('Mariel Aguirre');
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
}
