<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Venue extends Model
{
    protected $fillable = ['name'];

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('name');
    }
}
