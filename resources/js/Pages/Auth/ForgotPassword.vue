<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <div class="min-h-screen flex flex-col justify-center items-center bg-gradient-to-b from-blue-400 to-blue-600 py-20">
        <Head title="Recuperar contraseña" />

        <div class="flex flex-col items-center mb-6">
            <img
                src="../../../imagenes/logoTrain&Rescue.png"
                alt="Train & Rescue"
                class="w-24 h-24 mb-2 rounded-full shadow-md object-contain"
            />
            <h1 class="text-3xl font-extrabold text-white">Train & Rescue</h1>
            <p class="text-white mt-1">Recupera el acceso a tu cuenta</p>
        </div>

        <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-8 mx-2">
            <form @submit.prevent="submit">
                <h2 class="text-xl font-bold text-center mb-1">Olvidé mi contraseña</h2>
                <p class="text-center text-neutral-500 mb-6">
                    Introduce tu correo y te enviaremos un enlace para restablecerla.
                </p>

                <div
                    v-if="status"
                    class="mb-4 rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-700"
                >
                    {{ status }}
                </div>

                <div class="mb-4">
                    <InputLabel for="email" value="Email" class="mb-1" />

                    <TextInput
                        id="email"
                        type="email"
                        class="w-full rounded-lg border border-neutral-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-4 py-3 text-base placeholder-neutral-400"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="ejemplo@email.com"
                    />

                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <button
                    type="submit"
                    class="w-full bg-red-500 text-white font-bold py-3 rounded-xl mt-2 shadow-md transition-all hover:bg-red-600 disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="form.processing"
                >
                    Enviar enlace de recuperación
                </button>

                <div class="mt-6 text-center">
                    <Link :href="route('login')" class="text-red-500 font-semibold hover:underline">
                        Volver al inicio de sesión
                    </Link>
                </div>
            </form>
        </div>
    </div>
</template>
