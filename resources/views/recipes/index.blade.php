<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold tracking-tight text-foreground">
                    {{ __('My Recipes') }}
                </h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ $recipes->count() }} {{ Str::plural('recipe', $recipes->count()) }} logged
                </p>
            </div>

            <a href="{{ route('recipes.create') }}"
               class="inline-flex h-9 items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90">
                Add recipe
            </a>

        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            @include('partials.flash')

            <div class="space-y-3">
                @forelse ($recipes as $recipe)
                    <a href="{{ route('recipes.show', $recipe) }}"
                       class="block rounded-lg border border-border bg-card p-4 shadow-sm transition-colors hover:border-ring/40 hover:bg-accent/40">
                        <div class="flex items-baseline justify-between gap-4">
                            <h3 class="font-medium text-card-foreground">{{ $recipe->name }}</h3>

                            @if ($recipe->rating)
                                <span class="shrink-0 rounded-md bg-secondary px-2 py-0.5 text-xs font-medium text-secondary-foreground">
                                    ★ {{ $recipe->rating }}
                                </span>
                            @endif
                        </div>
                    </a>
                @empty
                    <div class="rounded-lg border border-dashed border-border p-10 text-center">
                        <p class="text-sm font-medium text-foreground">No recipes yet</p>
                        <p class="mt-1 text-sm text-muted-foreground">Add your first one to get started.</p>

                        <a href="{{ route('recipes.create') }}"
                           class="mt-4 inline-flex h-9 items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90">
                            Add recipe
                        </a>
                    </div>
                @endforelse
            </div>
            <div class="mt-6">
                {{ $recipes->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
