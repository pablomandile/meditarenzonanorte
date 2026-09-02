<?php

namespace App\Http\Requests\Admin;

use App\Support\YouTube;
use Illuminate\Foundation\Http\FormRequest;

class TutorialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'youtube_url' => [
                'required',
                'string',
                'max:500',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! YouTube::id($value)) {
                        $fail('Poné el enlace de un video de YouTube (el de la barra del navegador o el de "Compartir").');
                    }
                },
            ],
        ];
    }
}
