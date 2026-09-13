<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <link rel="preload" as="font" type="font/woff2" href="/fonts/dm-sans-latin.woff2" crossorigin>
    <link rel="preload" as="font" type="font/woff2" href="/fonts/space-mono-400-latin.woff2" crossorigin>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
<div class="min-h-screen flex flex-col sm:justify-center items-center px-4 pt-6 sm:px-0 sm:pt-0 bg-background">
    <div>
        <a href="/">
            <x-application-logo class="w-16 h-16 fill-current text-muted-foreground"/>
        </a>
    </div>

    <div
        class="w-full sm:max-w-md mt-6 px-6 py-6 bg-card text-card-foreground border border-border shadow-sm overflow-hidden rounded-lg">
        {{ $slot }}
    </div>
</div>
</body>
</html>
