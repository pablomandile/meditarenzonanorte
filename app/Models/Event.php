<?php

namespace App\Models;

use App\Support\EventCalendar;
use App\Support\SpanishDate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    /** Los dos se calculan; viajan en los props para que las vistas no rearmen nada. */
    protected $appends = ['date_auto', 'date_label'];

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
        'image_url',
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

    /**
     * La fecha y el horario escritos a partir de los campos de fecha y hora, para
     * no tener que tipearlos de nuevo a mano.
     *
     * "Sábado 29 de agosto de 10 a 17.30 hs", "Del 28 al 30 de agosto", y con el
     * año sólo cuando no es el actual, que es como se escribe una fecha.
     */
    protected function dateAuto(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (! $this->starts_at) {
                return null;
            }

            $start = $this->starts_at;
            $end = $this->ends_at && $this->ends_at->greaterThan($start) ? $this->ends_at : null;
            $hours = SpanishDate::hourRange($this->start_time, $this->end_time);
            $year = $start->year === now(EventCalendar::TIMEZONE)->year ? '' : ' de '.$start->year;

            if (! $end) {
                $text = ucfirst(SpanishDate::weekday($start->dayOfWeekIso)).' '.$start->day.' de '.SpanishDate::month($start->month).$year;
            } elseif ($start->month === $end->month) {
                $text = 'Del '.$start->day.' al '.$end->day.' de '.SpanishDate::month($end->month).$year;
            } else {
                $text = 'Del '.$start->day.' de '.SpanishDate::month($start->month)
                    .' al '.$end->day.' de '.SpanishDate::month($end->month).$year;
            }

            return $hours ? $text.' '.$hours : $text;
        });
    }

    /** Lo que se publica: el texto a mano si lo hay, y si no el automático. */
    protected function dateLabel(): Attribute
    {
        return Attribute::get(fn (): ?string => filled($this->date_text) ? $this->date_text : $this->date_auto);
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
