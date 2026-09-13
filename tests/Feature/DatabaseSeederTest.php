<?php

use App\Models\Dish;
use App\Models\Recipe;
use App\Models\Restaurant;

beforeEach(function () {
    $this->seed();
});

test('the seeder fills in timing and servings on most recipes', function () {
    expect(Recipe::count())->toBe(15)
        ->and(Recipe::whereNotNull('total_minutes')->count())->toBe(12)
        ->and(Recipe::whereNotNull('servings')->count())->toBe(12)
        ->and(Recipe::whereNotNull('source_url')->count())->toBe(12);
});

test('seeded recipes are a mix of make again and not', function () {
    expect(Recipe::where('make_again', true)->count())->toBeGreaterThan(0)
        ->and(Recipe::where('make_again', false)->count())->toBeGreaterThan(0);
});

test('the seeder rates and judges the dishes it creates', function () {
    expect(Dish::count())->toBe(17)
        ->and(Dish::whereNotNull('rating')->count())->toBeGreaterThan(0)
        ->and(Dish::whereNotNull('notes')->count())->toBeGreaterThan(0);
});

test('seeded dishes are a mix of order again and not', function () {
    expect(Dish::where('order_again', true)->count())->toBeGreaterThan(0)
        ->and(Dish::where('order_again', false)->count())->toBeGreaterThan(0);
});

test('every seeded record belongs to the test user', function () {
    expect(Restaurant::whereNull('user_id')->count())->toBe(0)
        ->and(Recipe::whereNull('user_id')->count())->toBe(0)
        ->and(Dish::whereNull('restaurant_id')->count())->toBe(0);
});
