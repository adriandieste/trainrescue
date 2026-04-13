<script setup>
import { computed } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

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
    <div class="min-h-screen flex flex-col justify-center items-center bg-gradient-to-b from-blue-400 to-blue-600 py-20">
        <div class="flex flex-col items-center mb-6">
            <img
                src="/imagenes/trainrescue-logo-horizontal.png"
                alt="Logo de Train & Rescue"
                class="w-auto h-12 mb-2 object-contain"
            />
            <h1 class="text-3xl font-extrabold text-white">Train & Rescue</h1>
            <p class="text-white mt-1">Salvamento y Socorrismo</p>
        </div>

        <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-8 mx-2">
            <h2 class="text-xl font-bold text-center mb-1">Verificar correo</h2>
            <p class="text-center text-neutral-500 mb-6">
                Confirma tu cuenta para continuar
            </p>

            <div
                class="mb-4 p-4 bg-green-50 rounded-lg border border-green-200 text-sm text-green-700"
                v-if="verificationSuccess"
            >
                {{ verificationSuccess }}
            </div>

            <div
                class="mb-4 p-4 bg-green-50 rounded-lg border border-green-200 text-sm text-green-700"
                v-if="verificationLinkSent"
            >
                Te hemos enviado un nuevo enlace de verificación.
            </div>

            <div class="mb-6 p-4 rounded-lg border" :class="isVerified ? 'bg-green-50 border-green-200' : 'bg-blue-50 border-blue-200'">
                <p class="text-sm text-gray-700" v-if="!isVerified">
                    ¡Gracias por registrarte! Antes de comenzar, verifica tu dirección de correo haciendo clic en el enlace que te enviamos. Si no lo has recibido, puedes reenviarlo.
                </p>
                <p class="text-sm text-gray-700" v-else>
                    Tu correo ya está verificado. Ya puedes acceder a todas las funcionalidades de la app.
                </p>
            </div>

            <form @submit.prevent="submit" v-if="!isVerified">
                <button
                    type="submit"
                    class="w-full bg-blue-500 text-white font-bold py-3 rounded-xl shadow-md transition-all hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="form.processing"
                >
                    Reenviar correo de verificación
                </button>
            </form>

            <div class="mt-4 text-center">
                <Link
                    v-if="isVerified"
                    :href="route('dashboard')"
                    class="text-blue-500 font-semibold hover:underline text-sm"
                >
                    Ir al dashboard
                </Link>
                <Link
                    v-else
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="text-blue-500 font-semibold hover:underline text-sm"
                >
                    Cerrar sesión
                </Link>
            </div>
        </div>
    </div>
</template>
