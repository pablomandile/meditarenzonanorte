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
        'show_on_calendar',
        'content',
    ];

    protected function casts(): array
    {
        return [
            'visible' => 'boolean',
            'show_on_calendar' => 'boolean',
            'position' => 'integer',
            'content' => 'array',
        ];
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
