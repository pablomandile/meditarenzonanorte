<?php

namespace App\Http\Requests\Admin;

use App\Support\SectionRegistry;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return SectionRegistry::rules($this->route('section')->type);
    }

    /**
     * Los mensajes por defecto nombran la clave cruda ("content.occurrences.1.weekday"),
     * que no le dice nada al dueño del sitio.
     */
    public function messages(): array
    {
        return [
            'content.occurrences.*.weekday.required_if' => 'Elegí el día de la semana en cada fecha del calendario.',
            'content.occurrences.*.weekday.between' => 'El día de la semana no es válido.',
            'content.occurrences.*.date.required_if' => 'Completá la fecha en cada actividad del calendario.',
            'content.occurrences.*.date.date_format' => 'Revisá las fechas del calendario.',
            'content.occurrences.*.from.date_format' => 'Revisá las fechas del calendario.',
            'content.occurrences.*.until.date_format' => 'Revisá las fechas del calendario.',
            'content.occurrences.*.start.date_format' => 'Revisá las horas del calendario: tienen que ser horas válidas.',
            'content.occurrences.*.end.date_format' => 'Revisá las horas del calendario: tienen que ser horas válidas.',
        ];
    }
}
