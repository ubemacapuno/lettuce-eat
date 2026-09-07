<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-foreground leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-card overflow-hidden border border-border rounded-lg">
                <div class="p-6 text-foreground">
                    <p>{{ __("You're logged in!") }}</p>

                    <a href="{{ route('restaurants.index') }}"
                       class="inline-block mt-4 px-4 py-2 bg-primary text-primary-foreground text-sm font-semibold rounded-md hover:bg-primary/90">
                        {{ __('Go to my restaurants') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
