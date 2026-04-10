<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <div class="min-h-screen flex flex-col justify-center items-center bg-gradient-to-b from-blue-400 to-blue-600 py-20">
        <Head title="Confirmar contraseña" />

        <div class="flex flex-col items-center mb-6">
            <img
                src="../../../imagenes/logoTrain&Rescue.png"
                alt="Train & Rescue"
                class="w-24 h-24 mb-2 rounded-full shadow-md object-contain"
            />
            <h1 class="text-3xl font-extrabold text-white">Train & Rescue</h1>
            <p class="text-white mt-1">Salvamento y Socorrismo</p>
        </div>

        <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-8 mx-2">
            <h2 class="text-xl font-bold text-center mb-1">Confirmar contraseña</h2>
            <p class="text-center text-neutral-500 mb-6">
                Esta es una zona segura. Por favor, confirma tu contraseña antes de continuar.
            </p>

            <form @submit.prevent="submit">
                <div class="mb-4">
                    <InputLabel for="password" value="Contraseña" class="mb-1" />
                    <TextInput
                        id="password"
                        type="password"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                        autofocus
                        class="w-full rounded-lg border border-neutral-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-4 py-3 text-base placeholder-neutral-400"
                        placeholder="********"
                    />
                    <InputError class="mt-2" :message="form.errors.password" />
                </div>

                <button
                    type="submit"
                    class="w-full bg-red-500 text-white font-bold py-3 rounded-xl mt-2 shadow-md transition-all hover:bg-red-600 disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="form.processing"
                >
                    Confirmar
                </button>
            </form>
        </div>
    </div>
</template>
