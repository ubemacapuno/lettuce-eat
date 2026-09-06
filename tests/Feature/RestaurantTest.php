<?php

use App\Models\Dish;
use App\Models\Restaurant;
use App\Models\User;

test('a restaurant belongs to the user who added it', function () {
    $user = User::factory()->create();

    $restaurant = Restaurant::factory()->for($user)->create();

    expect($restaurant->user)->toBeInstanceOf(User::class)
        ->and($restaurant->user->id)->toBe($user->id);
});

test('a restaurant has many dishes', function () {
    $restaurant = Restaurant::factory()->hasDishes(3)->create();

    expect($restaurant->dishes)->toHaveCount(3)
        ->and($restaurant->dishes->first())->toBeInstanceOf(Dish::class);
});

test('one users restaurants are never visible to another user', function () {
    $mine = User::factory()->has(Restaurant::factory()->count(2))->create();
    User::factory()->has(Restaurant::factory()->count(5))->create();

    expect($mine->restaurants)->toHaveCount(2)
        ->and(Restaurant::count())->toBe(7);
});

test('a restaurant stores its rating to the tenths place', function (float|int $given, string $stored) {
    $restaurant = Restaurant::factory()->create(['rating' => $given]);

    expect($restaurant->fresh()->rating)->toBe($stored);
})->with([
    'a tenth' => [4.2, '4.2'],
    'another tenth' => [3.8, '3.8'],
    'the top of the scale' => [5, '5.0'],
    'the bottom of the scale' => [1, '1.0'],
    'rounds past a tenth' => [4.25, '4.3'],
]);

test('a restaurant can be saved before it has been rated', function () {
    $restaurant = Restaurant::factory()->create(['rating' => null]);

    expect($restaurant->fresh()->rating)->toBeNull();
});

test('a restaurant location field is optional', function (string $field) {
    $restaurant = Restaurant::factory()->create([$field => null]);

    expect($restaurant->fresh()->{$field})->toBeNull();
})->with([
    'street address' => 'street_address',
    'city' => 'city',
    'state' => 'state',
]);

test('deleting a restaurant deletes its dishes', function () {
    $restaurant = Restaurant::factory()->hasDishes(2)->create();

    $restaurant->delete();

    expect(Dish::count())->toBe(0);
});

test('deleting a dish leaves its restaurant alone', function () {
    $restaurant = Restaurant::factory()->hasDishes(2)->create();

    $restaurant->dishes->first()->delete();

    expect(Restaurant::count())->toBe(1)
        ->and($restaurant->fresh()->dishes)->toHaveCount(1);
});

test('deleting a user deletes their restaurants and dishes', function () {
    $user = User::factory()->create();
    Restaurant::factory()->for($user)->hasDishes(2)->create();

    $user->delete();

    expect(Restaurant::count())->toBe(0)
        ->and(Dish::count())->toBe(0);
});

test('a user can reach every dish they have logged', function () {
    $user = User::factory()->create();
    Restaurant::factory()->for($user)->hasDishes(2)->create();
    Restaurant::factory()->for($user)->hasDishes(3)->create();

    expect($user->dishes)->toHaveCount(5);
});

test('the owning user cannot be mass assigned', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();

    $restaurant = $owner->restaurants()->create([
        'name' => "Portillo's",
        'user_id' => $attacker->id,
    ]);

    expect($restaurant->user_id)->toBe($owner->id);
});
