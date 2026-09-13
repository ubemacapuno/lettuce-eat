<?php

use App\Models\Recipe;
use App\Models\User;

test('the recipe index paginates after ten recipes', function () {
    $user = User::factory()->create();
    Recipe::factory()->count(12)->for($user)->create();

    $this->actingAs($user)
        ->get(route('recipes.index'))
        ->assertSuccessful()
        ->assertSee('Pagination Navigation')
        ->assertSee('href="'.route('recipes.index').'?page=2"', false);
});

test('pagination is styled with theme tokens, not laravel defaults', function () {
    $user = User::factory()->create();
    Recipe::factory()->count(12)->for($user)->create();

    $html = $this->actingAs($user)->get(route('recipes.index'))->getContent();

    expect($html)->toContain('border-border')
        ->toContain('bg-card')
        ->not->toContain('bg-gray-')
        ->not->toContain('dark:bg-gray-')
        ->not->toContain('rounded-md text-sm font-medium text-gray-');
});

test('the current page is marked for assistive tech and filled with the primary colour', function () {
    $user = User::factory()->create();
    Recipe::factory()->count(12)->for($user)->create();

    $html = $this->actingAs($user)->get(route('recipes.index'))->getContent();

    expect($html)->toContain('aria-current="page"')
        ->toContain('bg-primary text-primary-foreground');
});

test('the previous control is disabled rather than linked on page one', function () {
    $user = User::factory()->create();
    Recipe::factory()->count(12)->for($user)->create();

    $html = $this->actingAs($user)->get(route('recipes.index'))->getContent();

    expect($html)->toContain('aria-disabled="true"')
        ->not->toContain('rel="prev"');
});

test('page two links back to page one', function () {
    $user = User::factory()->create();
    Recipe::factory()->count(12)->for($user)->create();

    $html = $this->actingAs($user)->get(route('recipes.index', ['page' => 2]))->getContent();

    expect($html)->toContain('rel="prev"')->not->toContain('rel="next"');
});
