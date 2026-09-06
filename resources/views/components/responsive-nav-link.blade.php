@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full rounded-md px-3 py-2 text-start text-base font-medium bg-accent text-accent-foreground transition-colors focus:outline-none'
            : 'block w-full rounded-md px-3 py-2 text-start text-base font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus:outline-none';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
