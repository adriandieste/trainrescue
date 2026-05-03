<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({ name: '', email: '', password: '', password_confirmation: '', rol: '' });
const submit = () => form.post(route('register'), { onFinish: () => form.reset('password', 'password_confirmation') });
</script>

<template>
    <AuthLayout>
        <Head title="Crear cuenta" />

        <div class="mb-6 text-center lg:text-left">
            <h2 class="text-3xl font-bold text-slate-800">Registro</h2>
            <p class="text-slate-500 text-sm">Completa el formulario para empezar</p>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <InputLabel for="name" value="Nombre" />
                    <TextInput id="name" v-model="form.name" required class="w-full !rounded-xl" />
                    <InputError :message="form.errors.name" />
                </div>
                <div>
                    <InputLabel for="email" value="Email" />
                    <TextInput id="email" type="email" v-model="form.email" required class="w-full !rounded-xl" />
                    <InputError :message="form.errors.email" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <InputLabel for="password" value="Contraseña" />
                    <TextInput id="password" type="password" v-model="form.password" required class="w-full !rounded-xl" />
                    <InputError :message="form.errors.password" />
                </div>
                <div>
                    <InputLabel for="password_confirmation" value="Confirmar" />
                    <TextInput id="password_confirmation" type="password" v-model="form.password_confirmation" required class="w-full !rounded-xl" />
                    <InputError :message="form.errors.password_confirmation" />
                </div>
            </div>

            <div>
                <InputLabel value="¿Quién eres?" class="mb-3" />
                <div class="flex flex-col sm:flex-row gap-3">
                    <button type="button" @click="form.rol = 'socorrista'"
                            :class="['flex-1 p-4 rounded-2xl border-2 transition-all flex items-center gap-3', form.rol === 'socorrista' ? 'border-blue-500 bg-blue-50 shadow-md' : 'border-slate-100 hover:border-blue-200']">
                        <div class="bg-blue-100 p-2 rounded-lg text-blue-600">👤</div>
                        <span class="font-bold text-slate-700">Socorrista</span>
                    </button>
                    <button type="button" @click="form.rol = 'entrenador'"
                            :class="['flex-1 p-4 rounded-2xl border-2 transition-all flex items-center gap-3', form.rol === 'entrenador' ? 'border-orange-400 bg-orange-50 shadow-md' : 'border-slate-100 hover:border-orange-200']">
                        <div class="bg-orange-100 p-2 rounded-lg text-orange-600">📋</div>
                        <span class="font-bold text-slate-700">Entrenador</span>
                    </button>
                </div>
                <InputError :message="form.errors.rol" />
            </div>

            <button type="submit" :disabled="form.processing" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-2xl shadow-lg shadow-blue-500/30 transition-all mt-4">
                Crear mi cuenta
            </button>

            <p class="text-center text-sm text-slate-500 pt-2">
                ¿Ya tienes cuenta? <Link :href="route('login')" class="text-red-500 font-bold hover:underline">Inicia sesión</Link>
            </p>
        </form>
    </AuthLayout>
</template>
