<script setup>
import { ref } from 'vue';

defineProps({
    user: { type: Object, required: true },
    links: { type: Array, default: () => [] },
    profileUrl: { type: String, required: true },
    logoutUrl: { type: String, required: true },
});

const menuOpen = ref(false);
const mobileOpen = ref(false);

const csrf = document.querySelector('meta[name="csrf-token"]').content;
</script>

<template>
    <nav class="border-b border-border bg-background">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-14 items-center justify-between">
                <div class="flex items-center gap-8">
                    <a :href="links[0]?.href ?? '/'" class="flex items-center gap-2">
                        <span class="text-lg">🥬</span>
                        <span class="text-sm font-semibold tracking-tight text-foreground">Lettuce Eat</span>
                    </a>

                    <div class="hidden items-center gap-6 sm:flex">
                        <a
                            v-for="link in links"
                            :key="link.href"
                            :href="link.href"
                            class="text-sm font-medium transition-colors"
                            :class="link.active ? 'text-foreground' : 'text-muted-foreground hover:text-foreground'"
                        >
                            {{ link.label }}
                        </a>
                    </div>
                </div>

                <!-- Desktop: user dropdown -->
                <div class="hidden sm:flex sm:items-center">
                    <div class="relative">
                        <button
                            type="button"
                            class="inline-flex h-8 items-center gap-2 rounded-md px-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                            @click="menuOpen = ! menuOpen"
                        >
                            <span
                                class="flex h-6 w-6 items-center justify-center rounded-full bg-secondary text-xs font-semibold text-secondary-foreground"
                            >{{ user.name.charAt(0).toUpperCase() }}</span>
                            {{ user.name }}
                        </button>

                        <div v-if="menuOpen" class="fixed inset-0 z-40" @click="menuOpen = false"></div>

                        <Transition
                            enter-active-class="transition ease-out duration-150"
                            enter-from-class="opacity-0 scale-95"
                            leave-active-class="transition ease-in duration-100"
                            leave-to-class="opacity-0 scale-95"
                        >
                            <div
                                v-if="menuOpen"
                                class="absolute end-0 z-50 mt-2 w-56 origin-top-right rounded-md border border-border bg-popover p-1 text-popover-foreground shadow-md"
                            >
                                <div class="px-2 py-1.5">
                                    <p class="text-sm font-medium leading-none">{{ user.name }}</p>
                                    <p class="mt-1 truncate text-xs text-muted-foreground">{{ user.email }}</p>
                                </div>

                                <div class="-mx-1 my-1 h-px bg-border"></div>

                                <a
                                    :href="profileUrl"
                                    class="block rounded-sm px-2 py-1.5 text-sm transition-colors hover:bg-accent hover:text-accent-foreground"
                                >Profile</a>

                                <form :action="logoutUrl" method="POST">
                                    <input type="hidden" name="_token" :value="csrf">
                                    <button
                                        type="submit"
                                        class="block w-full rounded-sm px-2 py-1.5 text-start text-sm transition-colors hover:bg-accent hover:text-accent-foreground"
                                    >Log out</button>
                                </form>
                            </div>
                        </Transition>
                    </div>
                </div>

                <!-- Mobile: hamburger -->
                <button
                    type="button"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring sm:hidden"
                    @click="mobileOpen = ! mobileOpen"
                >
                    <svg class="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            :d="mobileOpen ? 'M6 18L18 6M6 6l12 12' : 'M4 6h16M4 12h16M4 18h16'"
                        />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile: panel -->
        <div v-show="mobileOpen" class="border-t border-border px-4 py-3 sm:hidden">
            <div class="space-y-1">
                <a
                    v-for="link in links"
                    :key="link.href"
                    :href="link.href"
                    class="block rounded-md px-3 py-2 text-sm font-medium transition-colors"
                    :class="link.active ? 'bg-accent text-accent-foreground' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'"
                >
                    {{ link.label }}
                </a>
            </div>

            <div class="mt-3 border-t border-border pt-3">
                <p class="px-3 text-sm font-medium text-foreground">{{ user.name }}</p>
                <p class="px-3 text-xs text-muted-foreground">{{ user.email }}</p>

                <div class="mt-2 space-y-1">
                    <a
                        :href="profileUrl"
                        class="block rounded-md px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground"
                    >Profile</a>

                    <form :action="logoutUrl" method="POST">
                        <input type="hidden" name="_token" :value="csrf">
                        <button
                            type="submit"
                            class="block w-full rounded-md px-3 py-2 text-start text-sm font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground"
                        >Log out</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>
</template>
