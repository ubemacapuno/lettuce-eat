<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <link rel="preload" as="font" type="font/woff2" href="/fonts/dm-sans-latin.woff2" crossorigin>
    <link rel="preload" as="font" type="font/woff2" href="/fonts/space-mono-400-latin.woff2" crossorigin>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
<div class="min-h-screen flex flex-col items-center justify-center bg-background px-6">
    <div class="w-full max-w-md text-center">
        <p class="text-5xl">🥬</p>

        <h1 class="mt-6 text-3xl font-semibold tracking-tight text-foreground">
            Lettuce Eat
        </h1>

        <p class="mt-3 text-sm text-muted-foreground">
            Keep track of the restaurants you like and the dishes worth ordering again.
        </p>

        @auth
            <x-button-link :href="route('restaurants.index')" class="mt-8">
                Go to my restaurants
            </x-button-link>
        @else
            <div class="mt-8 flex items-center justify-center gap-3">
                <x-button-link :href="route('login')">
                    Log in
                </x-button-link>

                @if (Route::has('register'))
                    <x-button-link :href="route('register')" variant="secondary">
                        Register
                    </x-button-link>
                @endif
            </div>
        @endauth
    </div>
</div>
</body>
</html>
