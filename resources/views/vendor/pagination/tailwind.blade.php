@php
    $base = 'inline-flex h-9 min-w-[2.25rem] items-center justify-center border border-border px-3 text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring';
    $enabled = $base.' bg-card text-card-foreground shadow-sm hover:bg-accent hover:text-accent-foreground';
    $disabled = $base.' cursor-not-allowed border-border/40 text-muted-foreground';
    $current = $base.' bg-primary text-primary-foreground shadow-sm';
@endphp

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}">
        <div class="flex items-center justify-between gap-2 sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="{{ $disabled }}" aria-disabled="true">{!! __('pagination.previous') !!}</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $enabled }}">{!! __('pagination.previous') !!}</a>
            @endif

            <span class="text-xs uppercase tracking-wide text-muted-foreground">
                {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
            </span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $enabled }}">{!! __('pagination.next') !!}</a>
            @else
                <span class="{{ $disabled }}" aria-disabled="true">{!! __('pagination.next') !!}</span>
            @endif
        </div>

        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between sm:gap-4">
            <p class="text-sm text-muted-foreground">
                {!! __('Showing') !!}
                @if ($paginator->firstItem())
                    <span class="font-medium text-foreground">{{ $paginator->firstItem() }}</span>
                    {!! __('to') !!}
                    <span class="font-medium text-foreground">{{ $paginator->lastItem() }}</span>
                @else
                    {{ $paginator->count() }}
                @endif
                {!! __('of') !!}
                <span class="font-medium text-foreground">{{ $paginator->total() }}</span>
                {!! __('results') !!}
            </p>

            <div class="flex items-center gap-1">
                @if ($paginator->onFirstPage())
                    <span class="{{ $disabled }}" aria-disabled="true" aria-label="{{ __('pagination.previous') }}">&laquo;</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $enabled }}"
                       aria-label="{{ __('pagination.previous') }}">&laquo;</a>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="{{ $disabled }}" aria-disabled="true">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="{{ $current }}" aria-current="page">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="{{ $enabled }}"
                                   aria-label="{{ __('Go to page :page', ['page' => $page]) }}">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $enabled }}"
                       aria-label="{{ __('pagination.next') }}">&raquo;</a>
                @else
                    <span class="{{ $disabled }}" aria-disabled="true" aria-label="{{ __('pagination.next') }}">&raquo;</span>
                @endif
            </div>
        </div>
    </nav>
@endif
