<?php

use App\Models\Restaurant;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('a half-star rating is accepted', function (string $rating) {
    $this->actingAs($this->user)
        ->post(route('restaurants.store'), ['name' => 'Portillo\'s', 'rating' => $rating])
        ->assertSessionHasNoErrors();
})->with(['1', '1.5', '2.5', '4', '4.5', '5']);

test('a rating between half stars is rejected', function (string $rating) {
    $this->actingAs($this->user)
        ->post(route('restaurants.store'), ['name' => 'Portillo\'s', 'rating' => $rating])
        ->assertSessionHasErrors('rating');
})->with(['1.8', '3.8', '4.2', '4.9', '2.1']);

test('a dish rating between half stars is rejected', function () {
    $restaurant = Restaurant::factory()->for($this->user)->create();

    $this->actingAs($this->user)
        ->post(route('restaurants.dishes.store', $restaurant), ['name' => 'Cake Shake', 'rating' => '3.8'])
        ->assertSessionHasErrors('rating');
});

test('the factory only produces half-star ratings', function () {
    Restaurant::factory()->count(60)->for($this->user)->create()
        ->each(fn (Restaurant $restaurant) => expect(fmod((float) $restaurant->rating * 2, 1.0))->toBe(0.0));
});

test('rating stays optional', function () {
    $this->actingAs($this->user)
        ->post(route('restaurants.store'), ['name' => 'Portillo\'s', 'rating' => ''])
        ->assertSessionHasNoErrors();
});
