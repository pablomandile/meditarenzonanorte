<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FaqRequest;
use App\Models\Faq;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FaqController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Faqs/Index', [
            'faqs' => Faq::ordered()->get(),
        ]);
    }

    public function store(FaqRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['position'] = (int) Faq::max('position') + 1;

        Faq::create($data);

        return back()->with('success', 'Pregunta creada.');
    }

    public function update(FaqRequest $request, Faq $faq): RedirectResponse
    {
        $faq->update($request->validated());

        return back()->with('success', 'Pregunta guardada.');
    }

    public function destroy(Faq $faq): RedirectResponse
    {
        $faq->delete();

        return back()->with('success', 'Pregunta eliminada.');
    }

    public function move(Request $request, Faq $faq): RedirectResponse
    {
        $direction = $request->validate(['direction' => ['required', 'in:up,down']])['direction'];

        $sibling = Faq::when($direction === 'up',
            fn ($q) => $q->where('position', '<', $faq->position)->orderByDesc('position'),
            fn ($q) => $q->where('position', '>', $faq->position)->orderBy('position'),
        )->first();

        if ($sibling) {
            [$faq->position, $sibling->position] = [$sibling->position, $faq->position];
            $faq->save();
            $sibling->save();
        }

        return back();
    }
}
