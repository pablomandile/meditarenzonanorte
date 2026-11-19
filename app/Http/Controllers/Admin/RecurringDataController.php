<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Price;
use App\Models\Teacher;
use App\Models\Venue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Datos recurrentes": las listas de maestr@s, lugares y precios que después se
 * eligen en los campos "Maestr@", "Lugar" y "Precio" de las fichas de clase.
 * Sólo se agregan y se borran; el orden es alfabético.
 */
class RecurringDataController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/RecurringData/Index', [
            'teachers' => Teacher::ordered()->get(['id', 'name']),
            'venues' => Venue::ordered()->get(['id', 'name']),
            'prices' => Price::ordered()->get(['id', 'name']),
        ]);
    }

    public function storeTeacher(Request $request): RedirectResponse
    {
        Teacher::create($this->validateName($request, 'teachers'));

        return back()->with('success', 'Maestr@ agregad@.');
    }

    public function destroyTeacher(Teacher $teacher): RedirectResponse
    {
        $teacher->delete();

        return back()->with('success', 'Maestr@ eliminad@.');
    }

    public function storeVenue(Request $request): RedirectResponse
    {
        Venue::create($this->validateName($request, 'venues'));

        return back()->with('success', 'Lugar agregado.');
    }

    public function destroyVenue(Venue $venue): RedirectResponse
    {
        $venue->delete();

        return back()->with('success', 'Lugar eliminado.');
    }

    public function storePrice(Request $request): RedirectResponse
    {
        Price::create($this->validateName($request, 'prices'));

        return back()->with('success', 'Precio agregado.');
    }

    public function destroyPrice(Price $price): RedirectResponse
    {
        $price->delete();

        return back()->with('success', 'Precio eliminado.');
    }

    /**
     * @return array{name: string}
     */
    private function validateName(Request $request, string $table): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique($table, 'name')],
        ], [
            'name.unique' => 'Ese nombre ya está en la lista.',
        ]);
    }
}
