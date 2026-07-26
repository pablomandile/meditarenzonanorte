<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\Setting;
use App\Support\ImageStorage;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    private const TEXT_KEYS = [
        'site_name',
        'phone_display',
        'phone_link',
        'whatsapp_url',
        'email',
        'instagram_url',
        'address',
    ];

    public function edit(): Response
    {
        $settings = Setting::values();
        $settings['footer_resources'] = json_decode($settings['footer_resources'] ?? '[]', true) ?: [];

        return Inertia::render('Admin/Settings/Edit', ['settings' => $settings]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();

        foreach (self::TEXT_KEYS as $key) {
            if (array_key_exists($key, $data)) {
                Setting::set($key, $data[$key]);
            }
        }

        $resources = array_values(array_filter($data['footer_resources'] ?? [], fn ($card) => array_filter($card ?? [])));

        foreach ($request->file('files.footer_resources', []) as $i => $fileGroup) {
            if (isset($fileGroup['image'], $resources[$i])) {
                $old = json_decode(Setting::get('footer_resources', '[]'), true)[$i]['image'] ?? null;
                $resources[$i]['image'] = ImageStorage::replace($fileGroup['image'], 'settings', $old);
            }
        }

        Setting::set('footer_resources', json_encode($resources, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        if ($request->hasFile('files.logo')) {
            Setting::set('logo_path', ImageStorage::replace(
                $request->file('files.logo'),
                'settings',
                Setting::get('logo_path'),
            ));
        }

        return back()->with('success', 'Ajustes guardados.');
    }
}
