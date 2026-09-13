{{--this is only used for simplePaginate()--}}
@php
    $base = 'inline-flex h-9 min-w-[2.25rem] items-center justify-center border border-border px-3 text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring';
    $enabled = $base.' bg-card text-card-foreground shadow-sm hover:bg-accent hover:text-accent-foreground';
    $disabled = $base.' cursor-not-allowed border-border/40 text-muted-foreground';
@endphp

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex items-center justify-between gap-2">
        @if ($paginator->onFirstPage())
            <span class="{{ $disabled }}" aria-disabled="true">{!! __('pagination.previous') !!}</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $enabled }}">{!! __('pagination.previous') !!}</a>
        @endif

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $enabled }}">{!! __('pagination.next') !!}</a>
        @else
            <span class="{{ $disabled }}" aria-disabled="true">{!! __('pagination.next') !!}</span>
        @endif
    </nav>
@endif
