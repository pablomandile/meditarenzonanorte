<?php

namespace App\Http\Middleware;

use App\Models\Page;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'nav' => fn () => Page::inMenu()->get(['slug', 'menu_label'])
                ->map(fn ($page) => ['slug' => $page->slug, 'label' => $page->menu_label])
                ->values(),
            'settings' => function () {
                $settings = Setting::values();
                $settings['footer_resources'] = json_decode($settings['footer_resources'] ?? '[]', true) ?: [];

                return $settings;
            },
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
            ],
        ]);
    }
}
