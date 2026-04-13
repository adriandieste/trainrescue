<script setup>
const props = defineProps({
    variant: {
        type: String,
        default: 'round',
    },
    alt: {
        type: String,
        default: 'Logo de Train & Rescue',
    },
});
</script>
<template>
    <img
        :src="props.variant === 'horizontal' ? '/imagenes/trainrescue-logo-horizontal.png' : '/imagenes/logoTrain&Rescue.png'"
        :alt="props.alt"
        class="trainrescue-logo block w-auto object-contain"
    />
</template>
