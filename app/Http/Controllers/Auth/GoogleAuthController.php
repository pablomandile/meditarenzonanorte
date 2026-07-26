<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class GoogleAuthController extends Controller
{
    /**
     * Redirect the user to Google's OAuth consent screen.
     */
    public function redirect(): SymfonyRedirectResponse|RedirectResponse
    {
        if (! config('services.google.client_id')) {
            return redirect()->route('login')
                ->withErrors(['email' => 'El acceso con Google no está configurado.']);
        }

        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle the callback from Google and log the user in.
     *
     * Security: only e-mails present in GOOGLE_ALLOWED_EMAILS may access the
     * admin panel. Any other Google account is rejected.
     */
    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable) {
            return redirect()->route('login')
                ->withErrors(['email' => 'No se pudo iniciar sesión con Google. Intentá de nuevo.']);
        }

        $email = strtolower((string) $googleUser->getEmail());

        $allowed = collect(explode(',', (string) config('services.google.allowed_emails')))
            ->map(fn ($e) => strtolower(trim($e)))
            ->filter();

        if ($allowed->isEmpty() || ! $allowed->contains($email)) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Esta cuenta de Google no está autorizada para el panel.']);
        }

        $user = User::firstOrCreate(
            ['email' => $googleUser->getEmail()],
            [
                'name' => $googleUser->getName() ?: $email,
                'password' => Hash::make(Str::random(40)),
                'email_verified_at' => now(),
            ],
        );

        $user->forceFill([
            'google_id' => $googleUser->getId(),
            'avatar' => $googleUser->getAvatar(),
        ])->save();

        Auth::login($user, remember: true);

        request()->session()->regenerate();

        return redirect()->intended(route('admin.pages.index'));
    }
}
