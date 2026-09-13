<?php

use App\Models\Dish;
use App\Models\Restaurant;
use App\Models\User;

test('guests are redirected to login', function (string $method, string $uri) {
    $this->$method($uri)->assertRedirect('/login');
})->with([
    'index' => ['get', '/restaurants'],
    'create' => ['get', '/restaurants/create'],
    'store' => ['post', '/restaurants'],
]);

test('a user only sees their own restaurants on the index', function () {
    $me = User::factory()->create();
    $mine = Restaurant::factory()->for($me)->create(['name' => 'Portillos']);
    $theirs = Restaurant::factory()->create(['name' => 'Somewhere Else']);

    $this->actingAs($me)
        ->get(route('restaurants.index'))
        ->assertSuccessful()
        ->assertSee($mine->name)
        ->assertDontSee($theirs->name);
});

test('the index shows an empty state when there are no restaurants', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('restaurants.index'))
        ->assertSuccessful()
        ->assertSee('No restaurants yet');
});

test('a restaurant is saved against the logged in user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('restaurants.store'), [
            'name' => 'Portillos',
            'rating' => 4.5,
            'street_address' => '123 Beef St',
            'city' => 'Gilbert',
            'state' => 'AZ',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('restaurants', [
        'name' => 'Portillos',
        'city' => 'Gilbert',
        'user_id' => $user->id,
    ]);
});

test('user_id cannot be forged through the form', function () {
    $me = User::factory()->create();
    $victim = User::factory()->create();

    $this->actingAs($me)->post(route('restaurants.store'), [
        'name' => 'Portillos',
        'user_id' => $victim->id,
    ]);

    $this->assertDatabaseHas('restaurants', [
        'name' => 'Portillos',
        'user_id' => $me->id,
    ]);
});

test('invalid ratings are rejected', function (mixed $rating) {
    $this->actingAs(User::factory()->create())
        ->post(route('restaurants.store'), ['name' => 'Test', 'rating' => $rating])
        ->assertSessionHasErrors('rating');
})->with([
    'too high' => 12.5,
    'too low' => 0.5,
    'too precise' => 4.257,
    'not a number' => 'delicious',
]);

test('a name is required', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('restaurants.store'), ['name' => ''])
        ->assertSessionHasErrors('name');
});

test('a user can update their own restaurant', function () {
    $user = User::factory()->create();
    $restaurant = Restaurant::factory()->for($user)->create(['name' => 'Old Name']);

    $this->actingAs($user)
        ->put(route('restaurants.update', $restaurant), [
            'name' => 'New Name',
            'rating' => 3.5,
        ])
        ->assertRedirect(route('restaurants.show', $restaurant));

    expect($restaurant->fresh()->name)->toBe('New Name');
});

test('a user can delete their own restaurant and its dishes go with it', function () {
    $user = User::factory()->create();
    $restaurant = Restaurant::factory()->for($user)->hasDishes(3)->create();

    $this->actingAs($user)
        ->delete(route('restaurants.destroy', $restaurant))
        ->assertRedirect(route('restaurants.index'));

    $this->assertDatabaseMissing('restaurants', ['id' => $restaurant->id]);
    $this->assertDatabaseCount('dishes', 0);
});

test('a user cannot touch another users restaurant', function (string $method, string $routeName) {
    $theirs = Restaurant::factory()->create();

    $this->actingAs(User::factory()->create())
        ->$method(route($routeName, $theirs), ['name' => 'Hacked'])
        ->assertForbidden();
})->with([
    'show' => ['get', 'restaurants.show'],
    'edit' => ['get', 'restaurants.edit'],
    'update' => ['put', 'restaurants.update'],
    'destroy' => ['delete', 'restaurants.destroy'],
]);

test('the create form renders with a star rating island', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('restaurants.create'))
        ->assertSuccessful()
        ->assertSee('data-vue="StarRating"', false);
});

test('the edit form renders prefilled', function () {
    $user = User::factory()->create();
    $restaurant = Restaurant::factory()->for($user)->create([
        'name' => 'Portillos',
        'city' => 'Gilbert',
    ]);

    $this->actingAs($user)
        ->get(route('restaurants.edit', $restaurant))
        ->assertSuccessful()
        ->assertSee('Portillos')
        ->assertSee('Gilbert');
});

test('dishes are listed alphabetically regardless of case', function () {
    $user = User::factory()->create();
    $restaurant = Restaurant::factory()->for($user)->create();

    foreach (['zeppole', 'Arancini', 'cake Shake'] as $name) {
        Dish::factory()->for($restaurant)->create(['name' => $name, 'order_again' => false]);
    }

    $this->actingAs($user)
        ->get(route('restaurants.show', $restaurant))
        ->assertSuccessful()
        ->assertSeeInOrder(['Arancini', 'cake Shake', 'zeppole']);
});

test('dishes worth repeating float above the rest', function () {
    $user = User::factory()->create();
    $restaurant = Restaurant::factory()->for($user)->create();

    Dish::factory()->for($restaurant)->create(['name' => 'Arancini', 'order_again' => false]);
    Dish::factory()->for($restaurant)->create(['name' => 'Zeppole', 'order_again' => true]);
    Dish::factory()->for($restaurant)->create(['name' => 'Bomboloni', 'order_again' => true]);

    $this->actingAs($user)
        ->get(route('restaurants.show', $restaurant))
        ->assertSuccessful()
        ->assertSeeInOrder(['Bomboloni', 'Zeppole', 'Arancini']);
});

test('the show page summarises the dishes logged at a restaurant', function () {
    $user = User::factory()->create();
    $restaurant = Restaurant::factory()->for($user)->create();

    Dish::factory()->for($restaurant)->create(['rating' => 4.5, 'order_again' => true]);
    Dish::factory()->for($restaurant)->create(['rating' => 3.5, 'order_again' => true]);
    Dish::factory()->for($restaurant)->create(['rating' => null, 'order_again' => false]);

    $this->actingAs($user)
        ->get(route('restaurants.show', $restaurant))
        ->assertSuccessful()
        ->assertSee('3 dishes')
        ->assertSee('4.0 avg')
        ->assertSee('2 worth repeating');
});

test('unrated dishes are left out of the average', function () {
    $user = User::factory()->create();
    $restaurant = Restaurant::factory()->for($user)->create();

    Dish::factory()->for($restaurant)->create(['rating' => 5.0]);
    Dish::factory()->for($restaurant)->create(['rating' => null]);

    $this->actingAs($user)
        ->get(route('restaurants.show', $restaurant))
        ->assertSuccessful()
        ->assertSee('5.0 avg')
        ->assertDontSee('2.5 avg');
});

test('a restaurant with no dishes shows no dish summary', function () {
    $user = User::factory()->create();
    $restaurant = Restaurant::factory()->for($user)->create();

    $response = $this->actingAs($user)
        ->get(route('restaurants.show', $restaurant))
        ->assertSuccessful()
        ->assertSee('No dishes logged here yet');

    expect($response->getContent())
        ->not->toMatch('/\\d+ dishe?s?\\b/')
        ->not->toMatch('/\\d+ worth repeating/');
});

test('the index orders restaurants alphabetically', function () {
    $user = User::factory()->create();

    foreach (['zizzis', 'Alinea', 'portillos'] as $name) {
        Restaurant::factory()->for($user)->create(['name' => $name]);
    }

    $this->actingAs($user)
        ->get(route('restaurants.index'))
        ->assertSuccessful()
        ->assertSeeInOrder(['Alinea', 'portillos', 'zizzis']);
});
