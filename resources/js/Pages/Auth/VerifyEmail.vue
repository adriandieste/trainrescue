<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <div class="min-h-screen flex flex-col justify-center items-center bg-gradient-to-b from-blue-400 to-blue-600 py-20">
        <div class="flex flex-col items-center mb-6">
            <img
                src="../../../imagenes/logoTrain&Rescue.png"
                alt="Train & Rescue"
                class="w-24 h-24 mb-2 rounded-full shadow-md  object-contain"
            />
            <h1 class="text-3xl font-extrabold text-white">Train & Rescue</h1>
            <p class="text-white mt-1">Salvamento y Socorrismo</p>
        </div>
        <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-8 mx-2">
            <h2 class="text-xl font-bold text-center mb-1">Verificar Email</h2>
            <p class="text-center text-neutral-500 mb-6">
                Confirma tu cuenta
            </p>

            <div class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                <p class="text-sm text-gray-700">
                    ¡Gracias por registrarte! Antes de comenzar, por favor verifica tu dirección de correo electrónico
                    haciendo clic en el enlace que te enviamos. Si no recibiste el email, podemos reenviarlo.
                </p>
            </div>

            <div
                class="mb-4 p-4 bg-green-50 rounded-lg border border-green-200 text-sm text-green-700"
                v-if="verificationLinkSent"
            >
                ✓ Se ha enviado un nuevo enlace de verificación a tu email.
            </div>

            <form @submit.prevent="submit">
                <button
                    type="submit"
                    class="w-full bg-blue-500 text-white font-bold py-3 rounded-xl shadow-md transition-all hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="form.processing"
                >
                    Reenviar Email de Verificación
                </button>

                <div class="mt-4 text-center">
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="text-blue-500 font-semibold hover:underline text-sm"
                    >
                        Cerrar Sesión
                    </Link>
                </div>
            </form>
        </div>
    </div>
</template>


