@php($restaurant = $restaurant ?? null)

<div>
    <x-input-label for="name" :value="__('Name')" />
    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                  :value="old('name', $restaurant?->name)" required autofocus />
    <x-input-error class="mt-2" :messages="$errors->get('name')" />
</div>

<div>
    <x-input-label for="rating" :value="__('Rating (1–5)')" />
    <div class="mt-1" data-vue="StarRating" data-props="{{ json_encode([
        'name' => 'rating',
        'value' => old('rating', $restaurant?->rating),
    ]) }}"></div>
    <x-input-error class="mt-2" :messages="$errors->get('rating')" />
</div>

<div>
    <x-input-label for="notes" :value="__('Notes')" />
    <textarea id="notes" name="notes" rows="3"
              class="mt-1 flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm text-foreground shadow-sm transition-colors placeholder:text-muted-foreground focus:border-ring focus:outline-none focus:ring-1 focus:ring-ring">{{ old('notes', $restaurant?->notes) }}</textarea>
    <x-input-error class="mt-2" :messages="$errors->get('notes')" />
</div>

<div>
    <x-input-label for="street_address" :value="__('Street address')" />
    <x-text-input id="street_address" name="street_address" type="text" class="mt-1 block w-full"
                  :value="old('street_address', $restaurant?->street_address)" />
    <x-input-error class="mt-2" :messages="$errors->get('street_address')" />
</div>

<div class="flex gap-4">
    <div class="flex-1">
        <x-input-label for="city" :value="__('City')" />
        <x-text-input id="city" name="city" type="text" class="mt-1 block w-full"
                      :value="old('city', $restaurant?->city)" />
        <x-input-error class="mt-2" :messages="$errors->get('city')" />
    </div>

    <div class="w-28">
        <x-input-label for="state" :value="__('State')" />
        <x-text-input id="state" name="state" type="text" class="mt-1 block w-full"
                      :value="old('state', $restaurant?->state)" />
        <x-input-error class="mt-2" :messages="$errors->get('state')" />
    </div>
</div>
