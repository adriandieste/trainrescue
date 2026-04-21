<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';

const layoutRef = ref(null);
const layoutScale = ref(1);

const recalculateScale = () => {
    if (!layoutRef.value) return;
    const vh = window.innerHeight;
    const contentHeight = layoutRef.value.scrollHeight;
    // Si el contenido es más alto que la pantalla, reducimos escala (mínimo 0.8)
    if (contentHeight > vh - 20) {
        layoutScale.value = Math.max(0.8, (vh - 20) / contentHeight);
    } else {
        layoutScale.value = 1;
    }
};

onMounted(() => {
    recalculateScale();
    window.addEventListener('resize', recalculateScale);
});
onBeforeUnmount(() => window.removeEventListener('resize', recalculateScale));
</script>

<template>
    <div class="min-h-[100svh] w-full overflow-x-hidden bg-gradient-to-br from-blue-600 via-blue-500 to-blue-400 flex items-center justify-center p-4 sm:p-8">

        <div ref="layoutRef"
             class="w-full max-w-[1100px] transition-transform duration-300 ease-out"
             :style="{ transform: `scale(${layoutScale})` }">

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-16 items-center">

                <div class="text-center lg:text-left text-white space-y-4">
                    <div class="inline-flex p-3 bg-white/10 backdrop-blur-md rounded-2xl mb-2">
                        <img src="/imagenes/trainrescue-logo-horizontal.png" alt="Logo" class="h-12 w-auto object-contain" />
                    </div>
                    <h1 class="text-4xl sm:text-5xl font-black tracking-tight drop-shadow-md">
                        Train & Rescue
                    </h1>
                    <p class="text-lg sm:text-xl text-blue-50 font-medium opacity-90 max-w-md mx-auto lg:mx-0">
                        La plataforma definitiva para el Salvamento y Socorrismo profesional.
                    </p>

                    <div class="hidden lg:flex gap-4 mt-8">
                        <div class="h-1 w-20 bg-red-500 rounded-full"></div>
                        <div class="h-1 w-10 bg-white/30 rounded-full"></div>
                    </div>
                </div>

                <div class="bg-white rounded-[2.5rem] shadow-2xl p-6 sm:p-10 border border-white/20">
                    <slot />
                </div>

            </div>
        </div>
    </div>
</template>
