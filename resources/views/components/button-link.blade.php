@props([
    'variant' => 'primary',
    'size' => 'default',
])

@php
    $base = 'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring';

    $variants = [
        'primary' => 'bg-primary text-primary-foreground shadow hover:bg-primary/90',
        'secondary' => 'border border-input bg-transparent text-foreground shadow-sm hover:bg-accent hover:text-accent-foreground',
    ];

    $sizes = [
        'default' => 'h-9 px-4 py-2 text-sm',
        'sm' => 'h-8 px-3 text-sm',
    ];

    $classes = $base.' '.($sizes[$size] ?? $sizes['default']).' '.($variants[$variant] ?? $variants['primary']);
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
