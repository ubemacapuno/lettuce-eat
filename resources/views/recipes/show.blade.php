@php
    $prose = 'text-sm leading-relaxed text-muted-foreground '
        .'[&>*:first-child]:mt-0 [&>*:last-child]:mb-0 '
        .'[&_h1]:mb-2 [&_h1]:mt-5 [&_h1]:text-base [&_h1]:font-semibold [&_h1]:text-foreground '
        .'[&_h2]:mb-2 [&_h2]:mt-5 [&_h2]:text-sm [&_h2]:font-semibold [&_h2]:text-foreground '
        .'[&_h3]:mb-2 [&_h3]:mt-4 [&_h3]:text-sm [&_h3]:font-medium [&_h3]:text-foreground '
        .'[&_p]:my-2 '
        .'[&_ul]:my-2 [&_ul]:list-disc [&_ul]:pl-5 '
        .'[&_ol]:my-2 [&_ol]:list-decimal [&_ol]:pl-5 '
        .'[&_li]:my-1 [&_li]:pl-1 '
        .'[&_strong]:font-semibold [&_strong]:text-foreground '
        .'[&_em]:italic '
        .'[&_blockquote]:my-3 [&_blockquote]:border-l-2 [&_blockquote]:border-border [&_blockquote]:pl-4 [&_blockquote]:italic '
        .'[&_code]:rounded [&_code]:bg-secondary [&_code]:px-1 [&_code]:py-0.5 [&_code]:font-mono [&_code]:text-xs '
        .'[&_a]:underline [&_a]:underline-offset-4 [&_a]:transition-colors hover:[&_a]:text-foreground';

    $markdown = ['html_input' => 'strip', 'allow_unsafe_links' => false];

    $meta = collect([
        $recipe->total_minutes ? $recipe->total_minutes.' min' : null,
        $recipe->servings ? $recipe->servings.' '.Str::plural('serving', $recipe->servings) : null,
    ])->filter();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div>
                <a href="{{ route('recipes.index') }}"
                   class="text-sm text-muted-foreground transition-colors hover:text-foreground">
                    ← All recipes
                </a>

                <div class="mt-2 flex flex-wrap items-baseline gap-x-3 gap-y-1">
                    <h2 class="text-xl font-semibold tracking-tight text-foreground">
                        {{ $recipe->name }}
                    </h2>

                    @if ($recipe->rating)
                        <span class="text-base font-semibold tracking-tight text-foreground">
                            ★ {{ $recipe->rating }}
                        </span>
                    @endif

                    @if ($recipe->make_again)
                        <span class="rounded-md bg-success/15 px-2 py-0.5 text-xs font-medium text-success">
                            Make again
                        </span>
                    @endif
                </div>

                @if ($meta->isNotEmpty())
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ $meta->join(' · ') }}
                    </p>
                @endif
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <x-button-link :href="route('recipes.edit', $recipe)" variant="secondary">
                    Edit
                </x-button-link>

                <div data-vue="ConfirmButton" data-props="{{ json_encode([
                    'label' => 'Delete',
                    'action' => route('recipes.destroy', $recipe),
                    'variant' => 'outline',
                    'title' => 'Delete '.$recipe->name.'?',
                    'message' => 'The ingredients and instructions go with it. This cannot be undone.',
                    'confirmLabel' => 'Delete',
                ]) }}"></div>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            @include('partials.flash')

            @if ($recipe->source_url)
                <a href="{{ $recipe->source_url }}" target="_blank" rel="noopener noreferrer"
                   class="mb-6 flex items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-foreground">
                    <span aria-hidden="true">↗</span>
                    <span class="min-w-0 truncate underline underline-offset-4">{{ $recipe->source_url }}</span>
                </a>
            @endif

            @if ($recipe->ingredients || $recipe->instructions)
                <div class="space-y-8">
                    @if ($recipe->ingredients)
                        <div>
                            <h3 class="mb-3 text-sm font-medium uppercase tracking-wide text-muted-foreground">
                                Ingredients
                            </h3>

                            <div class="rounded-lg border border-border bg-card p-5 shadow-sm">
                                <div class="{{ $prose }}">
                                    {!! Str::markdown($recipe->ingredients, $markdown) !!}
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($recipe->instructions)
                        <div>
                            <h3 class="mb-3 text-sm font-medium uppercase tracking-wide text-muted-foreground">
                                Instructions
                            </h3>

                            <div class="rounded-lg border border-border bg-card p-5 shadow-sm">
                                <div class="{{ $prose }}">
                                    {!! Str::markdown($recipe->instructions, $markdown) !!}
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @else
                <div class="rounded-lg border border-dashed border-border p-10 text-center">
                    <p class="text-sm font-medium text-foreground">Nothing written down yet</p>
                    <p class="mt-1 text-sm text-muted-foreground">Add the ingredients and instructions so you can cook it again.</p>

                    <x-button-link :href="route('recipes.edit', $recipe)" class="mt-4">
                        Add details
                    </x-button-link>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
