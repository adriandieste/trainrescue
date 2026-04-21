<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
    },
    isVerified: {
        type: Boolean,
        default: false,
    },
});

const form = useForm({});
const page = usePage();

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);

const verificationSuccess = computed(() => page.props.flash?.success);
</script>

<template>
    <AuthLayout title="Casi listo..." subtitle="Verifica tu identidad para empezar a entrenar">
        <Head title="Verificar correo" />

        <div class="mb-6">
            <h2 class="text-3xl font-bold text-slate-800">Verificar correo</h2>
            <p class="text-slate-500 mt-1">Confirma tu cuenta para continuar</p>
        </div>

        <div class="space-y-4 mb-6">
            <div
                v-if="verificationSuccess"
                class="p-4 bg-green-50 border border-green-200 text-sm text-green-700 rounded-2xl flex items-center gap-3"
            >
                <span class="text-lg">✅</span> {{ verificationSuccess }}
            </div>

            <div
                v-if="verificationLinkSent"
                class="p-4 bg-blue-50 border border-blue-200 text-sm text-blue-700 rounded-2xl flex items-center gap-3"
            >
                <span class="text-lg">✉️</span> Te hemos enviado un nuevo enlace de verificación.
            </div>
        </div>

        <div
            class="mb-8 p-5 rounded-[2rem] border transition-all"
            :class="isVerified ? 'bg-green-50/50 border-green-100' : 'bg-slate-50 border-slate-100'"
        >
            <div v-if="!isVerified" class="flex flex-col gap-3">
                <div class="text-sm leading-relaxed text-slate-600">
                    <strong class="text-slate-800 block mb-1">¡Gracias por registrarte!</strong>
                    Antes de comenzar, verifica tu dirección de correo haciendo clic en el enlace que te enviamos. Si no lo has recibido, puedes solicitar otro.
                </div>
            </div>
            <div v-else class="flex flex-col gap-2 items-center text-center py-2">
                <div class="h-12 w-12 bg-green-100 text-green-600 rounded-full flex items-center justify-center text-xl mb-2">
                    ✓
                </div>
                <p class="text-slate-700 font-medium">
                    Tu correo ya está verificado. ¡Ya puedes acceder a todas las funcionalidades!
                </p>
            </div>
        </div>

        <div class="space-y-4">
            <form @submit.prevent="submit" v-if="!isVerified">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-2xl shadow-lg shadow-blue-500/25 transition-all active:scale-[0.98] disabled:opacity-50"
                >
                    Reenviar correo de verificación
                </button>
            </form>

            <div class="flex flex-col items-center gap-4">
                <Link
                    v-if="isVerified"
                    :href="route('dashboard')"
                    class="w-full text-center bg-red-500 hover:bg-red-600 text-white font-bold py-4 rounded-2xl shadow-lg shadow-red-500/25 transition-all"
                >
                    Ir al panel principal
                </Link>

                <Link
                    v-else
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="text-slate-400 font-semibold hover:text-red-500 transition-colors text-sm underline decoration-slate-200 underline-offset-4"
                >
                    Cerrar sesión
                </Link>
            </div>
        </div>
    </AuthLayout>
</template>
