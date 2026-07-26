<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'title',
        'description',
        'date_text',
        'starts_at',
        'location',
        'price',
        'cta_label',
        'cta_url',
        'image_path',
        'visible',
        'show_on_home',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'visible' => 'boolean',
            'show_on_home' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('visible', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByRaw('starts_at IS NULL, starts_at ASC')->orderBy('position');
    }
}
