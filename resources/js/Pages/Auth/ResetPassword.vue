<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <div class="min-h-screen flex flex-col justify-center items-center bg-gradient-to-b from-blue-400 to-blue-600 py-20">
        <Head title="Restablecer contraseña" />

        <div class="flex flex-col items-center mb-6">
            <img
                src="/imagenes/trainrescue-logo-horizontal.png"
                alt="Logo de Train & Rescue"
                class="mb-2 h-12 w-auto object-contain"
            />
            <h1 class="text-3xl font-extrabold text-white">Train & Rescue</h1>
            <p class="text-white mt-1">Crea una nueva contraseña</p>
        </div>

        <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-8 mx-2">
            <form @submit.prevent="submit">
                <h2 class="text-xl font-bold text-center mb-1">Restablecer contraseña</h2>
                <p class="text-center text-neutral-500 mb-6">
                    Introduce tu nueva contraseña para volver a acceder.
                </p>

                <div class="mb-4">
                    <InputLabel for="email" value="Email" class="mb-1" />

                    <TextInput
                        id="email"
                        type="email"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                        class="w-full rounded-lg border border-neutral-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-4 py-3 text-base placeholder-neutral-400"
                        placeholder="ejemplo@email.com"
                    />

                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <div class="mb-4">
                    <InputLabel for="password" value="Nueva contraseña" class="mb-1" />

                    <TextInput
                        id="password"
                        type="password"
                        v-model="form.password"
                        required
                        autocomplete="new-password"
                        class="w-full rounded-lg border border-neutral-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-4 py-3 text-base placeholder-neutral-400"
                        placeholder="********"
                    />

                    <InputError class="mt-2" :message="form.errors.password" />
                </div>

                <div class="mb-4">
                    <InputLabel for="password_confirmation" value="Confirmar contraseña" class="mb-1" />

                    <TextInput
                        id="password_confirmation"
                        type="password"
                        v-model="form.password_confirmation"
                        required
                        autocomplete="new-password"
                        class="w-full rounded-lg border border-neutral-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-4 py-3 text-base placeholder-neutral-400"
                        placeholder="********"
                    />

                    <InputError class="mt-2" :message="form.errors.password_confirmation" />
                </div>

                <button
                    type="submit"
                    class="w-full bg-red-500 text-white font-bold py-3 rounded-xl mt-2 shadow-md transition-all hover:bg-red-600 disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="form.processing"
                >
                    Guardar nueva contraseña
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
