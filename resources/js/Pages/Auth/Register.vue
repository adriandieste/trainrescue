<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    rol: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <div class="min-h-screen flex flex-col justify-center items-center bg-gradient-to-b from-blue-400 to-blue-600 py-20">
        <div class="flex flex-col items-center mb-6">
            <img
                src="../../../imagenes/logoTrain&Rescue.png"
                alt="Train & Rescue"
                class="w-24 h-24 mb-2 rounded-full shadow-md  object-contain"
            />
            <h1 class="text-3xl font-extrabold text-white">
                Train & Rescue
            </h1>
            <p class="text-white mt-1">Crea tu cuenta</p>
        </div>
        <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-8 mx-2">


            <form @submit.prevent="submit">
                <h2 class="text-xl font-bold text-center mb-1">Registro</h2>
                <p class="text-center text-neutral-500 mb-6">
                    Completa tus datos para empezar
                </p>

                <div class="mb-4">
                    <InputLabel for="name" value="Nombre Completo" class="mb-1" />

                    <TextInput
                        id="name"
                        type="text"
                        v-model="form.name"
                        required
                        autofocus
                        autocomplete="name"
                        class="w-full rounded-lg border border-neutral-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-4 py-3 text-base placeholder-neutral-400"
                        placeholder="Tu nombre"
                    />

                    <InputError :message="form.errors.name" />
                </div>

                <div class="mb-4">
                    <InputLabel for="email" value="Email" class="mb-1" />

                    <TextInput
                        id="email"
                        type="email"
                        v-model="form.email"
                        required
                        autocomplete="username"
                        class="w-full rounded-lg border border-neutral-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-4 py-3 text-base placeholder-neutral-400"
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
                        class="w-full rounded-lg border border-neutral-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-4 py-3 text-base placeholder-neutral-400"
                        placeholder="********"
                    />

                    <InputError :message="form.errors.password" />
                </div>

                <div class="mb-4">
                    <InputLabel
                        for="password_confirmation"
                        value="Confirmar contraseña"
                        class="mb-1"
                    />

                    <TextInput
                        id="password_confirmation"
                        type="password"
                        v-model="form.password_confirmation"
                        required
                        autocomplete="new-password"
                        class="w-full rounded-lg border border-neutral-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-4 py-3 text-base placeholder-neutral-400"
                        placeholder="********"
                    />

                    <InputError :message="form.errors.password_confirmation" />
                </div>

                <div class="mb-4">
                    <InputLabel for="rol" value="Selecciona tu rol" class="mb-1" />

                    <div class="flex flex-col gap-3 mt-2">
                        <div
                            :class="[
                                'flex items-center p-4 rounded-xl border cursor-pointer transition-all',
                                form.rol === 'atleta'
                                    ? 'border-blue-500 bg-blue-50 shadow'
                                    : 'border-neutral-200 bg-white',
                                'hover:border-blue-400',
                            ]"
                            @click="form.rol = 'atleta'"
                        >
                            <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center bg-blue-100 rounded-lg mr-4">
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
                                <div class="text-neutral-500 text-sm">
                                    Realizo entrenamientos
                                </div>
                            </div>
                            <input
                                type="radio"
                                v-model="form.rol"
                                value="atleta"
                                class="ml-auto accent-blue-500 sr-only"
                                tabindex="-1"
                                aria-hidden="true"
                            />
                        </div>
                        <div
                            :class="[
                                'flex items-center p-4 rounded-xl border cursor-pointer transition-all',
                                form.rol === 'entrenador'
                                    ? 'border-orange-400 bg-orange-50 shadow'
                                    : 'border-neutral-200 bg-white',
                                'hover:border-orange-300',
                            ]"
                            @click="form.rol = 'entrenador'"
                        >
                            <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center bg-orange-100 rounded-lg mr-4">
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
                                <div class="text-neutral-500 text-sm">
                                    Gestiono entrenamientos
                                </div>
                            </div>
                            <input
                                type="radio"
                                v-model="form.rol"
                                value="entrenador"
                                class="ml-auto accent-orange-400 sr-only"
                                tabindex="-1"
                                aria-hidden="true"
                            />
                        </div>
                    </div>

                    <InputError :message="form.errors.rol" />
                </div>

                <button
                    type="submit"
                    class="w-full bg-neutral-400 text-white font-bold py-3 rounded-xl mt-2 shadow-md transition-all hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="form.processing"
                >
                    Crear Cuenta
                </button>

                <div class="mt-6 text-center">
                    <span class="text-neutral-600">¿Ya tienes cuenta? </span>
                    <Link
                        :href="route('login')"
                        class="text-red-500 font-semibold hover:underline"
                    >
                        Inicia sesión
                    </Link>
                </div>
            </form>
        </div>
    </div>
</template>
