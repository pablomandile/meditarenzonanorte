<?php

namespace Tests\Feature;

use App\Models\Section;
use App\Models\Teacher;
use App\Models\User;
use App\Support\MediaLibrary;
use Database\Seeders\ContentSeeder;
use Database\Seeders\MaestrosPerfilesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * La descripción y la foto de los maestr@s de "Datos recurrentes": se editan en
 * el panel, se ven al tocar el nombre en una ficha de clase y las usan las
 * secciones "Maestro / persona" que eligen un maestr@.
 */
class TeacherProfilesTest extends TestCase
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

    private function ficha(): Section
    {
        return Section::whereHas('page', fn ($q) => $q->where('slug', 'clases-semanales'))->where('key', 'clase-principal')->firstOrFail();
    }

    private function person(string $key = 'directora'): Section
    {
        return Section::whereHas('page', fn ($q) => $q->where('slug', 'quienes-somos'))->where('key', $key)->firstOrFail();
    }

    /** @return array<string, mixed> */
    private function publicContent(string $slug, int $sectionId): array
    {
        $content = [];
        $this->get("/{$slug}")->assertOk()->assertInertia(function (AssertableInertia $page) use (&$content, $sectionId) {
            $content = collect($page->toArray()['props']['sections'])->firstWhere('id', $sectionId)['content'];
        });

        return $content;
    }

    public function test_admin_can_edit_a_teachers_description_and_photo(): void
    {
        $admin = $this->admin();
        $teacher = Teacher::create(['name' => 'Kelsang Panchen']);

        $this->actingAs($admin)->post("/admin/datos-recurrentes/maestros/{$teacher->id}", [
            '_method' => 'put',
            'name' => 'Kelsang Panchen',
            'description' => "Maestro residente.\n\nEnseña en Rosario.",
            'file' => UploadedFile::fake()->image('panchen.jpg'),
        ])->assertRedirect()->assertSessionHas('success');

        $teacher->refresh();
        $this->assertSame("Maestro residente.\n\nEnseña en Rosario.", $teacher->description);
        $this->assertStringStartsWith('teachers/', $teacher->photo);
        Storage::disk('public')->assertExists($teacher->photo);

        // La galería la cuenta como en uso.
        $this->assertSame(['Datos recurrentes: Kelsang Panchen'], MediaLibrary::usage()[$teacher->photo]);

        $this->actingAs($admin)->get('/admin/datos-recurrentes')->assertInertia(
            fn (AssertableInertia $page) => $page->where('teachers.0.description', "Maestro residente.\n\nEnseña en Rosario.")
                ->where('teachers.0.photo', $teacher->photo),
        );

        // Quitar la foto borra el archivo.
        $old = $teacher->photo;
        $this->actingAs($admin)->post("/admin/datos-recurrentes/maestros/{$teacher->id}", [
            '_method' => 'put',
            'name' => 'Kelsang Panchen',
            'description' => 'Maestro residente.',
        ])->assertRedirect();

        $this->assertNull($teacher->fresh()->photo);
        Storage::disk('public')->assertMissing($old);
    }

    public function test_a_photo_picked_from_the_gallery_is_copied_and_deleting_the_teacher_removes_it(): void
    {
        $admin = $this->admin();
        $teacher = Teacher::create(['name' => 'Kelsang Panchen']);
        Storage::disk('public')->put('sections/otra.jpg', 'x');

        $this->actingAs($admin)->post("/admin/datos-recurrentes/maestros/{$teacher->id}", [
            '_method' => 'put',
            'name' => 'Kelsang Panchen',
            'photo' => 'sections/otra.jpg',
        ])->assertRedirect();

        $photo = $teacher->fresh()->photo;
        $this->assertStringStartsWith('teachers/', $photo);
        Storage::disk('public')->assertExists('sections/otra.jpg');

        $this->actingAs($admin)->delete("/admin/datos-recurrentes/maestros/{$teacher->id}")->assertRedirect();
        Storage::disk('public')->assertMissing($photo);
        Storage::disk('public')->assertExists('sections/otra.jpg');
    }

    public function test_renaming_a_teacher_renames_it_in_the_class_cards(): void
    {
        $admin = $this->admin();
        $teacher = Teacher::create(['name' => 'Kelsang Panchen']);
        Teacher::create(['name' => 'Kelsang Dema']);
        $ficha = $this->ficha();
        $ficha->update(['content' => [...$ficha->content, 'teachers' => 'kelsang panchen, Guen Rinchung']]);

        // Un nombre que ya está en la lista: rechazado.
        $this->actingAs($admin)->post("/admin/datos-recurrentes/maestros/{$teacher->id}", ['_method' => 'put', 'name' => 'Kelsang Dema'])
            ->assertSessionHasErrors('name');

        $this->actingAs($admin)->post("/admin/datos-recurrentes/maestros/{$teacher->id}", ['_method' => 'put', 'name' => 'Guen Kelsang Panchen'])
            ->assertRedirect();

        $this->assertSame('Guen Kelsang Panchen', $teacher->fresh()->name);
        $this->assertSame('Guen Kelsang Panchen, Guen Rinchung', $ficha->fresh()->content['teachers']);
    }

    public function test_the_class_card_gets_the_profiles_of_its_teachers(): void
    {
        $ficha = $this->ficha();
        $ficha->update(['content' => [...$ficha->content, 'teachers' => 'Kelsang Panchen, Guen Rinchung']]);
        Teacher::create(['name' => 'kelsang panchen', 'description' => 'Maestro residente.', 'photo' => 'teachers/p.jpg']);
        // Sin descripción ni foto: su nombre queda como texto.
        Teacher::create(['name' => 'Guen Rinchung']);

        $profiles = $this->publicContent('clases-semanales', $ficha->id)['teacher_profiles'];

        $this->assertSame(['kelsang panchen' => ['name' => 'kelsang panchen', 'description' => 'Maestro residente.', 'photo' => 'teachers/p.jpg']], $profiles);
    }

    public function test_a_person_section_shows_the_chosen_teacher_and_falls_back_to_its_own_content(): void
    {
        $admin = $this->admin();
        $section = $this->person();
        $teacher = Teacher::create(['name' => 'Guenla Dekyong', 'description' => 'Descripción de la lista.', 'photo' => 'teachers/d.jpg']);

        $this->actingAs($admin)->get("/admin/sections/{$section->id}/edit")->assertInertia(fn (AssertableInertia $page) => $page->where(
            'fields',
            fn ($fields) => collect($fields)->firstWhere('key', 'teacher_id')['choices'][0]['name'] === 'Guenla Dekyong'
                && collect($fields)->firstWhere('key', 'body')['unless'] === 'teacher_id',
        ));

        $this->actingAs($admin)->put("/admin/sections/{$section->id}", ['content' => [...$section->content, 'teacher_id' => (string) $teacher->id]])
            ->assertRedirect()->assertSessionHas('success');
        $this->assertSame($teacher->id, $section->fresh()->content['teacher_id']);

        $content = $this->publicContent('quienes-somos', $section->id);
        $this->assertSame('Guenla Dekyong', $content['name']);
        $this->assertSame('Descripción de la lista.', $content['body']);
        $this->assertSame('teachers/d.jpg', $content['image']);

        // Sin foto en la lista, queda la de la sección.
        $teacher->update(['photo' => null]);
        $this->assertSame($section->content['image'], $this->publicContent('quienes-somos', $section->id)['image']);

        // Borrado de la lista: la sección vuelve a lo suyo.
        $teacher->delete();
        $content = $this->publicContent('quienes-somos', $section->id);
        $this->assertSame($section->content['body'], $content['body']);

        // Un id que no existe no se guarda.
        $this->actingAs($admin)->put("/admin/sections/{$section->id}", ['content' => [...$section->content, 'teacher_id' => '9999']])
            ->assertSessionHasErrors('content.teacher_id');
    }

    public function test_the_seeder_moves_the_bios_and_photos_of_quienes_somos_to_the_list(): void
    {
        Teacher::create(['name' => 'Guenla Jampa', 'description' => 'Ya tenía una.']);

        $this->seed(MaestrosPerfilesSeeder::class);

        $directora = $this->person('directora');
        $teacher = Teacher::where('name', 'Guenla Dekyong')->firstOrFail();
        $this->assertSame($teacher->id, $directora->fresh()->content['teacher_id']);
        $this->assertSame($directora->content['body'], $teacher->description);
        $this->assertSame($directora->content['image'], $teacher->photo);

        // No pisa lo que el maestr@ ya tenía.
        $this->assertSame('Ya tenía una.', Teacher::where('name', 'Guenla Jampa')->value('description'));

        // Kelsang Panchen sale de su bloque de texto con imagen.
        $this->assertNotNull(Teacher::where('name', 'Kelsang Panchen')->value('description'));

        // Idempotente.
        $count = Teacher::count();
        $this->seed(MaestrosPerfilesSeeder::class);
        $this->assertSame($count, Teacher::count());

        // Y la página se ve igual que antes.
        $this->assertSame('Guenla Dekyong', $this->publicContent('quienes-somos', $directora->id)['name']);
    }
}
