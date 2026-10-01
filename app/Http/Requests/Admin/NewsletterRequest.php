<?php

namespace App\Http\Requests\Admin;

use App\Models\Event;
use App\Models\Section;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class NewsletterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:weekly,monthly'],
            // Mailchimp corta los asuntos largos en la bandeja de entrada mucho antes
            // de los 150, pero ése es su tope y no tiene sentido rechazar antes.
            'subject' => ['required', 'string', 'max:150'],
            'preview_text' => ['nullable', 'string', 'max:150'],
            'items' => ['nullable', 'array', 'max:30'],
            'items.*.kind' => ['required', 'in:section,event'],
            'items.*.id' => ['required', 'integer'],
        ];
    }

    /**
     * Que cada item exista de verdad.
     *
     * No se puede resolver con `exists:` porque la tabla depende del `kind` de la
     * misma fila, así que se comprueba acá, igual que el cruce de fechas de EventRequest.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $items = $this->input('items', []);

                if (! is_array($items)) {
                    return;
                }

                $sections = Section::query()->where('type', 'class_info')->pluck('id')->all();
                $events = Event::query()->pluck('id')->all();

                foreach ($items as $i => $item) {
                    $id = (int) ($item['id'] ?? 0);
                    $exists = ($item['kind'] ?? null) === 'section'
                        ? in_array($id, $sections, true)
                        : in_array($id, $events, true);

                    if (! $exists) {
                        $validator->errors()->add("items.$i.id", 'Esta actividad ya no existe: quitala de la lista.');
                    }
                }
            },
        ];
    }

    /**
     * Lo que se guarda en la columna `content`: sólo las referencias, normalizadas.
     *
     * @return array<int, array{kind: string, id: int}>
     */
    public function items(): array
    {
        return collect($this->input('items', []))
            ->map(fn ($item) => ['kind' => $item['kind'], 'id' => (int) $item['id']])
            ->values()
            ->all();
    }
}
