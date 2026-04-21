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
            <h2 class="text-3xl font-bold text-slate-800">Bienvenido</h2>
            <p class="text-slate-500 mt-1 text-sm sm:text-base">Introduce tus credenciales para acceder</p>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <div v-if="status" class="p-3 bg-green-50 text-green-700 rounded-xl text-sm border border-green-100">{{ status }}</div>

            <div>
                <InputLabel for="email" value="Correo electrónico" class="ml-1 mb-1 text-slate-700 font-semibold" />
                <TextInput id="email" type="email" v-model="form.email" required autofocus class="w-full !rounded-xl !border-slate-200 focus:!ring-blue-500/20" placeholder="tu@email.com" />
                <InputError :message="form.errors.email" />
            </div>

            <div>
                <div class="flex justify-between items-center mb-1">
                    <InputLabel for="password" value="Contraseña" class="ml-1 text-slate-700 font-semibold" />
                    <Link v-if="canResetPassword" :href="route('password.request')" class="text-xs text-blue-600 hover:underline">¿Olvidaste la clave?</Link>
                </div>
                <TextInput id="password" type="password" v-model="form.password" required class="w-full !rounded-xl !border-slate-200 focus:!ring-blue-500/20" placeholder="••••••••" />
                <InputError :message="form.errors.password" />
            </div>

            <button type="submit" :disabled="form.processing" class="w-full bg-red-500 hover:bg-red-600 text-white font-bold py-4 rounded-2xl shadow-lg shadow-red-500/30 transition-all active:scale-[0.98] disabled:opacity-50">
                Iniciar sesión
            </button>

            <div class="pt-4 text-center">
                <p class="text-slate-600 text-sm">¿Aún no tienes cuenta? <Link :href="route('register')" class="text-red-500 font-bold hover:text-red-600">Regístrate gratis</Link></p>
            </div>
        </form>
    </AuthLayout>
</template>
