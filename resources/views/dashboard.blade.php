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

                    <div class="flex flex-col gap-2">
                        <x-button-link :href="route('restaurants.index')" class="mt-4">
                            {{ __('Go to my restaurants') }}
                        </x-button-link>

                        <x-button-link :href="route('recipes.index')">
                            {{ __('Go to my recipes') }}
                        </x-button-link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
