<?php

namespace Database\Seeders;

use App\Models\Section;
use App\Models\Teacher;
use App\Support\ImageStorage;
use Illuminate\Database\Seeder;

/**
 * Pasa a "Datos recurrentes" la biografía y la foto de los maestr@s que ya están
 * cargados en las secciones "Maestro / persona" (¿Quiénes somos?), y deja cada
 * sección eligiendo a su maestr@: desde ahí, se editan en un solo lugar.
 *
 * También completa la ficha de Kelsang Panchen con su bloque de ¿Quiénes somos?
 * (es un "Texto con imagen", no una "Maestro / persona", así que no se vincula).
 *
 * Idempotente y seguro en producción: no pisa una descripción ni una foto que el
 * maestr@ ya tenga, no toca una sección que ya eligió maestr@, y lo que cada
 * sección tiene cargado queda como respaldo.
 *
 *   php artisan db:seed --class=MaestrosPerfilesSeeder --force
 */
class MaestrosPerfilesSeeder extends Seeder
{
    public function run(): void
    {
        Section::where('type', 'person')->where('is_template', false)->get()->each(function (Section $section) {
            $content = $section->content ?? [];
            $name = trim((string) ($content['name'] ?? ''));

            if ($name === '' || filled($content['teacher_id'] ?? null)) {
                return;
            }

            $teacher = $this->fill($name, $content['body'] ?? null, $content['image'] ?? null);

            $section->update(['content' => [...$content, 'teacher_id' => $teacher->id]]);
        });

        Section::where('type', 'text_image')->where('key', 'maestro')
            ->whereHas('page', fn ($q) => $q->where('slug', 'quienes-somos'))
            ->get()
            ->each(function (Section $section) {
                $name = trim((string) ($section->content['heading'] ?? ''));

                if ($name !== '') {
                    $this->fill($name, $section->content['body'] ?? null, $section->content['image'] ?? null);
                }
            });
    }

    /** El maestr@ con ese nombre (lo crea si no está), completando lo que le falte. */
    private function fill(string $name, ?string $description, ?string $image): Teacher
    {
        $teacher = Teacher::all()->first(fn (Teacher $t) => Teacher::key($t->name) === Teacher::key($name))
            ?? Teacher::create(['name' => $name]);

        $changes = [];

        if (blank($teacher->description) && filled($description)) {
            $changes['description'] = $description;
        }

        // La foto subida a una sección es de esa sección: el maestr@ se queda con
        // una copia (las sembradas, seed/..., se comparten tal cual).
        if (blank($teacher->photo) && filled($image)) {
            $changes['photo'] = ImageStorage::adopt($image, [], 'teachers');
        }

        if ($changes) {
            $teacher->update($changes);
        }

        return $teacher;
    }
}
