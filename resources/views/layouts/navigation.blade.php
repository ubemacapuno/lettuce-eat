@php
    $user = Auth::user();

    $navLinks = [
        [
            'label' => __('Dashboard'),
            'href' => route('dashboard'),
            'active' => request()->routeIs('dashboard'),
        ],
        [
            'label' => __('Restaurants'),
            'href' => route('restaurants.index'),
            'active' => request()->routeIs('restaurants.*') || request()->routeIs('dishes.*'),
        ],
        [
            'label' => __('Recipes'),
            'href' => route('recipes.index'),
            'active' => request()->routeIs('recipes.*'),
        ],
    ];
@endphp

<nav class="relative border-b border-border bg-background">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-14 items-center justify-between">
            <div class="flex items-center gap-8">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                    <span class="text-lg">🥬</span>
                    <span class="text-sm font-semibold tracking-tight text-foreground">Lettuce Eat</span>
                </a>

                <div class="hidden items-center gap-6 sm:flex">
                    @foreach ($navLinks as $link)
                        <a href="{{ $link['href'] }}"
                           class="text-sm font-medium transition-colors {{ $link['active'] ? 'text-foreground' : 'text-muted-foreground hover:text-foreground' }}">
                            {{ $link['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Desktop: user dropdown --}}
            <details class="group relative hidden sm:block" data-dismiss-on-outside-click>
                <summary
                    class="cursor-pointer list-none [&::-webkit-details-marker]:hidden inline-flex h-8 items-center gap-2 rounded-md px-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                    <span
                        class="flex h-6 w-6 items-center justify-center rounded-full bg-secondary text-xs font-semibold text-secondary-foreground">
                        {{ Str::upper(Str::substr($user->name, 0, 1)) }}
                    </span>
                    {{ $user->name }}
                </summary>

                <div
                    class="absolute end-0 z-50 mt-2 w-56 origin-top-right rounded-md border border-border bg-popover p-1 text-popover-foreground shadow-md">
                    <div class="px-2 py-1.5">
                        <p class="text-sm font-medium leading-none">{{ $user->name }}</p>
                        <p class="mt-1 truncate text-xs text-muted-foreground">{{ $user->email }}</p>
                    </div>

                    <div class="-mx-1 my-1 h-px bg-border"></div>

                    <a href="{{ route('profile.edit') }}"
                       class="block rounded-sm px-2 py-1.5 text-sm transition-colors hover:bg-accent hover:text-accent-foreground">{{ __('Profile') }}</a>

                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="block w-full rounded-sm px-2 py-1.5 text-start text-sm transition-colors hover:bg-accent hover:text-accent-foreground">{{ __('Log out') }}</button>
                    </form>
                </div>
            </details>

            {{-- Mobile: hamburger --}}
            <details class="group sm:hidden" data-dismiss-on-outside-click>
                <summary
                    class="cursor-pointer list-none [&::-webkit-details-marker]:hidden inline-flex h-8 w-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                    <svg class="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              class="group-open:hidden" d="M4 6h16M4 12h16M4 18h16"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              class="hidden group-open:block" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </summary>

                <div class="absolute inset-x-0 top-14 z-50 border-t border-border bg-background px-4 py-3">
                    <div class="space-y-1">
                        @foreach ($navLinks as $link)
                            <a href="{{ $link['href'] }}"
                               class="block rounded-md px-3 py-2 text-sm font-medium transition-colors {{ $link['active'] ? 'bg-accent text-accent-foreground' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground' }}">
                                {{ $link['label'] }}
                            </a>
                        @endforeach
                    </div>

                    <div class="mt-3 border-t border-border pt-3">
                        <p class="px-3 text-sm font-medium text-foreground">{{ $user->name }}</p>
                        <p class="px-3 text-xs text-muted-foreground">{{ $user->email }}</p>

                        <div class="mt-2 space-y-1">
                            <a href="{{ route('profile.edit') }}"
                               class="block rounded-md px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground">{{ __('Profile') }}</a>

                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="block w-full rounded-md px-3 py-2 text-start text-sm font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground">{{ __('Log out') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            </details>
        </div>
    </div>
</nav>
