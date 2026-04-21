<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
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
    <AuthLayout title="Nueva contraseña" subtitle="Crea una clave segura para volver a entrar en acción">
        <Head title="Restablecer contraseña" />

        <div class="mb-8">
            <h2 class="text-3xl font-bold text-slate-800">Restablecer</h2>
            <p class="text-slate-500 mt-1">Introduce tus nuevos datos de acceso</p>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <InputLabel for="email" value="Confirmar Email" class="ml-1 mb-1 text-slate-700 font-semibold" />
                <TextInput
                    id="email"
                    type="email"
                    v-model="form.email"
                    required
                    autocomplete="username"
                    class="w-full !rounded-xl !border-slate-200 !bg-slate-50 focus:!ring-blue-500/20"
                    placeholder="tu@email.com"
                />
                <InputError :message="form.errors.email" class="mt-1" />
            </div>

            <div>
                <InputLabel for="password" value="Nueva contraseña" class="ml-1 mb-1 text-slate-700 font-semibold" />
                <TextInput
                    id="password"
                    type="password"
                    v-model="form.password"
                    required
                    autofocus
                    autocomplete="new-password"
                    class="w-full !rounded-xl !border-slate-200 focus:!ring-blue-500/20"
                    placeholder="••••••••"
                />
                <InputError :message="form.errors.password" class="mt-1" />
            </div>

            <div>
                <InputLabel for="password_confirmation" value="Repetir contraseña" class="ml-1 mb-1 text-slate-700 font-semibold" />
                <TextInput
                    id="password_confirmation"
                    type="password"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                    class="w-full !rounded-xl !border-slate-200 focus:!ring-blue-500/20"
                    placeholder="••••••••"
                />
                <InputError :message="form.errors.password_confirmation" class="mt-1" />
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full bg-red-500 hover:bg-red-600 text-white font-bold py-4 rounded-2xl shadow-lg shadow-red-500/30 transition-all active:scale-[0.98] disabled:opacity-50 mt-4"
            >
                Actualizar contraseña
            </button>

            <div class="pt-4 text-center border-t border-slate-50 mt-6">
                <Link :href="route('login')" class="text-sm font-bold text-blue-600 hover:text-blue-700 transition-colors">
                    ← Volver al inicio de sesión
                </Link>
            </div>
        </form>
    </AuthLayout>
</template>
