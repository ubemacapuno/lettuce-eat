<?php

use App\Models\Recipe;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('the owner is allowed every ability', function (string $ability) {
    $me = User::factory()->create();
    $mine = Recipe::factory()->for($me)->create();

    expect(Gate::forUser($me)->allows($ability, $mine))->toBeTrue();
})->with(['view', 'update', 'delete']);

test('a stranger is denied every ability', function (string $ability) {
    $theirs = Recipe::factory()->create();

    expect(Gate::forUser(User::factory()->create())->allows($ability, $theirs))->toBeFalse();
})->with(['view', 'update', 'delete']);

test('a user cannot touch another users recipe', function (string $method, string $routeName) {
    $theirs = Recipe::factory()->create();

    $this->actingAs(User::factory()->create())
        ->$method(route($routeName, $theirs), ['name' => 'Hacked'])
        ->assertForbidden();
})->with([
    'show' => ['get', 'recipes.show'],
    'edit' => ['get', 'recipes.edit'],
    'update' => ['put', 'recipes.update'],
    'destroy' => ['delete', 'recipes.destroy'],
]);
