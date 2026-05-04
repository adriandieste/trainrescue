<script setup>
import GeneralLayout from '@/Layouts/GeneralLayout.vue';
import SocorristasClub from '@/Components/SocorristasClub.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
const props = defineProps({
    entrenamientos: {
        type: Array,
        default: () => [],
    },
    clubmates: {
        type: Array,
        default: () => [],
    },
    pendingInvitations: {
        type: Array,
        default: () => [],
    },
});
const page = usePage();
const user = computed(() => page.props.auth.user);
const flash = computed(() => page.props.flash ?? {});
const currentClub = computed(() => user.value?.club ?? null);
const currentClubLogoUrl = computed(() => currentClub.value?.logo_path ? `/storage/${currentClub.value.logo_path}` : null);
function acceptInvitation(invitationId) {
    router.post(route('club-invitations.accept', invitationId), {}, { preserveScroll: true });
}
function rejectInvitation(invitationId) {
    router.post(route('club-invitations.reject', invitationId), {}, { preserveScroll: true });
}
function formatDate(dateStr) {
    if (!dateStr) return '';
    const [y, m, d] = dateStr.split('-');
    return `${d}/${m}/${y}`;
}
const upcomingWorkouts = computed(() => {
    const today = new Date().toISOString().split('T')[0];
    return props.entrenamientos.filter(e => e.workout_date >= today);
});
const pastWorkouts = computed(() => {
    const today = new Date().toISOString().split('T')[0];
    return props.entrenamientos.filter(e => e.workout_date < today);
});
</script>
<template>
    <Head title="Dashboard Socorrista" />
    <GeneralLayout>
        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <!-- Flash messages -->
                <div
                    v-if="flash.success"
                    class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800 shadow-sm"
                >
                    {{ flash.success }}
                </div>
                <div
                    v-if="flash.error"
                    class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800 shadow-sm"
                >
                    {{ flash.error }}
                </div>
                <!-- Invitaciones pendientes -->
                <div v-if="pendingInvitations.length > 0" class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-gray-900">Invitaciones a clubs</h2>
                        <p class="mt-1 text-sm text-gray-600">Tienes invitaciones pendientes de responder.</p>
                    </div>
                    <div class="divide-y divide-gray-100">
                        <div
                            v-for="invitation in pendingInvitations"
                            :key="invitation.id"
                            class="flex items-center justify-between gap-4 px-6 py-4"
                        >
                            <div class="flex items-center gap-3">
                                <div v-if="invitation.club.logo_url" class="h-10 w-10 shrink-0 overflow-hidden rounded-lg border border-gray-200">
                                    <img :src="invitation.club.logo_url" :alt="invitation.club.name" class="h-full w-full object-cover">
                                </div>
                                <div v-else class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-100 text-blue-700 font-bold text-sm">
                                    {{ invitation.club.name.charAt(0).toUpperCase() }}
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">{{ invitation.club.name }}</p>
                                    <p class="text-xs text-gray-500">Invitado por {{ invitation.trainer.name }} · {{ invitation.created_at }}</p>
                                </div>
                            </div>
                            <div class="flex gap-2 shrink-0">
                                <button
                                    type="button"
                                    class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700"
                                    @click="acceptInvitation(invitation.id)"
                                >
                                    Aceptar
                                </button>
                                <button
                                    type="button"
                                    class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                    @click="rejectInvitation(invitation.id)"
                                >
                                    Rechazar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Info del club -->
                <div v-if="currentClub" class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="flex flex-col gap-4 p-6 md:flex-row md:items-start">
                        <div v-if="currentClubLogoUrl" class="h-20 w-20 shrink-0 overflow-hidden rounded-xl border border-gray-200 bg-white">
                            <img :src="currentClubLogoUrl" :alt="currentClub.name" class="h-full w-full object-cover">
                        </div>
                        <div>
                            <p class="text-sm font-medium uppercase tracking-wide text-blue-600">Tu club deportivo</p>
                            <h3 class="mt-1 text-xl font-semibold text-gray-900">{{ currentClub.name }}</h3>
                            <p class="mt-2 text-sm text-gray-600">
                                {{ currentClub.description || 'Tu entrenador todavía no ha añadido una descripción del club.' }}
                            </p>
                        </div>
                    </div>
                </div>
                <!-- Entrenamientos programados -->
                <div v-if="currentClub" class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-gray-900">Próximos entrenamientos</h2>
                        <p class="mt-1 text-sm text-gray-600">Sesiones programadas por tu entrenador para el club.</p>
                    </div>
                    <div v-if="upcomingWorkouts.length === 0" class="p-6 text-sm text-gray-500">
                        No hay entrenamientos programados próximamente.
                    </div>
                    <div v-else class="divide-y divide-gray-100">
                        <div
                            v-for="workout in upcomingWorkouts"
                            :key="workout.id"
                            class="p-5"
                        >
                            <div class="mb-3 flex items-center justify-between gap-2">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-900">{{ workout.title }}</h3>
                                    <p class="mt-0.5 text-xs font-medium text-blue-600">
                                        📅 {{ formatDate(workout.workout_date) }}
                                    </p>
                                </div>
                                <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700">
                                    {{ workout.exercises.length }} {{ workout.exercises.length === 1 ? 'ejercicio' : 'ejercicios' }}
                                </span>
                            </div>
                            <div v-if="workout.exercises.length > 0" class="space-y-2">
                                <div
                                    v-for="(exercise, index) in workout.exercises"
                                    :key="index"
                                    class="flex items-center justify-between gap-3 rounded-lg bg-gray-50 px-3 py-2"
                                >
                                    <span class="text-sm text-gray-800 font-medium">{{ index + 1 }}. {{ exercise.name }}</span>
                                    <span class="shrink-0 rounded-full bg-slate-800 px-3 py-1 text-xs font-semibold text-white tabular-nums">
                                        {{ exercise.load_label }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Entrenamientos pasados (colapsable) -->
                <div v-if="currentClub && pastWorkouts.length > 0" class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <details>
                        <summary class="cursor-pointer border-b border-gray-200 p-6">
                            <span class="text-base font-semibold text-gray-700">Historial de entrenamientos</span>
                            <span class="ml-2 text-sm text-gray-400">({{ pastWorkouts.length }})</span>
                        </summary>
                        <div class="divide-y divide-gray-100">
                            <div
                                v-for="workout in pastWorkouts"
                                :key="workout.id"
                                class="p-5 opacity-75"
                            >
                                <div class="mb-3 flex items-center justify-between gap-2">
                                    <div>
                                        <h3 class="text-sm font-semibold text-gray-900">{{ workout.title }}</h3>
                                        <p class="mt-0.5 text-xs text-gray-500">{{ formatDate(workout.workout_date) }}</p>
                                    </div>
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600">
                                        {{ workout.exercises.length }} ejercicios
                                    </span>
                                </div>
                                <div v-if="workout.exercises.length > 0" class="space-y-1.5">
                                    <div
                                        v-for="(exercise, index) in workout.exercises"
                                        :key="index"
                                        class="flex items-center justify-between gap-3 rounded-lg bg-gray-50 px-3 py-2"
                                    >
                                        <span class="text-sm text-gray-700">{{ index + 1 }}. {{ exercise.name }}</span>
                                        <span class="shrink-0 rounded-full bg-gray-600 px-3 py-1 text-xs font-semibold text-white tabular-nums">
                                            {{ exercise.load_label }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </details>
                </div>
                <!-- Compañeros de club -->
                <div v-if="currentClub" class="overflow-hidden bg-white shadow-sm sm:rounded-lg p-6">
                    <SocorristasClub :clubmates="clubmates" :club-name="currentClub.name" />
                </div>
            </div>
        </div>
    </GeneralLayout>
</template>
