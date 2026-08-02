<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'title',
        'description',
        'date_text',
        'starts_at',
        'ends_at',
        'start_time',
        'end_time',
        'location',
        'price',
        'cta_label',
        'cta_url',
        'image_path',
        'visible',
        'show_on_home',
        'show_on_calendar',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'visible' => 'boolean',
            'show_on_home' => 'boolean',
            'show_on_calendar' => 'boolean',
            'position' => 'integer',
        ];
    }

    /**
     * Las horas se normalizan a H:i sin pasar por un cast de fecha: MySQL
     * devuelve '19:00:00' y el <input type="time"> del panel rechaza los
     * segundos, y un cast datetime guardaría un timestamp entero en la columna.
     */
    protected function startTime(): Attribute
    {
        return Attribute::get(fn ($value) => $value ? substr($value, 0, 5) : null);
    }

    protected function endTime(): Attribute
    {
        return Attribute::get(fn ($value) => $value ? substr($value, 0, 5) : null);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('visible', true);
    }

    public function scopeOnCalendar(Builder $query): Builder
    {
        return $query->where('show_on_calendar', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByRaw('starts_at IS NULL, starts_at ASC')->orderBy('position');
    }
}
