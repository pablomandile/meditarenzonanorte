<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\Setting;
use App\Support\Construction;
use App\Support\GoogleAccess;
use App\Support\ImageStorage;
use App\Support\Typography;
use App\Support\WhatsApp;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    private const TEXT_KEYS = [
        'site_name',
        'phone_display',
        'phone_link',
        // whatsapp_url no está acá: se guarda aparte, porque hay que sacarle
        // cualquier mensaje viejo que haya quedado pegado (ver update()).
        'whatsapp_message',
        'email',
        'instagram_url',
        'address',
        'heading_font',
        'construction_title',
        'construction_message',
    ];

    public function edit(): Response
    {
        $settings = Setting::values();
        $settings['footer_resources'] = json_decode($settings['footer_resources'] ?? '[]', true) ?: [];
        // Sin el ?text= pegado: eso se edita aparte, en whatsapp_message.
        $settings['whatsapp_url'] = WhatsApp::baseUrl();

        return Inertia::render('Admin/Settings/Edit', [
            'settings' => $settings,
            // Las fuentes salen del catálogo y no del front, así que los nombres
            // viven en un solo lado. Ver App\Support\Typography.
            'fonts' => Typography::options(),
            // Ídem los textos de fábrica del cartel de "En construcción": el panel
            // los pinta como marca de agua de los campos vacíos, así lo que se ve
            // ahí es lo que va a salir publicado. Ver App\Support\Construction.
            'construction' => Construction::defaults(),
            // Para la sección "Acceso con Google": si el login con Google está
            // configurado en el servidor y qué cuentas ya tienen acceso fijo.
            'google' => [
                'configured' => GoogleAccess::configured(),
                'owner_emails' => GoogleAccess::ownerEmails(),
            ],
        ]);
    }

    /** El cartel de "En construcción" tal cual lo ven las visitas. */
    public function construction(): Response
    {
        return Inertia::render('Public/UnderConstruction', Construction::content() + ['preview' => true]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();

        foreach (self::TEXT_KEYS as $key) {
            if (array_key_exists($key, $data)) {
                Setting::set($key, $data[$key]);
            }
        }

        if (array_key_exists('whatsapp_url', $data)) {
            // Por si queda pegado un enlace viejo con el ?text= adentro (de antes de
            // separar el mensaje, o pegado a mano): se guarda solo la parte del
            // número, y el mensaje vive aparte en whatsapp_message.
            Setting::set('whatsapp_url', $data['whatsapp_url'] ? strtok($data['whatsapp_url'], '?') : null);
        }

        if (array_key_exists('google_allowed_emails', $data)) {
            // Se guarda normalizado —minúscula, sin repetidos, uno por línea— y sin
            // los emails que ya vienen fijos del servidor. Ver App\Support\GoogleAccess.
            Setting::set('google_allowed_emails', GoogleAccess::normalizeForStorage($data['google_allowed_emails']));
        }

        /*
         * El interruptor va aparte del bucle de textos: se guarda '1' o nada, para
         * que Construction::enabled() pueda leerlo con una simple prueba de verdad.
         */
        if ($request->has('under_construction')) {
            Setting::set('under_construction', $request->boolean('under_construction') ? '1' : null);
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
