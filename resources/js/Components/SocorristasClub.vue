<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

const page = usePage();
const currentUserId = computed(() => page.props.auth?.user?.id);

// ...existing code...

const props = defineProps({
    clubmates: {
        type: Array,
        default: () => [],
    },
    clubName: {
        type: String,
        default: '',
    },
});

function getInitials(name) {
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((n) => n[0])
        .join('')
        .toUpperCase();
}

// Lógica de abandono del club
const showLeaveModal = ref(false);
const isLeaving = ref(false);

function openLeaveModal() {
    showLeaveModal.value = true;
}

function cancelLeave() {
    showLeaveModal.value = false;
}

function confirmLeave() {
    isLeaving.value = true;
    router.delete(route('clubs.leave'), {
        preserveScroll: false,
        onFinish: () => {
            isLeaving.value = false;
            showLeaveModal.value = false;
        },
    });
}
</script>

<template>
    <div class="w-full">
        <!-- Cabecera con botón Abandonar Club -->
        <div class="mb-4 flex items-start justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Socorristas del club</h2>
                <p v-if="clubmates.length > 0" class="text-sm text-gray-600">
                    {{ clubmates.length }} miembro(s) en <strong>{{ clubName }}</strong>.
                </p>
            </div>
            <button
                type="button"
                @click="openLeaveModal"
                class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H5a3 3 0 01-3-3V7a3 3 0 013-3h5a3 3 0 013 3v1" />
                </svg>
                Salir del club
            </button>
        </div>

        <!-- Tabla de miembros -->
        <div v-if="clubmates.length === 0" class="rounded-lg border border-gray-200 bg-white p-6 text-center">
            <p class="text-gray-600">No tienes compañeros en tu club aún.</p>
        </div>

        <div v-else class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr class="border-b border-gray-200">
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-700">Miembro</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-700">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-700">Rol</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="clubmate in clubmates" :key="clubmate.id" class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div v-if="clubmate.avatar_url" class="h-10 w-10 flex-shrink-0 overflow-hidden rounded-full border border-gray-200">
                                    <img :src="clubmate.avatar_url" :alt="clubmate.name" class="h-full w-full object-cover">
                                </div>
                                <div v-else class="h-10 w-10 flex-shrink-0 rounded-full bg-blue-100 flex items-center justify-center font-semibold text-blue-600 text-sm">
                                    {{ getInitials(clubmate.name) }}
                                </div>
                                <p class="font-semibold text-gray-900">
                                    {{ clubmate.name }}
                                    <span v-if="clubmate.id === currentUserId" class="ml-1 text-xs font-normal text-blue-500">(yo)</span>
                                </p>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-gray-600">{{ clubmate.email }}</td>
                        <td class="px-6 py-4">
                            <span
                                class="inline-block rounded-full px-3 py-1 text-sm font-medium"
                                :class="clubmate.role === 'entrenador' ? 'bg-orange-100 text-orange-700' : 'bg-blue-100 text-blue-700'"
                            >
                                {{ clubmate.role_label }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal de confirmación de abandono -->
        <Teleport to="body">
            <div v-if="showLeaveModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" @click.self="cancelLeave">
                <div class="mx-4 w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H5a3 3 0 01-3-3V7a3 3 0 013-3h5a3 3 0 013 3v1" />
                        </svg>
                    </div>

                    <h3 class="mb-2 text-lg font-bold text-gray-900">¿Abandonar el club?</h3>
                    <p class="mb-6 text-sm text-gray-600">
                        Vas a salir de <strong>{{ clubName }}</strong>. Perderás el acceso a la lista de compañeros y deberás solicitar una nueva invitación para volver a unirte.
                    </p>

                    <div class="flex gap-3">
                        <button
                            type="button"
                            @click="cancelLeave"
                            class="flex-1 rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                        >
                            Cancelar
                        </button>
                        <button
                            type="button"
                            @click="confirmLeave"
                            :disabled="isLeaving"
                            class="flex-1 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700 disabled:opacity-60"
                        >
                            {{ isLeaving ? 'Saliendo...' : 'Sí, abandonar' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>


