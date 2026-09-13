<?php

use App\Models\User;

test('every layout preloads the self-hosted fonts', function (string $route) {
    $response = $this->actingAs(User::factory()->create())->get($route);

    $response->assertSuccessful()
        ->assertSee('rel="preload" as="font" type="font/woff2" href="/fonts/dm-sans-latin.woff2"', false);
})->with([
    'app layout' => '/dashboard',
    'welcome' => '/',
]);

test('no layout reaches out to a third party font cdn', function (string $route) {
    $response = $this->get($route);

    expect($response->getContent())
        ->not->toContain('fonts.bunny.net')
        ->not->toContain('fonts.googleapis.com')
        ->not->toContain('fonts.gstatic.com');
})->with([
    'welcome' => '/',
    'login' => '/login',
    'register' => '/register',
]);

test('the font files the layouts preload actually exist', function (string $file) {
    expect(public_path('fonts/'.$file))->toBeFile();
})->with([
    'dm-sans-latin.woff2',
    'space-mono-400-latin.woff2',
    'space-mono-700-latin.woff2',
]);
