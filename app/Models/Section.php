<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Section extends Model
{
    protected $fillable = [
        'page_id',
        'type',
        'key',
        'position',
        'visible',
        'is_template',
        'show_on_calendar',
        'content',
    ];

    protected function casts(): array
    {
        return [
            'visible' => 'boolean',
            'is_template' => 'boolean',
            'show_on_calendar' => 'boolean',
            'position' => 'integer',
            'content' => 'array',
        ];
    }

    /**
     * Una plantilla nunca se publica ni va al calendario: es solo el molde para
     * clonar. Se fuerza acá y no en cada lugar que guarda una sección —el panel,
     * el seeder, el "mostrar/ocultar"— para que no haya forma de dejarla visible.
     *
     * setAttribute() y no `$section->visible = …`: dentro de un closure con scope
     * de la clase, `visible` toca la propiedad reservada de Eloquent (la lista de
     * serialización), no la columna.
     */
    protected static function booted(): void
    {
        static::saving(function (Section $section) {
            if ($section->is_template) {
                $section->setAttribute('visible', false);
                $section->setAttribute('show_on_calendar', false);
            }
        });
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('visible', true);
    }

    public function scopeOnCalendar(Builder $query): Builder
    {
        return $query->where('show_on_calendar', true);
    }
}
