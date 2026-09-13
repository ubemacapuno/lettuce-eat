<?php

namespace Database\Factories;

use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Recipe>
 */
class RecipeFactory extends Factory
{
    /**
     * Recipes and instructions are markdown compatible,
     * so adding markdown to the factory:
     */
    public const array RECIPES = [
        [
            'name' => 'Weeknight Garlic Butter Pasta',
            'ingredients' => <<<'MD'
                ### Pasta

                - **1 lb** spaghetti
                - **6 cloves** garlic, thinly sliced
                - **1/2 cup** unsalted butter
                - *Kosher salt, to taste*

                ### To finish

                - 1 cup grated parmesan
                - Handful of flat-leaf parsley, chopped
                - Red pepper flakes
                MD,
            'instructions' => <<<'MD'
                ### Before you start

                Get the pasta water going *first* — everything else takes about as
                long as the boil does.

                1. Salt the water heavily and cook the spaghetti until **just shy**
                   of al dente.
                2. Melt the butter over low heat and add the garlic. Do **not** let
                   it brown.
                3. Reserve **1 cup** of pasta water, then drain.
                4. Toss the pasta in the butter with a splash of the reserved water
                   until the sauce goes glossy.
                5. Off the heat, add the parmesan a handful at a time.

                Finish with parsley and as many pepper flakes as you can defend.
                MD,
        ],
        [
            'name' => 'Pinakbet',
            'ingredients' => <<<'MD'
                ### Vegetables

                - **1** small kalabasa (squash), peeled and cubed
                - **2** Chinese eggplants (talong), sliced on the bias
                - **1 bunch** sitaw (long beans), cut into 2-inch pieces
                - **1** ampalaya (bitter melon), seeded and sliced
                - **8** okra, stems trimmed
                - **2** tomatoes, quartered

                ### Aromatics and seasoning

                - **1/2 lb** pork belly, cut into strips
                - **1** onion, sliced
                - **3 cloves** garlic, crushed
                - **1 thumb** ginger, julienned
                - **3 tbsp** bagoong (fermented shrimp paste)
                - *1 cup hugas bigas (rice washing water), or plain water*
                MD,
            'instructions' => <<<'MD'
                ### Build the base

                1. Render the pork belly in a wide pot until the fat runs out and
                   the edges crisp.
                2. Add the garlic, onion, and ginger. Cook until softened.
                3. Stir in the bagoong and fry it for a minute to take the raw
                   edge off.
                4. Add the tomatoes and the hugas bigas, then bring to a simmer.

                ### Layer and steam

                Add the vegetables in order of how long they take, *without
                stirring*: kalabasa on the bottom, then sitaw and okra, with the
                ampalaya and talong on top.

                Cover and simmer **15–20 minutes**, until the kalabasa gives way
                to a fork.

                > Don't stir it. Shake the pot, or fold once gently at the very
                > end; stirring turns the kalabasa to mush and spreads the
                > bitterness from the ampalaya through everything.

                Taste before adding any salt. The bagoong is doing most of the work.
                MD,
        ],
        [
            'name' => 'Overnight Oats',
            'ingredients' => <<<'MD'
                - 1/2 cup rolled oats
                - 1/2 cup whole milk
                - 1 tbsp maple syrup
                - Pinch of salt
                MD,
            'instructions' => <<<'MD'
                Stir everything together in a jar, seal it, and refrigerate
                **overnight**.

                In the morning, top with whatever fruit is around.
                MD,
        ],
    ];

    public function definition(): array
    {
        $recipe = fake()->randomElement(self::RECIPES);

        return [
            'user_id' => User::factory(),
            'name' => fake()->boolean() ? $recipe['name'] : Str::title(fake()->words(3, true)),
            'rating' => fake()->numberBetween(2, 10) / 2,
            'ingredients' => $recipe['ingredients'],
            'instructions' => $recipe['instructions'],
        ];
    }

    public function withDetails(): static
    {
        return $this->state(fn (array $attributes): array => [
            'source_url' => 'https://example.com/recipes/'.Str::slug($attributes['name'] ?? fake()->word()),
            'total_minutes' => fake()->numberBetween(10, 180),
            'servings' => fake()->numberBetween(1, 8),
            'make_again' => true,
        ]);
    }
}
