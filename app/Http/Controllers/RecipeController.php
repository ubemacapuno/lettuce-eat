<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class RecipeController extends Controller
{
    public function index(Request $request): View
    {
        return view('recipes.index', [
            'recipes' => $request->user()
                ->recipes()
                ->latest()
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('recipes.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules(),
            $this->messages());

        $recipe = $request->user()->recipes()->create($validated);

        return redirect()
            ->route('recipes.show', $recipe)
            ->with('success', "{$recipe->name} added!");
    }

    public function show(Recipe $recipe): View
    {
        Gate::authorize('view', $recipe);

        return view('recipes.show', ['recipe' => $recipe]);
    }

    public function edit(Recipe $recipe): View
    {
        Gate::authorize('update', $recipe);

        return view('recipes.edit', ['recipe' => $recipe]);
    }

    public function update(Request $request, Recipe $recipe): RedirectResponse
    {
        Gate::authorize('update', $recipe);

        $recipe->update($request->validate($this->rules(), $this->messages()));

        return redirect()
            ->route('recipes.show', $recipe)
            ->with('success', $recipe->wasChanged() ? 'Updated!' : 'No changes to save.');
    }

    public function destroy(Recipe $recipe): RedirectResponse
    {
        Gate::authorize('delete', $recipe);

        $recipe->delete();

        return redirect()
            ->route('recipes.index')
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
            'ingredients' => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
            'source_url' => ['nullable', 'string'],
            'total_minutes' => ['nullable', 'integer', 'min:1', 'max:10080'],
            'servings' => ['nullable', 'integer', 'min:1', 'max:255'],
            'make_again' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'name.required' => 'What is the recipe called?',
            'rating.between' => 'Rating must be between 1 and 5 stars.',
            'rating.multiple_of' => 'Ratings go in half stars, like 4 or 4.5.',
        ];
    }
}
