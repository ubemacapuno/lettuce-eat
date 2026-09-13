@php
    $dishes = $restaurant->dishes;
    $ratedDishes = $dishes->whereNotNull('rating');
    $repeatCount = $dishes->where('order_again', true)->count();

    $dishStats = collect([
        $dishes->isNotEmpty() ? $dishes->count().' '.Str::plural('dish', $dishes->count()) : null,
        $ratedDishes->isNotEmpty() ? '★ '.number_format($ratedDishes->avg('rating'), 1).' avg' : null,
        $repeatCount ? $repeatCount.' worth repeating' : null,
    ])->filter();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div>
                <a href="{{ route('restaurants.index') }}"
                   class="text-sm text-muted-foreground transition-colors hover:text-foreground">
                    ← All restaurants
                </a>

                <h2 class="mt-2 text-xl font-semibold tracking-tight text-foreground">
                    {{ $restaurant->name }}
                </h2>

                @if ($restaurant->street_address || $restaurant->city)
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ collect([$restaurant->street_address, $restaurant->city, $restaurant->state])->filter()->join(', ') }}
                    </p>
                @endif
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <a href="{{ route('restaurants.edit', $restaurant) }}"
                   class="inline-flex h-9 items-center justify-center rounded-md border border-input bg-transparent px-3 py-2 text-sm font-medium text-foreground shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground">
                    Edit
                </a>

                <div data-vue="ConfirmButton" data-props="{{ json_encode([
                    'label' => 'Delete',
                    'action' => route('restaurants.destroy', $restaurant),
                    'variant' => 'outline',
                    'title' => 'Delete '.$restaurant->name.'?',
                    'message' => 'Every dish you logged here goes with it. This cannot be undone.',
                    'confirmLabel' => 'Delete',
                ]) }}"></div>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            @include('partials.flash')

            @if ($restaurant->rating || $restaurant->notes || $dishStats->isNotEmpty())
                <div class="mb-8 rounded-lg border border-border bg-card p-5 shadow-sm">
                    @if ($restaurant->rating)
                        <div class="flex items-baseline gap-2">
                            <span class="text-2xl font-semibold tracking-tight text-card-foreground">
                                ★ {{ $restaurant->rating }}
                            </span>
                            <span class="text-sm text-muted-foreground">/ 5</span>
                        </div>
                    @endif

                    @if ($restaurant->notes)
                        <p class="{{ $restaurant->rating ? 'mt-3 ' : '' }}text-sm leading-relaxed text-muted-foreground">
                            {{ $restaurant->notes }}
                        </p>
                    @endif

                    @if ($dishStats->isNotEmpty())
                        <p class="{{ $restaurant->rating || $restaurant->notes ? 'mt-4 border-t border-border pt-4 ' : '' }}text-sm text-muted-foreground">
                            {{ $dishStats->join(' · ') }}
                        </p>
                    @endif
                </div>
            @endif

            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-medium uppercase tracking-wide text-muted-foreground">
                    Dishes
                </h3>

                <a href="{{ route('restaurants.dishes.create', $restaurant) }}"
                   class="inline-flex h-8 items-center justify-center rounded-md bg-primary px-3 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90">
                    Add dish
                </a>
            </div>

            <div class="space-y-3">
                @forelse ($restaurant->dishes as $dish)
                    <div class="rounded-lg border border-border bg-card px-4 py-3 shadow-sm">
                        <div class="flex items-baseline justify-between gap-4">
                            <h4 class="font-medium text-card-foreground">{{ $dish->name }}</h4>

                            <div class="flex shrink-0 items-center gap-2">
                                @if ($dish->order_again)
                                    <span class="rounded-md bg-success/15 px-2 py-0.5 text-xs font-medium text-success">
                                        Order again
                                    </span>
                                @endif

                                @if ($dish->rating)
                                    <span class="rounded-md bg-secondary px-2 py-0.5 text-xs font-medium text-secondary-foreground">
                                        ★ {{ $dish->rating }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        @if ($dish->notes)
                            <p class="mt-1 text-sm leading-relaxed text-muted-foreground">{{ $dish->notes }}</p>
                        @endif

                        <div class="mt-2 flex items-center gap-3">
                            <a href="{{ route('dishes.edit', $dish) }}"
                               class="text-sm text-muted-foreground transition-colors hover:text-foreground">
                                Edit
                            </a>

                            <div data-vue="ConfirmButton" data-props="{{ json_encode([
                                'label' => 'Remove',
                                'action' => route('dishes.destroy', $dish),
                                'variant' => 'link',
                                'title' => 'Remove '.$dish->name.'?',
                                'message' => 'This cannot be undone.',
                                'confirmLabel' => 'Remove',
                            ]) }}"></div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-lg border border-dashed border-border p-10 text-center">
                        <p class="text-sm font-medium text-foreground">No dishes logged here yet</p>
                        <p class="mt-1 text-sm text-muted-foreground">Add what you ordered and whether it's worth repeating.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
