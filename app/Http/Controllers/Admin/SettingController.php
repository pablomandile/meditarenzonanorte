<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\Setting;
use App\Support\ImageStorage;
use App\Support\Typography;
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
        'heading_font',
    ];

    public function edit(): Response
    {
        $settings = Setting::values();
        $settings['footer_resources'] = json_decode($settings['footer_resources'] ?? '[]', true) ?: [];

        return Inertia::render('Admin/Settings/Edit', [
            'settings' => $settings,
            // Las fuentes salen del catálogo y no del front, así que los nombres
            // viven en un solo lado. Ver App\Support\Typography.
            'fonts' => Typography::options(),
        ]);
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

        self::saveLogo($request, 'logo', 'logo_path');
        self::saveLogo($request, 'footer_logo', 'footer_logo_path');

        return back()->with('success', 'Ajustes guardados.');
    }

    /**
     * El logo del menú y el del pie son ajustes separados, así que pueden ser
     * archivos distintos; si el del pie queda vacío, el pie usa el del menú.
     *
     * Se reemplaza subiendo un archivo nuevo y se quita mandando la ruta vacía
     * (el botón "Quitar" del campo). La ruta solo se limpia si el formulario
     * mandó el campo: si no viene, el ajuste queda intacto.
     */
    private static function saveLogo(UpdateSettingsRequest $request, string $fileKey, string $settingKey): void
    {
        $current = Setting::get($settingKey);

        if ($request->hasFile("files.$fileKey")) {
            Setting::set($settingKey, ImageStorage::replace(
                $request->file("files.$fileKey"),
                'settings',
                $current,
            ));

            return;
        }

        if ($current !== null && $request->has($settingKey) && blank($request->input($settingKey))) {
            ImageStorage::delete($current);
            Setting::set($settingKey, null);
        }
    }
}
