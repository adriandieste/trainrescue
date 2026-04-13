<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    rol: '',
});

const layoutRef = ref(null);
const isCompact = ref(false);
const layoutScale = ref(1);
let resizeObserver;

const recalculateLayout = () => {
    if (!layoutRef.value) {
        return;
    }

    const viewportHeight = window.innerHeight;
    const contentHeight = layoutRef.value.scrollHeight;
    const isOverflowing = contentHeight > viewportHeight - 8;

    isCompact.value = isOverflowing;
    layoutScale.value = isOverflowing
        ? Math.max(0.8, Math.min(1, (viewportHeight - 8) / contentHeight))
        : 1;
};

onMounted(async () => {
    await nextTick();
    recalculateLayout();

    window.addEventListener('resize', recalculateLayout);
    resizeObserver = new ResizeObserver(recalculateLayout);

    if (layoutRef.value) {
        resizeObserver.observe(layoutRef.value);
    }
});

onBeforeUnmount(() => {
    window.removeEventListener('resize', recalculateLayout);

    if (resizeObserver) {
        resizeObserver.disconnect();
    }
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Registro" />

    <div class="h-screen overflow-hidden bg-gradient-to-b from-blue-400 to-blue-600">
        <div class="mx-auto flex h-full w-full items-center justify-center p-3 sm:p-5">
            <div
                ref="layoutRef"
                class="w-full origin-center transition-transform duration-150"
                :style="{ transform: `scale(${layoutScale})` }"
            >
                <div
                    class="mx-auto grid w-full gap-5"
                    :class="isCompact ? 'max-w-6xl md:grid-cols-2 md:items-center' : 'max-w-md grid-cols-1'"
                >
                    <div
                        class="flex flex-col items-center"
                        :class="isCompact ? 'md:items-start md:pl-6' : 'mb-2'"
                    >
                        <img
                            src="/imagenes/trainrescue-logo-horizontal.png"
                            alt="Logo de Train & Rescue"
                            class="mb-2 h-12 w-auto object-contain"
                        />
                        <h1 class="text-3xl font-extrabold text-white">Train & Rescue</h1>
                        <p class="mt-1 text-white">Crea tu cuenta</p>
                    </div>

                    <div class="w-full rounded-2xl bg-white p-8 shadow-xl">
                        <form @submit.prevent="submit">
                            <h2 class="mb-1 text-center text-xl font-bold">Registro</h2>
                            <p class="mb-6 text-center text-neutral-500">
                                Completa tus datos para empezar
                            </p>

                            <div class="mb-4">
                                <InputLabel for="name" value="Nombre completo" class="mb-1" />
                                <TextInput
                                    id="name"
                                    type="text"
                                    v-model="form.name"
                                    required
                                    autofocus
                                    autocomplete="name"
                                    class="w-full rounded-lg border border-neutral-300 px-4 py-3 text-base placeholder-neutral-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                                    placeholder="Tu nombre"
                                />
                                <InputError :message="form.errors.name" />
                            </div>

                            <div class="mb-4">
                                <InputLabel for="email" value="Correo electrónico" class="mb-1" />
                                <TextInput
                                    id="email"
                                    type="email"
                                    v-model="form.email"
                                    required
                                    autocomplete="username"
                                    class="w-full rounded-lg border border-neutral-300 px-4 py-3 text-base placeholder-neutral-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                                    placeholder="ejemplo@email.com"
                                />
                                <InputError :message="form.errors.email" />
                            </div>

                            <div class="mb-4">
                                <InputLabel for="password" value="Contraseña" class="mb-1" />
                                <TextInput
                                    id="password"
                                    type="password"
                                    v-model="form.password"
                                    required
                                    autocomplete="new-password"
                                    class="w-full rounded-lg border border-neutral-300 px-4 py-3 text-base placeholder-neutral-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                                    placeholder="********"
                                />
                                <InputError :message="form.errors.password" />
                            </div>

                            <div class="mb-4">
                                <InputLabel for="password_confirmation" value="Confirmar contraseña" class="mb-1" />
                                <TextInput
                                    id="password_confirmation"
                                    type="password"
                                    v-model="form.password_confirmation"
                                    required
                                    autocomplete="new-password"
                                    class="w-full rounded-lg border border-neutral-300 px-4 py-3 text-base placeholder-neutral-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                                    placeholder="********"
                                />
                                <InputError :message="form.errors.password_confirmation" />
                            </div>

                            <div class="mb-4">
                                <InputLabel for="rol" value="Selecciona tu rol" class="mb-1" />

                                <div class="mt-2 flex flex-col gap-3">
                                    <div
                                        :class="[
                                            'flex cursor-pointer items-center rounded-xl border p-4 transition-all',
                                            form.rol === 'atleta'
                                                ? 'border-blue-500 bg-blue-50 shadow'
                                                : 'border-neutral-200 bg-white',
                                            'hover:border-blue-400',
                                        ]"
                                        @click="form.rol = 'atleta'"
                                    >
                                        <div class="mr-4 flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-blue-100">
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                class="h-6 w-6 text-blue-500"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M5.121 17.804A13.937 13.937 0 0112 15c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z"
                                                />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="font-bold text-neutral-900">
                                                Atleta / Socorrista
                                            </div>
                                            <div class="text-sm text-neutral-500">
                                                Realizo entrenamientos
                                            </div>
                                        </div>
                                        <input
                                            type="radio"
                                            v-model="form.rol"
                                            value="atleta"
                                            class="sr-only"
                                            tabindex="-1"
                                            aria-hidden="true"
                                        />
                                    </div>

                                    <div
                                        :class="[
                                            'flex cursor-pointer items-center rounded-xl border p-4 transition-all',
                                            form.rol === 'entrenador'
                                                ? 'border-orange-400 bg-orange-50 shadow'
                                                : 'border-neutral-200 bg-white',
                                            'hover:border-orange-300',
                                        ]"
                                        @click="form.rol = 'entrenador'"
                                    >
                                        <div class="mr-4 flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-orange-100">
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                class="h-6 w-6 text-orange-400"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                                />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="font-bold text-neutral-900">
                                                Entrenador
                                            </div>
                                            <div class="text-sm text-neutral-500">
                                                Gestiono entrenamientos
                                            </div>
                                        </div>
                                        <input
                                            type="radio"
                                            v-model="form.rol"
                                            value="entrenador"
                                            class="sr-only"
                                            tabindex="-1"
                                            aria-hidden="true"
                                        />
                                    </div>
                                </div>

                                <InputError :message="form.errors.rol" />
                            </div>

                            <button
                                type="submit"
                                class="mt-2 w-full rounded-xl bg-neutral-400 py-3 font-bold text-white shadow-md transition-all hover:bg-blue-600 disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="form.processing"
                            >
                                Crear cuenta
                            </button>

                            <div class="mt-6 text-center">
                                <span class="text-neutral-600">¿Ya tienes cuenta? </span>
                                <Link :href="route('login')" class="font-semibold text-red-500 hover:underline">
                                    Inicia sesión
                                </Link>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
