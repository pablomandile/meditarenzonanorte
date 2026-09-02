<?php

namespace App\Http\Requests\Admin;

use App\Support\GoogleAccess;
use App\Support\Typography;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'site_name' => ['nullable', 'string', 'max:255'],
            'phone_display' => ['nullable', 'string', 'max:255'],
            'phone_link' => ['nullable', 'string', 'max:255'],
            'whatsapp_url' => ['nullable', 'string', 'max:500'],
            // El mensaje precargado del botón de WhatsApp. Va aparte del enlace de
            // arriba: SettingController le arma el ?text= por separado. Ver
            // App\Support\WhatsApp.
            'whatsapp_message' => ['nullable', 'string', 'max:1000'],
            'email' => ['nullable', 'email', 'max:255'],
            'instagram_url' => ['nullable', 'string', 'max:500'],
            'address' => ['nullable', 'string', 'max:500'],
            // Sólo una clave del catálogo: el valor termina dentro de un font-family
            // del HTML, así que no puede ser texto libre. Vacío = la fuente de siempre.
            'heading_font' => ['nullable', Rule::in(array_keys(Typography::FONTS))],
            // El cartel de "En construcción". Los textos vacíos no borran nada: el
            // cartel cae en los de fábrica. Ver App\Support\Construction.
            'under_construction' => ['nullable', 'boolean'],
            'construction_title' => ['nullable', 'string', 'max:120'],
            'construction_message' => ['nullable', 'string', 'max:600'],
            // Cuentas de Google habilitadas para el panel (una por línea). Cada
            // línea tiene que ser un email. Ver App\Support\GoogleAccess.
            'google_allowed_emails' => [
                'nullable',
                'string',
                'max:2000',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    foreach (GoogleAccess::parse($value) as $email) {
                        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $fail("«{$email}» no parece un email. Poné una dirección de Google por línea.");
                        }
                    }
                },
            ],
            // Las rutas viajan de vuelta para poder detectar el "Quitar" del campo
            // de imagen: llegan vacías cuando se sacó el logo.
            'logo_path' => ['nullable', 'string', 'max:500'],
            'footer_logo_path' => ['nullable', 'string', 'max:500'],
            'footer_resources' => ['nullable', 'array', 'max:6'],
            'footer_resources.*.image' => ['nullable', 'string', 'max:500'],
            'footer_resources.*.title' => ['nullable', 'string', 'max:255'],
            'footer_resources.*.text' => ['nullable', 'string', 'max:2000'],
            'footer_resources.*.url' => ['nullable', 'string', 'max:500'],
            'files' => ['nullable', 'array'],
            'files.logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'files.footer_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'files.footer_resources.*.image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
        ];
    }
}
