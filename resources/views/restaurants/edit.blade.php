<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold tracking-tight text-foreground">
            {{ __('Edit') }} {{ $restaurant->name }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-lg border border-border bg-card p-6 shadow-sm">
                <form method="POST" action="{{ route('restaurants.update', $restaurant) }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    @include('restaurants.partials.form')

                    <div class="flex items-center gap-4">
                        <x-primary-button>{{ __('Save') }}</x-primary-button>
                        <a href="{{ route('restaurants.show', $restaurant) }}" class="text-sm text-muted-foreground transition-colors hover:text-foreground">
                            {{ __('Cancel') }}
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
