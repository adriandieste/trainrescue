<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
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
    <AuthLayout title="Zona de Seguridad" subtitle="Verifica tu identidad para realizar cambios importantes en tu cuenta">
        <Head title="Confirmar contraseña" />

        <div class="mb-8">
            <h2 class="text-3xl font-bold text-slate-800">Confirmación</h2>
            <p class="text-slate-500 mt-2">Introduce tu contraseña actual para continuar.</p>
        </div>

        <div class="mb-6 p-4 bg-amber-50 border border-amber-100 rounded-2xl flex items-start gap-3 text-amber-800 text-sm">
            <span class="text-lg">🛡️</span>
            <span>Estás intentando acceder a una zona protegida. Esta medida garantiza la seguridad de tus datos.</span>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div>
                <InputLabel for="password" value="Tu contraseña" class="ml-1 mb-1 text-slate-700 font-semibold" />
                <TextInput
                    id="password"
                    type="password"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    autofocus
                    class="w-full !rounded-xl !border-slate-200 focus:!ring-blue-500/20"
                    placeholder="••••••••"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full bg-red-500 hover:bg-red-600 text-white font-bold py-4 rounded-2xl shadow-lg shadow-red-500/30 transition-all active:scale-[0.98] disabled:opacity-50"
            >
                Confirmar y Continuar
            </button>

            <div class="pt-2 text-center">
                <p class="text-xs text-slate-400">Protección de cuenta activa</p>
            </div>
        </form>
    </AuthLayout>
</template>
