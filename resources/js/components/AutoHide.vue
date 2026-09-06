<script setup>
import { onMounted, ref } from 'vue';

const props = defineProps({
    message: { type: String, required: true },
    delay: { type: Number, default: 2000 },
    classes: { type: String, default: 'text-sm text-muted-foreground' },
});

const visible = ref(true);

onMounted(() => setTimeout(() => (visible.value = false), props.delay));
</script>

<template>
    <Transition leave-active-class="transition ease-in duration-300" leave-to-class="opacity-0">
        <p v-if="visible" :class="classes">{{ message }}</p>
    </Transition>
</template>
