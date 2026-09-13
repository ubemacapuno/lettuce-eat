<?php

use Illuminate\Support\Facades\Blade;

test('it renders an anchor so navigation keeps its browser behaviour', function () {
    $html = Blade::render('<x-button-link href="/recipes">Add recipe</x-button-link>');

    expect($html)->toContain('<a ')
        ->toContain('href="/recipes"')
        ->toContain('Add recipe')
        ->not->toContain('<button');
});

test('it defaults to the primary variant at the default size', function () {
    $html = Blade::render('<x-button-link href="/x">Go</x-button-link>');

    expect($html)->toContain('bg-primary')
        ->toContain('text-primary-foreground')
        ->toContain('h-9')
        ->not->toContain('border-input');
});

test('the secondary variant swaps the fill for a border', function () {
    $html = Blade::render('<x-button-link href="/x" variant="secondary">Edit</x-button-link>');

    expect($html)->toContain('border-input')
        ->toContain('hover:bg-accent')
        ->not->toContain('bg-primary');
});

test('the small size shrinks the control', function () {
    $html = Blade::render('<x-button-link href="/x" size="sm">Add dish</x-button-link>');

    expect($html)->toContain('h-8')->not->toContain('h-9');
});

test('an unknown variant or size falls back instead of rendering unstyled', function () {
    $html = Blade::render('<x-button-link href="/x" variant="nope" size="nope">Go</x-button-link>');

    expect($html)->toContain('bg-primary')->toContain('h-9');
});

test('classes from the call site are merged onto the defaults', function () {
    $html = Blade::render('<x-button-link href="/x" class="mt-4">Go</x-button-link>');

    expect($html)->toContain('mt-4')->toContain('bg-primary');
});

test('it centres its label', function () {
    $html = Blade::render('<x-button-link href="/x">Go</x-button-link>');

    expect($html)->toContain('justify-center');
});
