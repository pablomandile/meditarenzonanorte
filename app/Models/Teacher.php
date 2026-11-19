<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Teacher extends Model
{
    protected $fillable = ['name', 'description', 'photo'];

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('name');
    }

    /** Tiene algo para mostrar además del nombre. */
    public function hasProfile(): bool
    {
        return filled($this->description) || filled($this->photo);
    }

    /**
     * Las fichas guardan el nombre como texto ("Ana, Luis"), así que se las cruza
     * con la lista por nombre, sin distinguir mayúsculas ni espacios de más.
     */
    public static function key(string $name): string
    {
        return Str::lower(trim($name));
    }
}
