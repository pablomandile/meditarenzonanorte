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
     * con la lista por nombre, sin distinguir mayúsculas, acentos ni espacios de
     * más. Sin acentos porque así compara MySQL (utf8mb4_unicode_ci): para la
     * base "María" y "Maria" son el mismo nombre y el índice único no deja
     * cargar los dos.
     */
    public static function key(string $name): string
    {
        return Str::lower(Str::ascii(trim($name)));
    }
}
