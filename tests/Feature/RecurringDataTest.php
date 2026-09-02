<?php

namespace Tests\Feature;

use App\Models\Section;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Venue;
use Database\Seeders\ContentSeeder;
use Database\Seeders\DatosRecurrentesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class RecurringDataTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    public function test_admin_can_add_and_delete_teachers_and_venues(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/datos-recurrentes/maestros', ['name' => 'Kelsang Dema'])
            ->assertRedirect()->assertSessionHas('success');
        $this->actingAs($admin)->post('/admin/datos-recurrentes/lugares', ['name' => 'Pasaje Cajaraville 173'])
            ->assertRedirect();

        $this->assertDatabaseHas('teachers', ['name' => 'Kelsang Dema']);
        $this->assertDatabaseHas('venues', ['name' => 'Pasaje Cajaraville 173']);

        // Repetido: rechazado.
        $this->actingAs($admin)->post('/admin/datos-recurrentes/maestros', ['name' => 'Kelsang Dema'])
            ->assertSessionHasErrors('name');
        $this->assertSame(1, Teacher::count());

        $teacher = Teacher::firstOrFail();
        $this->actingAs($admin)->delete("/admin/datos-recurrentes/maestros/{$teacher->id}")->assertRedirect();
        $this->assertDatabaseMissing('teachers', ['id' => $teacher->id]);
    }

    public function test_the_lists_need_a_session(): void
    {
        $this->get('/admin/datos-recurrentes')->assertRedirect('/login');
        $this->post('/admin/datos-recurrentes/maestros', ['name' => 'Colado'])->assertRedirect('/login');
        $this->assertSame(0, Teacher::count());
    }

    public function test_the_class_info_form_gets_the_recurring_lists_as_pools(): void
    {
        $this->seed(ContentSeeder::class);
        $admin = $this->admin();

        Teacher::create(['name' => 'Kelsang Panchen']);
        Venue::create(['name' => 'Psj. Cajaraville 173, Barrio Martin, Rosario']);

        $ficha = Section::whereHas('page', fn ($q) => $q->where('slug', 'clases-semanales'))
            ->where('key', 'clase-principal')->firstOrFail();

        $props = $this->actingAs($admin)->get("/admin/sections/{$ficha->id}/edit")->assertOk()->viewData('page')['props'];

        $this->assertSame(['Kelsang Panchen'], $props['pools']['teachers']);
        $this->assertContains(['key' => 'teachers', 'type' => 'tags', 'label' => 'Maestr@ (varios separados por coma)', 'pool' => 'teachers'], $props['fields']);

        // Y guardar la ficha sigue almacenando el texto tal cual (coma para varios).
        $this->actingAs($admin)->put("/admin/sections/{$ficha->id}", [
            'content' => [...$ficha->content, 'teachers' => 'Kelsang Panchen, Kelsang Dema', 'location' => 'Otro lugar'],
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame('Kelsang Panchen, Kelsang Dema', $ficha->fresh()->content['teachers']);
        $this->assertSame('Otro lugar', $ficha->fresh()->content['location']);
    }

    public function test_seeder_fills_the_lists_from_the_existing_class_cards(): void
    {
        $this->seed(ContentSeeder::class);
        $this->seed(DatosRecurrentesSeeder::class);

        $this->assertTrue(Teacher::where('name', 'Kelsang Panchen')->exists());
        $this->assertTrue(Venue::where('name', 'Psj. Cajaraville 173, Barrio Martin, Rosario')->exists());

        // No mete los textos de relleno de la plantilla.
        $this->assertFalse(Teacher::where('name', 'Quién lo dicta')->exists());
        $this->assertFalse(Venue::where('name', 'Dirección donde se dicta')->exists());

        // Idempotente.
        $before = Teacher::count();
        $this->seed(DatosRecurrentesSeeder::class);
        $this->assertSame($before, Teacher::count());
    }

    public function test_the_recurring_data_screen_renders_both_lists(): void
    {
        $admin = $this->admin();
        Teacher::create(['name' => 'Kelsang Panchen']);
        Venue::create(['name' => 'Rosario']);

        $this->actingAs($admin)->get('/admin/datos-recurrentes')->assertOk()->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Admin/RecurringData/Index')
                ->where('teachers.0.name', 'Kelsang Panchen')
                ->where('venues.0.name', 'Rosario'),
        );
    }
}
