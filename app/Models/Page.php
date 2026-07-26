<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'menu_label',
        'menu_order',
        'visible',
        'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'visible' => 'boolean',
            'menu_order' => 'integer',
        ];
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class)->orderBy('position');
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('visible', true);
    }

    public function scopeInMenu(Builder $query): Builder
    {
        return $query->visible()->whereNotNull('menu_label')->orderBy('menu_order');
    }
}
