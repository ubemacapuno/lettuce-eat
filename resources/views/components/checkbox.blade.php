@php
    $wrapperClass = $attributes->get('class');
    $inputAttributes = $attributes->except('class');
@endphp

<span class="relative inline-flex shrink-0 {{ $wrapperClass }}">
    <input type="checkbox" {{ $inputAttributes }}
           class="peer h-4 w-4 appearance-none rounded-sm border border-input bg-transparent transition-colors checked:border-primary checked:bg-primary checked:bg-none focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring focus-visible:ring-offset-0">

    <svg class="pointer-events-none absolute inset-0 hidden h-4 w-4 text-primary-foreground peer-checked:block"
         viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
        <path d="M12.207 4.793a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0l-2-2a1 1 0 011.414-1.414L6.5 9.086l4.293-4.293a1 1 0 011.414 0z" />
    </svg>
</span>
