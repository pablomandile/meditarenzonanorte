<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class EventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'date_text' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'location' => ['nullable', 'string', 'max:255'],
            'price' => ['nullable', 'string', 'max:255'],
            'cta_label' => ['nullable', 'string', 'max:255'],
            'cta_url' => ['nullable', 'string', 'max:500'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'image_path' => ['nullable', 'string', 'max:500'],
            'visible' => ['boolean'],
            'show_on_home' => ['boolean'],
            'show_on_calendar' => ['boolean'],
        ];
    }

    /**
     * Los cruces entre campos van acá y no como reglas after_or_equal: esas
     * reglas parsean el otro campo con Carbon aunque venga vacío, y null se
     * interpreta como "ahora" — la validación fallaría según la hora del día.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->filled('starts_at') && $this->filled('ends_at') && $this->date('ends_at') < $this->date('starts_at')) {
                    $validator->errors()->add('ends_at', 'El evento no puede terminar antes de empezar.');
                }

                $sameDay = ! $this->filled('ends_at') || ($this->filled('starts_at') && $this->date('ends_at')->isSameDay($this->date('starts_at')));

                if ($this->filled('start_time') && $this->filled('end_time') && $sameDay && $this->input('end_time') <= $this->input('start_time')) {
                    $validator->errors()->add('end_time', 'La hora de fin tiene que ser posterior a la de inicio.');
                }
            },
        ];
    }
}
