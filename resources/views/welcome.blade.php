<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

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
                    <a href="{{ route('restaurants.index') }}"
                       class="mt-8 inline-flex h-9 items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90">
                        Go to my restaurants
                    </a>
                @else
                    <div class="mt-8 flex items-center justify-center gap-3">
                        <a href="{{ route('login') }}"
                           class="inline-flex h-9 items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90">
                            Log in
                        </a>

                        @if (Route::has('register'))
                            <a href="{{ route('register') }}"
                               class="inline-flex h-9 items-center justify-center rounded-md border border-input bg-transparent px-4 py-2 text-sm font-medium text-foreground shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground">
                                Register
                            </a>
                        @endif
                    </div>
                @endauth
            </div>
        </div>
    </body>
</html>
