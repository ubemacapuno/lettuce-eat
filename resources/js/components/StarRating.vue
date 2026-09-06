<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    name: { type: String, default: 'rating' },
    value: { type: [String, Number], default: null },
    max: { type: Number, default: 5 },
});

const rating = ref(props.value === null || props.value === '' ? null : Number(props.value));
const hovered = ref(null);

const displayed = computed(() => hovered.value ?? rating.value ?? 0);

/** Half a star per click: the left half of star 3 means 2.5, the right half means 3. */
function valueAt(star, event) {
    const { left, width } = event.currentTarget.getBoundingClientRect();

    return event.clientX - left < width / 2 ? star - 0.5 : star;
}

/** How much of this star is filled in, as a CSS width. */
function fill(star) {
    return `${Math.min(Math.max(displayed.value - (star - 1), 0), 1) * 100}%`;
}
</script>

<template>
    <div class="flex h-9 items-center gap-3">
        <input type="hidden" :name="name" :value="rating ?? ''">

        <div class="flex gap-0.5" @mouseleave="hovered = null">
            <button
                v-for="star in max"
                :key="star"
                type="button"
                class="relative rounded text-xl leading-none transition-transform hover:scale-110 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                :aria-label="`Rate ${star} out of ${max}`"
                @mousemove="hovered = valueAt(star, $event)"
                @click="rating = valueAt(star, $event)"
            >
                <span class="text-muted/70">★</span>
                <span class="absolute inset-0 overflow-hidden text-foreground" :style="{ width: fill(star) }">★</span>
            </button>
        </div>

        <span class="w-7 text-sm tabular-nums text-muted-foreground">
            {{ rating === null ? '—' : rating.toFixed(1) }}
        </span>

        <button
            v-if="rating !== null"
            type="button"
            class="text-xs text-muted-foreground transition-colors hover:text-foreground"
            @click="rating = null"
        >
            clear
        </button>
    </div>
</template>
