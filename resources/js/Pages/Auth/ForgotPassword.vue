<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
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
    <AuthLayout title="Recupera tu acceso" subtitle="Te ayudaremos a volver a entrar en tu cuenta rápidamente">
        <Head title="Recuperar contraseña" />

        <div class="mb-8 text-center lg:text-left">
            <h2 class="text-3xl font-bold text-slate-800">¿Olvidaste tu clave?</h2>
            <p class="text-slate-500 mt-2">Introduce tu email y te enviaremos las instrucciones de recuperación.</p>
        </div>

        <div
            v-if="status"
            class="mb-6 p-4 rounded-2xl bg-green-50 border border-green-100 text-sm text-green-700 flex items-start gap-3"
        >
            <span class="text-lg">📩</span>
            <span>{{ status }}</span>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div>
                <InputLabel for="email" value="Correo electrónico" class="ml-1 mb-1 text-slate-700 font-semibold" />
                <TextInput
                    id="email"
                    type="email"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                    class="w-full !rounded-xl !border-slate-200 focus:!ring-blue-500/20"
                    placeholder="ejemplo@email.com"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full bg-red-500 hover:bg-red-600 text-white font-bold py-4 rounded-2xl shadow-lg shadow-red-500/30 transition-all active:scale-[0.98] disabled:opacity-50"
            >
                Enviar enlace de recuperación
            </button>

            <div class="pt-6 text-center border-t border-slate-50 mt-4">
                <p class="text-slate-500 text-sm">
                    ¿Recordaste tu contraseña?
                    <Link :href="route('login')" class="text-blue-600 font-bold hover:text-blue-700 transition-colors ml-1">
                        Inicia sesión
                    </Link>
                </p>
            </div>
        </form>
    </AuthLayout>
</template>
