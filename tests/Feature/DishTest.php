<?php

use App\Models\Dish;
use App\Models\Restaurant;

test('a dish belongs to a restaurant', function () {
    $restaurant = Restaurant::factory()->create();

    $dish = Dish::factory()->for($restaurant)->create();

    expect($dish->restaurant)->toBeInstanceOf(Restaurant::class)
        ->and($dish->restaurant->id)->toBe($restaurant->id);
});

test('a dish stores its rating to the tenths place', function (float|int $given, string $stored) {
    $dish = Dish::factory()->create(['rating' => $given]);

    expect($dish->fresh()->rating)->toBe($stored);
})->with([
    'a tenth' => [4.5, '4.5'],
    'another tenth' => [3.8, '3.8'],
    'the top of the scale' => [5, '5.0'],
    'the bottom of the scale' => [1, '1.0'],
    'rounds past a tenth' => [4.25, '4.3'],
]);

test('a dish can be saved before it has been rated', function () {
    $dish = Dish::factory()->create(['rating' => null]);

    expect($dish->fresh()->rating)->toBeNull();
});

test('order again reads back as a real boolean', function (bool $given, bool $stored) {
    $dish = Dish::factory()->create(['order_again' => $given]);

    expect($dish->fresh()->order_again)->toBe($stored);
})->with([
    'would order again' => [true, true],
    'would not order again' => [false, false],
]);

test('order again defaults to false when it is left off', function () {
    $dish = Restaurant::factory()->create()->dishes()->create(['name' => 'Cake Shake']);

    expect($dish->fresh()->order_again)->toBeFalse();
});

test('a dish records its rating, verdict and notes together', function () {
    $restaurant = Restaurant::factory()->create(['name' => "Portillo's"]);

    $dish = $restaurant->dishes()->create([
        'name' => 'Italian Beef',
        'rating' => 4.5,
        'order_again' => true,
        'notes' => 'Ask for the gravy on the side, add hot peppers.',
    ]);

    expect($dish->restaurant->name)->toBe("Portillo's")
        ->and($dish->rating)->toBe('4.5')
        ->and($dish->order_again)->toBeTrue()
        ->and($dish->notes)->toContain('hot peppers');
});

test('the parent restaurant cannot be mass assigned', function () {
    $mine = Restaurant::factory()->create();
    $other = Restaurant::factory()->create();

    $dish = $mine->dishes()->create([
        'name' => 'Cake Shake',
        'restaurant_id' => $other->id,
    ]);

    expect($dish->restaurant_id)->toBe($mine->id);
});

test('the reviewed state produces a dish that has been judged', function () {
    $dishes = Dish::factory()->count(40)->reviewed()->create();

    $dishes->each(function (Dish $dish) {
        expect($dish->order_again)->toBeBool();

        if ($dish->rating !== null) {
            expect((float) $dish->rating)
                ->toBeGreaterThanOrEqual(1.0)
                ->toBeLessThanOrEqual(5.0)
                ->and(fmod((float) $dish->rating * 10, 5))->toBe(0.0);
        }
    });

    expect($dishes->pluck('order_again')->unique())->toHaveCount(2)
        ->and($dishes->whereNotNull('rating'))->not->toBeEmpty();
});
