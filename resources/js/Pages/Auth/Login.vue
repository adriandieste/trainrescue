<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const layoutRef = ref(null);
const isCompact = ref(false);
const isHorizontal = ref(false);
const layoutScale = ref(1);
let resizeObserver;
let rafId = null;
let measureToken = 0;

const HEIGHT_EPSILON = 4;
const HORIZONTAL_ENTER_DELTA = 8;

const recalculateLayout = async () => {
    if (!layoutRef.value) {
        return;
    }

    const token = ++measureToken;
    const availableHeight = window.innerHeight - 8;
    const canUseHorizontal = window.innerWidth >= 960;

    const measureOverflow = () => layoutRef.value.scrollHeight - availableHeight;

    // Mantenemos el layout actual para evitar oscilaciones cerca del borde.
    await nextTick();
    if (token !== measureToken || !layoutRef.value) {
        return;
    }

    let overflowDelta = measureOverflow();
    let shouldUseHorizontal = isHorizontal.value;

    if (!canUseHorizontal) {
        shouldUseHorizontal = false;
    } else if (!isHorizontal.value && overflowDelta > HORIZONTAL_ENTER_DELTA) {
        shouldUseHorizontal = true;
    }

    if (shouldUseHorizontal !== isHorizontal.value) {
        isHorizontal.value = shouldUseHorizontal;
        await nextTick();
        if (token !== measureToken || !layoutRef.value) {
            return;
        }
        overflowDelta = measureOverflow();
    }

    const isOverflowing = overflowDelta > HEIGHT_EPSILON;
    isCompact.value = isHorizontal.value || isOverflowing;

    if (isOverflowing) {
        const rawScale = availableHeight / layoutRef.value.scrollHeight;
        layoutScale.value = Math.max(0.9, Math.min(1, rawScale));
    } else {
        layoutScale.value = 1;
    }
};

const runRecalculateLayout = () => {
    if (rafId !== null) {
        cancelAnimationFrame(rafId);
    }

    rafId = requestAnimationFrame(() => {
        rafId = null;
        void recalculateLayout();
    });
};

onMounted(async () => {
    await nextTick();
    runRecalculateLayout();

    window.addEventListener('resize', runRecalculateLayout);
    resizeObserver = new ResizeObserver(runRecalculateLayout);

    if (layoutRef.value) {
        resizeObserver.observe(layoutRef.value);
    }
});

onBeforeUnmount(() => {
    window.removeEventListener('resize', runRecalculateLayout);

    if (rafId !== null) {
        cancelAnimationFrame(rafId);
    }

    if (resizeObserver) {
        resizeObserver.disconnect();
    }
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Iniciar sesión" />

    <div class="h-[100svh] overflow-hidden bg-gradient-to-b from-blue-400 to-blue-600">
        <div class="mx-auto flex h-full w-full items-center justify-center p-3 sm:p-5">
            <div
                ref="layoutRef"
                class="w-full origin-center transition-transform duration-150"
                :style="{ transform: `scale(${layoutScale})` }"
            >
                <div
                    class="mx-auto grid w-full"
                    :class="isHorizontal
                        ? 'max-w-6xl grid-cols-1 md:grid-cols-[280px_1fr] items-center gap-6'
                        : 'max-w-md grid-cols-1 gap-5'"
                >
                    <div
                        class="flex flex-col"
                        :class="isHorizontal ? 'items-start pl-4' : 'mb-2 items-center'"
                    >
                        <img
                            src="/imagenes/trainrescue-logo-horizontal.png"
                            alt="Logo de Train & Rescue"
                            class="mb-2 w-auto object-contain"
                            :class="isCompact ? 'h-10' : 'h-12'"
                        />
                        <h1 class="font-extrabold text-white" :class="isCompact ? 'text-2xl' : 'text-3xl'">Train & Rescue</h1>
                        <p class="mt-1 text-white">Salvamento y Socorrismo</p>
                    </div>

                    <div class="w-full rounded-2xl bg-white shadow-xl" :class="isCompact ? 'p-6' : 'p-8'">
                        <form @submit.prevent="submit">
                            <h2 class="mb-1 text-center text-xl font-bold">Iniciar sesión</h2>
                            <p class="text-center text-neutral-500" :class="isCompact ? 'mb-4' : 'mb-6'">Accede a tu cuenta</p>

                            <div v-if="status" class="mb-4 rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-700">
                                {{ status }}
                            </div>

                            <div class="mb-4">
                                <InputLabel for="email" value="Correo electrónico" class="mb-1" />
                                <TextInput
                                    id="email"
                                    type="email"
                                    v-model="form.email"
                                    required
                                    autofocus
                                    autocomplete="username"
                                    class="w-full rounded-lg border border-neutral-300 px-4 py-3 text-base placeholder-neutral-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                                    placeholder="ejemplo@email.com"
                                />
                                <InputError :message="form.errors.email" />
                            </div>

                            <div class="mb-2">
                                <InputLabel for="password" value="Contraseña" class="mb-1" />
                                <TextInput
                                    id="password"
                                    type="password"
                                    v-model="form.password"
                                    required
                                    autocomplete="current-password"
                                    class="w-full rounded-lg border border-neutral-300 px-4 py-3 text-base placeholder-neutral-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                                    placeholder="********"
                                />
                                <InputError :message="form.errors.password" />
                            </div>

                            <div class="mb-4 flex justify-end">
                                <Link
                                    v-if="canResetPassword"
                                    :href="route('password.request')"
                                    class="text-sm text-blue-500 hover:underline"
                                >
                                    ¿Olvidaste tu contraseña?
                                </Link>
                            </div>

                            <button
                                type="submit"
                                class="mt-2 w-full rounded-xl bg-red-500 py-3 font-bold text-white shadow-md transition-all hover:bg-red-600 disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="form.processing"
                            >
                                Iniciar sesión
                            </button>

                            <div class="text-center" :class="isCompact ? 'mt-4' : 'mt-6'">
                                <span class="text-neutral-600">¿No tienes cuenta? </span>
                                <Link :href="route('register')" class="font-semibold text-red-500 hover:underline">Regístrate aquí</Link>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
