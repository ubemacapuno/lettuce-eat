<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold tracking-tight text-foreground">
                    {{ __('My Restaurants') }}
                </h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ $restaurants->count() }} {{ Str::plural('place', $restaurants->count()) }} logged
                </p>
            </div>

            <a href="{{ route('restaurants.create') }}"
               class="inline-flex h-9 items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90">
                Add restaurant
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            @include('partials.flash')

            <div class="space-y-3">
                @forelse ($restaurants as $restaurant)
                    <a href="{{ route('restaurants.show', $restaurant) }}"
                       class="block rounded-lg border border-border bg-card p-4 shadow-sm transition-colors hover:border-ring/40 hover:bg-accent/40">
                        <div class="flex items-baseline justify-between gap-4">
                            <h3 class="font-medium text-card-foreground">{{ $restaurant->name }}</h3>

                            @if ($restaurant->rating)
                                <span class="shrink-0 rounded-md bg-secondary px-2 py-0.5 text-xs font-medium text-secondary-foreground">
                                    ★ {{ $restaurant->rating }}
                                </span>
                            @endif
                        </div>

                        <p class="mt-1 text-sm text-muted-foreground">
                            @if ($restaurant->city)
                                {{ $restaurant->city }}{{ $restaurant->state ? ', '.$restaurant->state : '' }}
                                <span class="px-1 text-border">/</span>
                            @endif
                            {{ $restaurant->dishes_count }} {{ Str::plural('dish', $restaurant->dishes_count) }}
                        </p>
                    </a>
                @empty
                    <div class="rounded-lg border border-dashed border-border p-10 text-center">
                        <p class="text-sm font-medium text-foreground">No restaurants yet</p>
                        <p class="mt-1 text-sm text-muted-foreground">Add your first one to get started.</p>

                        <a href="{{ route('restaurants.create') }}"
                           class="mt-4 inline-flex h-9 items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90">
                            Add restaurant
                        </a>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
