<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({ canResetPassword: { type: Boolean }, status: { type: String } });

const form = useForm({ email: '', password: '', remember: false });
const submit = () => form.post(route('login'), { onFinish: () => form.reset('password') });
</script>

<template>
    <AuthLayout>
        <Head title="Iniciar sesión" />

        <div class="mb-8">
            <h2 class="text-3xl font-bold text-slate-800 dark:text-white">Bienvenido</h2>
            <p class="text-slate-500 dark:text-zinc-400 mt-1 text-sm sm:text-base">Introduce tus credenciales para acceder</p>
        </div>

        <a
            :href="route('auth.google.redirect')"
            class="mb-5 inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 px-4 py-3 font-semibold text-slate-700 dark:text-zinc-100 shadow-sm transition hover:bg-slate-50 dark:hover:bg-zinc-800"
        >
            <svg class="h-5 w-5" viewBox="0 0 48 48" aria-hidden="true">
                <path fill="#FFC107" d="M43.611 20.083H42V20H24v8h11.303C33.659 32.657 29.24 36 24 36c-6.627 0-12-5.373-12-12s5.373-12 12-12c3.059 0 5.842 1.154 7.959 3.041l5.657-5.657C34.046 6.053 29.27 4 24 4 12.955 4 4 12.955 4 24s8.955 20 20 20 20-8.955 20-20c0-1.341-.138-2.65-.389-3.917z"/>
                <path fill="#FF3D00" d="M6.306 14.691l6.571 4.819C14.655 15.108 18.961 12 24 12c3.059 0 5.842 1.154 7.959 3.041l5.657-5.657C34.046 6.053 29.27 4 24 4 16.318 4 9.656 8.337 6.306 14.691z"/>
                <path fill="#4CAF50" d="M24 44c5.168 0 9.86-1.977 13.409-5.197l-6.19-5.238C29.144 35.091 26.692 36 24 36c-5.219 0-9.623-3.317-11.283-7.946l-6.522 5.025C9.505 39.556 16.227 44 24 44z"/>
                <path fill="#1976D2" d="M43.611 20.083H42V20H24v8h11.303a12.03 12.03 0 0 1-4.084 5.565l.003-.002 6.19 5.238C36.971 39.205 44 34 44 24c0-1.341-.138-2.65-.389-3.917z"/>
            </svg>
            Inicia sesión con Google
        </a>

        <form @submit.prevent="submit" class="space-y-5">
            <div v-if="status" class="p-3 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 rounded-xl text-sm border border-green-100 dark:border-green-800">{{ status }}</div>

            <div>
                <InputLabel for="email" value="Correo electrónico" class="ml-1 mb-1 text-slate-700 dark:text-zinc-300 font-semibold" />
                <TextInput id="email" type="email" v-model="form.email" required autofocus class="w-full !rounded-xl !border-slate-200 dark:!border-zinc-800 focus:!ring-blue-500/20" placeholder="tu@email.com" />
                <InputError :message="form.errors.email" />
            </div>

            <div>
                <div class="flex justify-between items-center mb-1">
                    <InputLabel for="password" value="Contraseña" class="ml-1 text-slate-700 dark:text-zinc-300 font-semibold" />
                    <Link v-if="canResetPassword" :href="route('password.request')" class="text-xs text-blue-600 dark:text-blue-400 hover:underline">¿Olvidaste la clave?</Link>
                </div>
                <TextInput id="password" type="password" v-model="form.password" required class="w-full !rounded-xl !border-slate-200 dark:!border-zinc-800 focus:!ring-blue-500/20" placeholder="••••••••" />
                <InputError :message="form.errors.password" />
            </div>

            <button type="submit" :disabled="form.processing" class="w-full bg-red-500 hover:bg-red-600 text-white font-bold py-4 rounded-2xl shadow-lg shadow-red-500/30 transition-all active:scale-[0.98] disabled:opacity-50">
                Iniciar sesión
            </button>

            <div class="pt-4 text-center">
                <p class="text-slate-600 dark:text-zinc-400 text-sm">¿Aún no tienes cuenta? <Link :href="route('register')" class="text-red-500 font-bold hover:text-red-600">Regístrate gratis</Link></p>
            </div>
        </form>
    </AuthLayout>
</template>
