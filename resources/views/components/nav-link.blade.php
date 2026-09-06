@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 text-sm font-medium text-foreground transition-colors focus:outline-none'
            : 'inline-flex items-center px-1 pt-1 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground focus:outline-none';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
