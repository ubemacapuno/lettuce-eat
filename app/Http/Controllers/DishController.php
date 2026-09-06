<?php

namespace App\Http\Controllers;

use App\Models\Dish;
use App\Models\Restaurant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DishController extends Controller
{
    public function create(Restaurant $restaurant): View
    {
        Gate::authorize('update', $restaurant);

        return view('dishes.create', ['restaurant' => $restaurant]);
    }

    public function store(Request $request, Restaurant $restaurant): RedirectResponse
    {
        Gate::authorize('update', $restaurant);

        $validated = $request->validate($this->rules(), $this->messages());

        $restaurant->dishes()->create([
            ...$validated,
            'order_again' => $request->boolean('order_again'),
        ]);

        return redirect()
            ->route('restaurants.show', $restaurant)
            ->with('success', 'Dish added!');
    }

    public function edit(Dish $dish): View
    {
        Gate::authorize('update', $dish);

        return view('dishes.edit', ['dish' => $dish]);
    }

    public function update(Request $request, Dish $dish): RedirectResponse
    {
        Gate::authorize('update', $dish);

        $validated = $request->validate($this->rules(), $this->messages());

        $dish->update([
            ...$validated,
            'order_again' => $request->boolean('order_again'),
        ]);

        return redirect()
            ->route('restaurants.show', $dish->restaurant)
            ->with('success', 'Dish updated!');
    }

    public function destroy(Dish $dish): RedirectResponse
    {
        Gate::authorize('delete', $dish);

        $restaurant = $dish->restaurant;

        $dish->delete();

        return redirect()
            ->route('restaurants.show', $restaurant)
            ->with('success', 'Dish removed.');
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'rating' => ['nullable', 'numeric', 'between:1,5', 'decimal:0,1'],
            'notes' => ['nullable', 'string'],
            'order_again' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'name.required' => 'What is the dish called?',
            'rating.between' => 'Rating must be between 1 and 5 stars.',
            'rating.decimal' => 'Use at most one decimal place, like 4.5.',
        ];
    }
}
