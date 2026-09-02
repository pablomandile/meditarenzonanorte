<?php

namespace Tests\Feature;

use App\Models\Tutorial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class TutorialsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    public function test_admin_can_add_a_tutorial_and_it_gets_an_embed_url(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/tutorials', [
            'title' => 'Cómo editar una página',
            'youtube_url' => 'https://youtu.be/dQw4w9WgXcQ?si=abc',
        ])->assertRedirect()->assertSessionHas('success');

        $tutorial = Tutorial::firstOrFail();
        $this->assertSame('Cómo editar una página', $tutorial->title);
        $this->assertSame(1, $tutorial->position);

        $this->actingAs($admin)->get('/admin/tutorials')->assertOk()->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Admin/Tutorials/Index')
                ->where('tutorials.0.embed_url', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ')
                ->where('tutorials.0.thumbnail_url', 'https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg'),
        );
    }

    public function test_a_link_that_is_not_youtube_is_rejected(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/tutorials', [
            'title' => 'Roto',
            'youtube_url' => 'https://vimeo.com/12345',
        ])->assertSessionHasErrors('youtube_url');

        $this->assertSame(0, Tutorial::count());
    }

    public function test_admin_can_edit_reorder_and_delete_tutorials(): void
    {
        $admin = $this->admin();

        $a = Tutorial::create(['title' => 'Uno', 'youtube_url' => 'https://youtu.be/aaaaaaaaaaa', 'position' => 1]);
        $b = Tutorial::create(['title' => 'Dos', 'youtube_url' => 'https://youtu.be/bbbbbbbbbbb', 'position' => 2]);

        // Editar
        $this->actingAs($admin)->put("/admin/tutorials/{$a->id}", [
            'title' => 'Uno (editado)',
            'youtube_url' => 'https://www.youtube.com/watch?v=ccccccccccc',
        ])->assertRedirect();
        $this->assertSame('Uno (editado)', $a->fresh()->title);
        $this->assertSame('ccccccccccc', $a->fresh()->youtubeId());

        // Reordenar
        $this->actingAs($admin)->patch("/admin/tutorials/{$b->id}/move", ['direction' => 'up'])->assertRedirect();
        $this->assertSame($a->position, $b->fresh()->position);
        $this->assertSame($b->position, $a->fresh()->position);

        // Eliminar
        $this->actingAs($admin)->delete("/admin/tutorials/{$a->id}")->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseMissing('tutorials', ['id' => $a->id]);
    }

    public function test_the_tutorials_screen_needs_a_session(): void
    {
        $tutorial = Tutorial::create(['title' => 'Uno', 'youtube_url' => 'https://youtu.be/aaaaaaaaaaa', 'position' => 1]);

        $this->get('/admin/tutorials')->assertRedirect('/login');
        $this->post('/admin/tutorials', ['title' => 'X', 'youtube_url' => 'https://youtu.be/xxxxxxxxxxx'])->assertRedirect('/login');
        $this->delete("/admin/tutorials/{$tutorial->id}")->assertRedirect('/login');

        $this->assertDatabaseHas('tutorials', ['id' => $tutorial->id]);
    }
}
