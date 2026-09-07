<?php

use App\Models\Dish;
use App\Models\Restaurant;
use App\Models\User;

test('the navigation is rendered server-side, not mounted by javascript', function () {
    $user = User::factory()->create(['name' => 'Test User']);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk()
        ->assertSee('Lettuce Eat')
        ->assertSee(route('restaurants.index'))
        ->assertSee(route('profile.edit'))
        ->assertSee($user->name);

    /** The nav used to be an empty div hydrated by Vue, which flashed on every page load. */
    $response->assertDontSee('data-vue="NavBar"', escape: false);
});

test('the checkbox component compiles instead of leaking its own tag', function () {
    $user = User::factory()->create();
    $restaurant = Restaurant::factory()->for($user)->create();
    $dish = Dish::factory()->for($restaurant)->create(['order_again' => true]);

    $response = $this->actingAs($user)->get(route('dishes.edit', $dish));

    $response->assertOk()
        ->assertSee('<input type="checkbox" id="order_again"', escape: false)
        ->assertSee('checked="checked"', escape: false);

    /** A Blade directive inside the component tag stops it compiling and ships this literally. */
    $response->assertDontSee('<x-checkbox', escape: false);
});

test('an unchecked box renders without the checked attribute', function () {
    $user = User::factory()->create();
    $restaurant = Restaurant::factory()->for($user)->create();
    $dish = Dish::factory()->for($restaurant)->create(['order_again' => false]);

    $this->actingAs($user)
        ->get(route('dishes.edit', $dish))
        ->assertOk()
        ->assertDontSee('checked="checked"', escape: false);
});
