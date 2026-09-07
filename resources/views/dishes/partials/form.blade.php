@php($dish = $dish ?? null)

<div>
    <x-input-label for="name" :value="__('Dish name')" />
    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                  :value="old('name', $dish?->name)" required autofocus />
    <x-input-error class="mt-2" :messages="$errors->get('name')" />
</div>

<div>
    <x-input-label for="rating" :value="__('Rating (1–5)')" />
    <div class="mt-1" data-vue="StarRating" data-props="{{ json_encode([
        'name' => 'rating',
        'value' => old('rating', $dish?->rating),
    ]) }}"></div>
    <x-input-error class="mt-2" :messages="$errors->get('rating')" />
</div>

<div>
    <x-input-label for="notes" :value="__('Notes')" />
    <textarea id="notes" name="notes" rows="3"
              class="mt-1 flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm text-foreground shadow-sm transition-colors placeholder:text-muted-foreground focus:border-ring focus:outline-none focus:ring-1 focus:ring-ring">{{ old('notes', $dish?->notes) }}</textarea>
    <x-input-error class="mt-2" :messages="$errors->get('notes')" />
</div>

<label for="order_again"
       class="flex cursor-pointer items-start gap-3 rounded-md border border-input p-3 transition-colors hover:bg-accent/40">
    <input type="hidden" name="order_again" value="0">
    <x-checkbox id="order_again" name="order_again" value="1" class="mt-0.5"
                :checked="(bool) old('order_again', $dish?->order_again)" />
    <span>
        <span class="block text-sm font-medium leading-none text-foreground">{{ __('Order again?') }}</span>
        <span class="mt-1 block text-sm text-muted-foreground">{{ __('Worth repeating next time you go.') }}</span>
    </span>
</label>
