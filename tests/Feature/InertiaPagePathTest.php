<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Que la carpeta de páginas configurada exista con ESE case exacto.
 *
 * Este proyecto se desarrolla en Windows, que no distingue mayúsculas: una ruta
 * con el case equivocado resuelve igual y no se nota nada. En Linux —el runner
 * del CI y el servidor— no existe, y cualquier assertInertia()->component('X')
 * falla con "Inertia page component file [X] does not exist".
 *
 * Por eso no alcanza con is_dir(), que en Windows diría que sí de todos modos:
 * hay que comparar contra el nombre REAL que devuelve el filesystem.
 */
class InertiaPagePathTest extends TestCase
{
    public function test_las_carpetas_de_paginas_existen_con_el_case_configurado(): void
    {
        $rutas = [
            ...config('inertia.page_paths'),
            ...config('inertia.testing.page_paths'),
        ];

        foreach (array_unique($rutas) as $ruta) {
            $this->assertContains(
                basename($ruta),
                scandir(dirname($ruta)),
                "config/inertia.php apunta a [$ruta], y el filesystem no tiene esa carpeta con ese case. "
                    .'En Windows funciona igual; en Linux los tests de Inertia fallan.',
            );
        }
    }
}
