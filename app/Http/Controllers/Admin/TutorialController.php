<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TutorialRequest;
use App\Models\Tutorial;
use App\Support\YouTube;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Ayuda → Tutoriales" del panel: los videos de YouTube que explican cómo usar el
 * sitio. Se administran igual que las FAQ (crear, editar, ordenar, eliminar) y no
 * tocan el sitio público.
 */
class TutorialController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Tutorials/Index', [
            'tutorials' => Tutorial::ordered()->get()->map(fn (Tutorial $tutorial) => [
                'id' => $tutorial->id,
                'title' => $tutorial->title,
                'youtube_url' => $tutorial->youtube_url,
                'position' => $tutorial->position,
                // Lo que necesita la vista para mostrar el video sin volver a parsear la URL.
                'embed_url' => YouTube::embedUrl($tutorial->youtube_url),
                'thumbnail_url' => YouTube::thumbnailUrl($tutorial->youtube_url),
            ]),
        ]);
    }

    public function store(TutorialRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['position'] = (int) Tutorial::max('position') + 1;

        Tutorial::create($data);

        return back()->with('success', 'Tutorial agregado.');
    }

    public function update(TutorialRequest $request, Tutorial $tutorial): RedirectResponse
    {
        $tutorial->update($request->validated());

        return back()->with('success', 'Tutorial guardado.');
    }

    public function destroy(Tutorial $tutorial): RedirectResponse
    {
        $tutorial->delete();

        return back()->with('success', 'Tutorial eliminado.');
    }

    public function move(Request $request, Tutorial $tutorial): RedirectResponse
    {
        $direction = $request->validate(['direction' => ['required', 'in:up,down']])['direction'];

        $sibling = Tutorial::when($direction === 'up',
            fn ($q) => $q->where('position', '<', $tutorial->position)->orderByDesc('position'),
            fn ($q) => $q->where('position', '>', $tutorial->position)->orderBy('position'),
        )->first();

        if ($sibling) {
            [$tutorial->position, $sibling->position] = [$sibling->position, $tutorial->position];
            $tutorial->save();
            $sibling->save();
        }

        return back();
    }
}
