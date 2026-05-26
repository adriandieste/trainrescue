<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="block w-full rounded-lg border border-neutral-200 dark:border-zinc-800 bg-white dark:bg-black px-3 py-2 text-sm placeholder:text-neutral-400 dark:placeholder:text-zinc-500 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary dark:focus:ring-blue-500/40 dark:focus:border-blue-500"
        v-model="model"
        ref="input"
    />
</template>
