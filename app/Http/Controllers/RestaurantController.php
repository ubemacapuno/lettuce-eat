<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class RestaurantController extends Controller
{
    public function index(Request $request): View
    {
        return view('restaurants.index', [
            'restaurants' => $request->user()
                ->restaurants()
                ->withCount('dishes')
                ->latest()
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('restaurants.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules(), $this->messages());

        $restaurant = $request->user()->restaurants()->create($validated);

        return redirect()
            ->route('restaurants.show', $restaurant)
            ->with('success', "{$restaurant->name} added!");
    }

    public function show(Restaurant $restaurant): View
    {
        Gate::authorize('view', $restaurant);

        $restaurant->load('dishes');

        return view('restaurants.show', ['restaurant' => $restaurant]);
    }

    public function edit(Restaurant $restaurant): View
    {
        Gate::authorize('update', $restaurant);

        return view('restaurants.edit', ['restaurant' => $restaurant]);
    }

    public function update(Request $request, Restaurant $restaurant): RedirectResponse
    {
        Gate::authorize('update', $restaurant);

        $restaurant->update($request->validate($this->rules(), $this->messages()));

        return redirect()
            ->route('restaurants.show', $restaurant)
            ->with('success', 'Updated!');
    }

    public function destroy(Restaurant $restaurant): RedirectResponse
    {
        Gate::authorize('delete', $restaurant);

        $restaurant->delete();

        return redirect()
            ->route('restaurants.index')
            ->with('success', 'Deleted.');
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'rating' => ['nullable', 'numeric', 'between:1,5', 'multiple_of:0.5'],
            'notes' => ['nullable', 'string'],
            'street_address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'name.required' => 'What is the place called?',
            'rating.between' => 'Rating must be between 1 and 5 stars.',
            'rating.multiple_of' => 'Ratings go in half stars, like 4 or 4.5.',
        ];
    }
}
