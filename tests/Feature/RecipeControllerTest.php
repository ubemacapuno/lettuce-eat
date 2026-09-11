<?php

use App\Models\Recipe;
use App\Models\User;

test('guests are redirected to login', function (string $method, string $uri) {
    $this->$method($uri)->assertRedirect('/login');
})->with([
    'index' => ['get', '/recipes'],
    'create' => ['get', '/recipes/create'],
    'store' => ['post', '/recipes'],
]);

test('a user only sees their own recipes on the index', function () {
    $me = User::factory()->create();
    $mine = Recipe::factory()->for($me)->create(['name' => 'Pinakbet']);
    $theirs = Recipe::factory()->create(['name' => 'Somebody Elses Soup']);

    $this->actingAs($me)
        ->get(route('recipes.index'))
        ->assertSuccessful()
        ->assertSee($mine->name)
        ->assertDontSee($theirs->name);
});

test('the index shows an empty state when there are no recipes', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('recipes.index'))
        ->assertSuccessful()
        ->assertSee('No recipes yet');
});

test('a recipe is saved against the logged in user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('recipes.store'), [
            'name' => 'Pinakbet',
            'rating' => 4.5,
            'ingredients' => '- kalabasa',
            'instructions' => '1. Simmer.',
            'total_minutes' => 45,
            'servings' => 4,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('recipes', [
        'name' => 'Pinakbet',
        'total_minutes' => 45,
        'servings' => 4,
        'user_id' => $user->id,
    ]);
});

test('user_id cannot be forged through the form', function () {
    $me = User::factory()->create();
    $victim = User::factory()->create();

    $this->actingAs($me)->post(route('recipes.store'), [
        'name' => 'Pinakbet',
        'user_id' => $victim->id,
    ]);

    $this->assertDatabaseHas('recipes', [
        'name' => 'Pinakbet',
        'user_id' => $me->id,
    ]);
});

test('invalid ratings are rejected', function (mixed $rating) {
    $this->actingAs(User::factory()->create())
        ->post(route('recipes.store'), ['name' => 'Test', 'rating' => $rating])
        ->assertSessionHasErrors('rating');
})->with([
    'too high' => 12.5,
    'too low' => 0.5,
    'too precise' => 4.257,
    'not a number' => 'delicious',
]);

test('a name is required', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('recipes.store'), ['name' => ''])
        ->assertSessionHasErrors('name');
});

test('out of range timings and servings are rejected', function (string $field, mixed $value) {
    $this->actingAs(User::factory()->create())
        ->post(route('recipes.store'), ['name' => 'Test', $field => $value])
        ->assertSessionHasErrors($field);
})->with([
    'a week and a half of cooking' => ['total_minutes', 20000],
    'zero minutes' => ['total_minutes', 0],
    'more servings than the column holds' => ['servings', 256],
    'zero servings' => ['servings', 0],
]);

test('make_again is saved', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('recipes.store'), [
        'name' => 'Pinakbet',
        'make_again' => '1',
    ]);

    $this->assertDatabaseHas('recipes', [
        'name' => 'Pinakbet',
        'make_again' => true,
    ]);
});

test('a user can update their own recipe', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->for($user)->create(['name' => 'Old Name']);

    $this->actingAs($user)
        ->put(route('recipes.update', $recipe), [
            'name' => 'New Name',
            'rating' => 3.5,
        ])
        ->assertRedirect(route('recipes.show', $recipe));

    expect($recipe->fresh()->name)->toBe('New Name');
});

test('a user can delete their own recipe', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->for($user)->create();

    $this->actingAs($user)
        ->delete(route('recipes.destroy', $recipe))
        ->assertRedirect(route('recipes.index'));

    $this->assertDatabaseMissing('recipes', ['id' => $recipe->id]);
});

test('the create form renders with a star rating island', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('recipes.create'))
        ->assertSuccessful()
        ->assertSee('data-vue="StarRating"', false);
});

test('the edit form renders prefilled', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->for($user)->create([
        'name' => 'Pinakbet',
        'servings' => 4,
    ]);

    $this->actingAs($user)
        ->get(route('recipes.edit', $recipe))
        ->assertSuccessful()
        ->assertSee('Pinakbet')
        ->assertSee('value="4"', false);
});

test('the show page renders markdown instead of raw asterisks', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->for($user)->create([
        'ingredients' => "- **2 cups** flour\n- 1 tsp salt",
        'instructions' => '1. Mix it.',
    ]);

    $this->actingAs($user)
        ->get(route('recipes.show', $recipe))
        ->assertSuccessful()
        ->assertSee('<strong>2 cups</strong>', false)
        ->assertSee('<ol>', false)
        ->assertDontSee('**2 cups**');
});

test('html inside markdown is stripped on the show page', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->for($user)->create([
        'ingredients' => "<script>alert('xss')</script>\n\n- salt",
    ]);

    $this->actingAs($user)
        ->get(route('recipes.show', $recipe))
        ->assertSuccessful()
        ->assertSee('<li>salt</li>', false)
        ->assertDontSee('<script>alert', false);
});

test('the show page prompts for details when nothing is written down', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->for($user)->create([
        'ingredients' => null,
        'instructions' => null,
    ]);

    $this->actingAs($user)
        ->get(route('recipes.show', $recipe))
        ->assertSuccessful()
        ->assertSee('Nothing written down yet');
});
