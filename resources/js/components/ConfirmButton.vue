<script setup>
import { nextTick, ref } from 'vue';

const props = defineProps({
    label: { type: String, required: true },
    action: { type: String, required: true },
    method: { type: String, default: 'delete' },
    title: { type: String, default: 'Are you sure?' },
    message: { type: String, default: '' },
    confirmLabel: { type: String, default: 'Confirm' },
    variant: { type: String, default: 'link' },
    requiresPassword: { type: Boolean, default: false },
    passwordError: { type: String, default: '' },
    startOpen: { type: Boolean, default: false },
});

const open = ref(props.startOpen);
const password = ref(null);

const csrf = document.querySelector('meta[name="csrf-token"]').content;

const triggerClasses = {
    link: 'text-sm text-muted-foreground transition-colors hover:text-destructive',
    outline: 'inline-flex h-9 items-center justify-center rounded-md border border-input bg-transparent px-3 py-2 text-sm font-medium text-foreground shadow-sm transition-colors hover:border-destructive/40 hover:bg-destructive/10 hover:text-destructive focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring',
    danger: 'inline-flex h-9 items-center justify-center rounded-md bg-destructive px-4 py-2 text-sm font-medium text-destructive-foreground shadow-sm transition-colors hover:bg-destructive/90 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-destructive',
};

function show() {
    open.value = true;

    if (props.requiresPassword) {
        nextTick(() => password.value?.focus());
    }
}
</script>

<template>
    <button type="button" :class="triggerClasses[variant] ?? triggerClasses.link" @click="show">
        {{ label }}
    </button>

    <Teleport to="body">
        <Transition
            enter-active-class="ease-out duration-200"
            enter-from-class="opacity-0"
            leave-active-class="ease-in duration-150"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6"
                @keydown.escape="open = false"
            >
                <div class="absolute inset-0 bg-background/80 backdrop-blur-sm" @click="open = false"></div>

                <form
                    :action="action"
                    method="POST"
                    class="relative w-full rounded-lg border border-border bg-popover p-6 text-popover-foreground shadow-lg sm:max-w-lg"
                >
                    <input type="hidden" name="_token" :value="csrf">
                    <input type="hidden" name="_method" :value="method">

                    <h2 class="text-lg font-semibold tracking-tight">{{ title }}</h2>

                    <p v-if="message" class="mt-2 text-sm text-muted-foreground">{{ message }}</p>

                    <div v-if="requiresPassword" class="mt-5">
                        <input
                            ref="password"
                            type="password"
                            name="password"
                            placeholder="Password"
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm text-foreground shadow-sm transition-colors placeholder:text-muted-foreground focus:border-ring focus:outline-none focus:ring-1 focus:ring-ring"
                        >
                        <p v-if="passwordError" class="mt-2 text-sm font-medium text-destructive">{{ passwordError }}</p>
                    </div>

                    <div class="mt-6 flex justify-end gap-2">
                        <button
                            type="button"
                            class="inline-flex h-9 items-center justify-center rounded-md border border-input bg-transparent px-4 py-2 text-sm font-medium text-foreground shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground"
                            @click="open = false"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            class="inline-flex h-9 items-center justify-center rounded-md bg-destructive px-4 py-2 text-sm font-medium text-destructive-foreground shadow-sm transition-colors hover:bg-destructive/90"
                        >
                            {{ confirmLabel }}
                        </button>
                    </div>
                </form>
            </div>
        </Transition>
    </Teleport>
</template>
