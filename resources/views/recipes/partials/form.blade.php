@php($recipe = $recipe ?? null)

<div>
    <x-input-label for="name" :value="__('Name')" />
    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                  :value="old('name', $recipe?->name)" required autofocus />
    <x-input-error class="mt-2" :messages="$errors->get('name')" />
</div>

<div>
    <x-input-label for="rating" :value="__('Rating (1–5)')" />
    <div class="mt-1" data-vue="StarRating" data-props="{{ json_encode([
        'name' => 'rating',
        'value' => old('rating', $recipe?->rating),
    ]) }}"></div>
    <x-input-error class="mt-2" :messages="$errors->get('rating')" />
</div>

<div>
    <x-input-label for="ingredients" :value="__('Ingredients')" />
    <textarea id="ingredients" name="ingredients" rows="8"
              class="mt-1 flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 font-mono text-sm text-foreground shadow-sm transition-colors placeholder:text-muted-foreground focus:border-ring focus:outline-none focus:ring-1 focus:ring-ring">{{ old('ingredients', $recipe?->ingredients) }}</textarea>
    <p class="mt-1 text-sm text-muted-foreground">{{ __('Markdown works here — headings, **bold**, and - lists.') }}</p>
    <x-input-error class="mt-2" :messages="$errors->get('ingredients')" />
</div>

<div>
    <x-input-label for="instructions" :value="__('Instructions')" />
    <textarea id="instructions" name="instructions" rows="12"
              class="mt-1 flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 font-mono text-sm text-foreground shadow-sm transition-colors placeholder:text-muted-foreground focus:border-ring focus:outline-none focus:ring-1 focus:ring-ring">{{ old('instructions', $recipe?->instructions) }}</textarea>
    <p class="mt-1 text-sm text-muted-foreground">{{ __('Numbered steps render as an ordered list.') }}</p>
    <x-input-error class="mt-2" :messages="$errors->get('instructions')" />
</div>

<div>
    <x-input-label for="source_url" :value="__('Source link')" />
    <x-text-input id="source_url" name="source_url" type="url" class="mt-1 block w-full"
                  placeholder="https://" :value="old('source_url', $recipe?->source_url)" />
    <p class="mt-1 text-sm text-muted-foreground">{{ __('Where you found it, if it came from somewhere.') }}</p>
    <x-input-error class="mt-2" :messages="$errors->get('source_url')" />
</div>

<div class="flex gap-4">
    <div class="flex-1">
        <x-input-label for="total_minutes" :value="__('Total minutes')" />
        <x-text-input id="total_minutes" name="total_minutes" type="number" min="1" max="10080"
                      class="mt-1 block w-full" :value="old('total_minutes', $recipe?->total_minutes)" />
        <x-input-error class="mt-2" :messages="$errors->get('total_minutes')" />
    </div>

    <div class="w-28">
        <x-input-label for="servings" :value="__('Servings')" />
        <x-text-input id="servings" name="servings" type="number" min="1" max="255"
                      class="mt-1 block w-full" :value="old('servings', $recipe?->servings)" />
        <x-input-error class="mt-2" :messages="$errors->get('servings')" />
    </div>
</div>

<label for="make_again"
       class="flex cursor-pointer items-start gap-3 rounded-md border border-input p-3 transition-colors hover:bg-accent/40">
    <input type="hidden" name="make_again" value="0">
    <x-checkbox id="make_again" name="make_again" value="1" class="mt-0.5"
                :checked="(bool) old('make_again', $recipe?->make_again)" />
    <span>
        <span class="block text-sm font-medium leading-none text-foreground">{{ __('Make again?') }}</span>
        <span class="mt-1 block text-sm text-muted-foreground">{{ __('Worth cooking a second time.') }}</span>
    </span>
</label>
