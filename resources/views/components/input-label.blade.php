@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-sm font-medium leading-none text-foreground']) }}>
    {{ $value ?? $slot }}
</label>
