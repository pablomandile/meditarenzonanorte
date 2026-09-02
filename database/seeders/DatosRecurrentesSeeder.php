<?php

namespace Database\Seeders;

use App\Models\Section;
use App\Models\Teacher;
use App\Models\Venue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Llena "Datos recurrentes" con los maestr@s y lugares que ya están escritos en
 * las fichas de clase del sitio, así la lista no arranca vacía. Idempotente: un
 * nombre que ya está no se duplica, y no toca las fichas.
 *
 *   php artisan db:seed --class=DatosRecurrentesSeeder --force
 */
class DatosRecurrentesSeeder extends Seeder
{
    /** Textos de relleno de la ficha plantilla, que no son datos de verdad. */
    private const PLACEHOLDERS = [
        'quién lo dicta', 'quién la dicta', 'dirección donde se dicta',
    ];

    public function run(): void
    {
        $sections = Section::query()->where('type', 'class_info')->where('is_template', false)->get();

        $sections
            ->flatMap(fn (Section $section) => explode(',', (string) ($section->content['teachers'] ?? '')))
            ->pipe(fn ($names) => $this->clean($names))
            ->each(fn (string $name) => Teacher::firstOrCreate(['name' => $name]));

        $sections
            ->map(fn (Section $section) => (string) ($section->content['location'] ?? ''))
            ->pipe(fn ($names) => $this->clean($names))
            ->each(fn (string $name) => Venue::firstOrCreate(['name' => $name]));
    }

    /**
     * @param  Collection<int, string>  $names
     * @return Collection<int, string>
     */
    private function clean($names)
    {
        return $names
            ->map(fn (string $name) => trim($name))
            ->filter()
            ->reject(fn (string $name) => in_array(Str::lower($name), self::PLACEHOLDERS, true))
            ->unique(fn (string $name) => Str::lower($name))
            ->values();
    }
}
