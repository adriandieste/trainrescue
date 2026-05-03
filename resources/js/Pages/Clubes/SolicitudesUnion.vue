<script setup>
import GeneralLayout from '@/Layouts/GeneralLayout.vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineProps({
    club: Object,
    requests: Array,
});

const flash = computed(() => usePage().props.flash ?? {});
const idProcesando = ref(null);
const accionProcesando = ref(null);

function aceptarSolicitud(id) {
    idProcesando.value = id;
    accionProcesando.value = 'accept';

    router.post(route('clubs.join-requests.accept', { joinRequest: id }), {}, {
        onFinish: () => {
            idProcesando.value = null;
            accionProcesando.value = null;
        },
    });
}

function rechazarSolicitud(id) {
    idProcesando.value = id;
    accionProcesando.value = 'reject';

    router.post(route('clubs.join-requests.reject', { joinRequest: id }), {}, {
        onFinish: () => {
            idProcesando.value = null;
            accionProcesando.value = null;
        },
    });
}
</script>

<template>
    <Head :title="`Solicitudes de union - ${club.name}`" />

    <GeneralLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Solicitudes de union al club
                    <span class="text-blue-600">{{ club.name }}</span>
                </h2>
                <span
                    v-if="requests.length > 0"
                    class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-800"
                >
                    {{ requests.length }} pendiente(s)
                </span>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl space-y-4 sm:px-6 lg:px-8">
                <div
                    v-if="flash.success"
                    class="flex items-start gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800"
                >
                    <p class="text-sm font-medium">{{ flash.success }}</p>
                </div>

                <div
                    v-if="flash.error"
                    class="flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800"
                >
                    <p class="text-sm font-medium">{{ flash.error }}</p>
                </div>

                <div v-if="requests.length === 0" class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-10 text-center text-gray-500">
                        <p class="text-base font-medium">No hay solicitudes pendientes</p>
                        <p class="mt-1 text-sm">Cuando alguien solicite unirse a tu club, aparecera aqui.</p>
                    </div>
                </div>

                <div
                    v-for="request in requests"
                    :key="request.id"
                    class="overflow-hidden bg-white shadow-sm sm:rounded-lg"
                >
                    <div class="p-6">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-start gap-4">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-full bg-blue-100">
                                    <img
                                        v-if="request.user.avatar"
                                        :src="request.user.avatar"
                                        :alt="request.user.name"
                                        class="h-full w-full object-cover"
                                    >
                                    <span v-else class="text-lg font-bold text-blue-600">
                                        {{ request.user.name.charAt(0).toUpperCase() }}
                                    </span>
                                </div>

                                <div>
                                    <p class="font-semibold text-gray-900">{{ request.user.name }}</p>
                                    <p class="text-sm text-gray-500">{{ request.user.email }}</p>
                                    <p class="mt-0.5 text-xs text-gray-400">Solicitado {{ request.created_at }}</p>
                                    <p
                                        v-if="request.message"
                                        class="mt-2 rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm italic text-gray-700"
                                    >
                                        "{{ request.message }}"
                                    </p>
                                </div>
                            </div>

                            <div class="flex shrink-0 items-center gap-2">
                                <button
                                    :disabled="idProcesando === request.id"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 disabled:opacity-50"
                                    @click="rechazarSolicitud(request.id)"
                                >
                                    Rechazar
                                </button>

                                <button
                                    :disabled="idProcesando === request.id"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-green-700 disabled:opacity-50"
                                    @click="aceptarSolicitud(request.id)"
                                >
                                    <span v-if="idProcesando === request.id && accionProcesando === 'accept'">Procesando...</span>
                                    <span v-else>Aceptar</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </GeneralLayout>
</template>

