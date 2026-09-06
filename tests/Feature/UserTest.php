<?php

use App\Models\Dish;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\QueryException;

test('two users cannot share an email address', function () {
    User::factory()->create(['email' => 'corey@test.com']);
    User::factory()->create(['email' => 'corey@test.com']);
})->throws(QueryException::class);

test('a new user starts with no restaurants and no dishes', function () {
    $user = User::factory()->create();

    expect($user->restaurants)->toBeEmpty()
        ->and($user->dishes)->toBeEmpty();
});

test('a users dishes never include another users dishes', function () {
    $mine = User::factory()->create();
    Restaurant::factory()->for($mine)->hasDishes(2)->create();

    $theirs = User::factory()->create();
    Restaurant::factory()->for($theirs)->hasDishes(7)->create();

    expect($mine->dishes)->toHaveCount(2)
        ->and($theirs->dishes)->toHaveCount(7)
        ->and(Dish::count())->toBe(9);
});
