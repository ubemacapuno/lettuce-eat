<?php

use App\Models\User;

test('a forwarded request is treated as secure so urls are generated over https', function () {
    $response = $this->withServerVariables([
        'HTTP_X_FORWARDED_PROTO' => 'https',
        'HTTP_X_FORWARDED_HOST' => 'lettuce-eat.example.ts.net',
    ])->get(route('login'));

    $response->assertOk()
        ->assertSee('https://lettuce-eat.example.ts.net/login', escape: false)
        ->assertDontSee('http://lettuce-eat.example.ts.net/login', escape: false);
});

test('an authenticated page keeps forwarded https links', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withServerVariables([
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_HOST' => 'lettuce-eat.example.ts.net',
        ])
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('https://lettuce-eat.example.ts.net/restaurants', escape: false);
});

test('the health endpoint the container healthcheck hits is reachable', function () {
    $this->get('/up')->assertOk();
});
