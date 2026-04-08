<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

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

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
     <div class="min-h-screen flex flex-col justify-center items-center bg-gradient-to-b from-blue-400 to-blue-600 py-20" >
         <div class="flex flex-col items-center mb-6">
             <img
                 src="../../../imagenes/logoTrain&Rescue.png"
                 alt="Train & Rescue"
                 class="w-24 h-24 mb-2 rounded-full shadow-md  object-contain"
             />
             <h1 class="text-3xl font-extrabold text-white">Train & Rescue</h1>
             <p class="text-white mt-1">Salvamento y Socorrismo</p>
         </div>
         <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-8  mx-2">

            <form @submit.prevent="submit">
                <h2 class="text-xl font-bold text-center mb-1">Iniciar Sesión</h2>
                <p class="text-center text-neutral-500 mb-6">Accede a tu cuenta</p>
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
                        class="w-full rounded-lg border border-neutral-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-4 py-3 text-base placeholder-neutral-400"
                        placeholder="********"
                    />
                    <InputError :message="form.errors.password" />
                </div>
                <div class="flex justify-end mb-4">
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
                    class="w-full bg-red-500 text-white font-bold py-3 rounded-xl mt-2 shadow-md transition-all hover:bg-red-600 disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="form.processing"
                >
                    Iniciar Sesión
                </button>
                <div class="mt-6 text-center">
                    <span class="text-neutral-600">¿No tienes cuenta? </span>
                    <Link :href="route('register')" class="text-red-500 font-semibold hover:underline">Regístrate aquí</Link>
                </div>
            </form>
        </div>
    </div>
</template>
