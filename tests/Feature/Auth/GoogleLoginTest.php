<?php

namespace Tests\Feature\Auth;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // El login con Google configurado, y el dueño fijo desde el servidor.
        config([
            'services.google.client_id' => 'test-client-id',
            'services.google.client_secret' => 'test-secret',
            'services.google.allowed_emails' => 'pablo.mandile@gmail.com',
        ]);
    }

    /** Simula la vuelta de Google con una cuenta dada y devuelve la respuesta del callback. */
    private function arriveFromGoogleAs(string $email, string $name = 'Persona', string $id = 'g-abc'): TestResponse
    {
        $socialUser = (new SocialiteUser)->map([
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'avatar' => 'https://example.com/avatar.png',
        ]);

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($socialUser);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        return $this->get('/auth/google/callback');
    }

    public function test_the_owner_email_from_the_server_can_sign_in(): void
    {
        $response = $this->arriveFromGoogleAs('pablo.mandile@gmail.com', 'Pablo');

        $this->assertAuthenticated();
        $response->assertRedirect(route('admin.pages.index'));
        $this->assertDatabaseHas('users', ['email' => 'pablo.mandile@gmail.com', 'google_id' => 'g-abc']);
    }

    public function test_an_account_enabled_in_the_panel_can_sign_in(): void
    {
        Setting::set('google_allowed_emails', 'colaboradora@gmail.com');

        $this->arriveFromGoogleAs('colaboradora@gmail.com');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'colaboradora@gmail.com']);
    }

    public function test_an_unlisted_google_account_is_rejected(): void
    {
        $response = $this->arriveFromGoogleAs('cualquiera@gmail.com');

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('users', ['email' => 'cualquiera@gmail.com']);
    }

    public function test_the_allowlist_is_case_insensitive(): void
    {
        Setting::set('google_allowed_emails', 'Colaboradora@Gmail.com');

        $this->arriveFromGoogleAs('COLABORADORA@gmail.com');

        $this->assertAuthenticated();
    }

    public function test_clearing_the_panel_list_blocks_an_account_that_is_not_the_owner(): void
    {
        Setting::set('google_allowed_emails', null);

        $this->arriveFromGoogleAs('colaboradora@gmail.com');

        $this->assertGuest();
    }

    public function test_admin_can_edit_the_google_allowlist_from_settings(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->post('/admin/settings', [
            'site_name' => 'Meditar',
            'google_allowed_emails' => "  Uno@Gmail.com \n dos@gmail.com\nuno@gmail.com\npablo.mandile@gmail.com",
            'footer_resources' => [],
        ])->assertRedirect()->assertSessionHas('success');

        // Minúscula, sin repetidos, sin el email del dueño (ya viene fijo), uno por línea.
        $this->assertSame("uno@gmail.com\ndos@gmail.com", Setting::get('google_allowed_emails'));
    }

    public function test_the_google_allowlist_rejects_a_line_that_is_not_an_email(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->post('/admin/settings', [
            'site_name' => 'Meditar',
            'google_allowed_emails' => "ok@gmail.com\nno-arroba",
            'footer_resources' => [],
        ])->assertSessionHasErrors('google_allowed_emails');

        $this->assertNull(Setting::get('google_allowed_emails'));
    }

    public function test_the_settings_screen_reports_whether_google_login_is_configured(): void
    {
        $admin = User::factory()->create();

        $props = $this->actingAs($admin)->get('/admin/settings')->assertOk()->viewData('page')['props'];

        $this->assertTrue($props['google']['configured']);
        $this->assertSame(['pablo.mandile@gmail.com'], $props['google']['owner_emails']);
    }
}
