<?php

use App\Models\Dish;
use App\Models\Restaurant;
use App\Models\User;

test('a dish is attached to the restaurant in the url', function () {
    $user = User::factory()->create();
    $restaurant = Restaurant::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('restaurants.dishes.store', $restaurant), [
            'name' => 'Italian Beef',
            'rating' => 4.5,
            'notes' => 'ask for the gravy on the side',
            'order_again' => '1',
        ])
        ->assertRedirect(route('restaurants.show', $restaurant));

    $this->assertDatabaseHas('dishes', [
        'name' => 'Italian Beef',
        'restaurant_id' => $restaurant->id,
        'order_again' => true,
    ]);
});

test('an unchecked order_again saves as false', function () {
    $user = User::factory()->create();
    $restaurant = Restaurant::factory()->for($user)->create();

    $this->actingAs($user)->post(route('restaurants.dishes.store', $restaurant), [
        'name' => 'Cake Shake',
    ]);

    expect($restaurant->dishes()->first()->order_again)->toBeFalse();
});

test('unchecking order_again on an edit turns it back off', function () {
    $user = User::factory()->create();
    $dish = Dish::factory()
        ->for(Restaurant::factory()->for($user))
        ->create(['order_again' => true]);

    $this->actingAs($user)->put(route('dishes.update', $dish), [
        'name' => $dish->name,
        'order_again' => '0',
    ]);

    expect($dish->fresh()->order_again)->toBeFalse();
});

test('invalid dish ratings are rejected', function (mixed $rating) {
    $user = User::factory()->create();
    $restaurant = Restaurant::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('restaurants.dishes.store', $restaurant), ['name' => 'Test', 'rating' => $rating])
        ->assertSessionHasErrors('rating');
})->with([
    'too high' => 12.5,
    'too low' => 0.5,
    'too precise' => 4.257,
    'not a number' => 'tasty',
]);

test('dishes show up on the restaurant page', function () {
    $user = User::factory()->create();
    $restaurant = Restaurant::factory()->for($user)->create();
    Dish::factory()->for($restaurant)->create(['name' => 'Italian Beef']);

    $this->actingAs($user)
        ->get(route('restaurants.show', $restaurant))
        ->assertSuccessful()
        ->assertSee('Italian Beef');
});

test('a user can delete their own dish', function () {
    $user = User::factory()->create();
    $dish = Dish::factory()->for(Restaurant::factory()->for($user))->create();

    $this->actingAs($user)
        ->delete(route('dishes.destroy', $dish))
        ->assertRedirect(route('restaurants.show', $dish->restaurant));

    $this->assertDatabaseMissing('dishes', ['id' => $dish->id]);
});

test('a user cannot add a dish to another users restaurant', function () {
    $theirs = Restaurant::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('restaurants.dishes.store', $theirs), ['name' => 'Hacked'])
        ->assertForbidden();
});

test('a user cannot touch another users dish', function (string $method, string $routeName) {
    $theirs = Dish::factory()->create();

    $this->actingAs(User::factory()->create())
        ->$method(route($routeName, $theirs), ['name' => 'Hacked'])
        ->assertForbidden();
})->with([
    'edit' => ['get', 'dishes.edit'],
    'update' => ['put', 'dishes.update'],
    'destroy' => ['delete', 'dishes.destroy'],
]);

test('the dish create form renders', function () {
    $user = User::factory()->create();
    $restaurant = Restaurant::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('restaurants.dishes.create', $restaurant))
        ->assertSuccessful()
        ->assertSee('data-vue="StarRating"', false);
});

test('the dish edit form renders prefilled', function () {
    $user = User::factory()->create();
    $dish = Dish::factory()
        ->for(Restaurant::factory()->for($user))
        ->create(['name' => 'Italian Beef']);

    $this->actingAs($user)
        ->get(route('dishes.edit', $dish))
        ->assertSuccessful()
        ->assertSee('Italian Beef');
});
