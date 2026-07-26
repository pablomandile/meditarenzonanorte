<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

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
            'email' => ['nullable', 'email', 'max:255'],
            'instagram_url' => ['nullable', 'string', 'max:500'],
            'address' => ['nullable', 'string', 'max:500'],
            'footer_resources' => ['nullable', 'array', 'max:6'],
            'footer_resources.*.image' => ['nullable', 'string', 'max:500'],
            'footer_resources.*.title' => ['nullable', 'string', 'max:255'],
            'footer_resources.*.text' => ['nullable', 'string', 'max:2000'],
            'footer_resources.*.url' => ['nullable', 'string', 'max:500'],
            'files' => ['nullable', 'array'],
            'files.logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'files.footer_resources.*.image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
        ];
    }
}
