<script setup>
import GeneralLayout from '@/Layouts/GeneralLayout.vue';
import SocorristasClub from '@/Components/SocorristasClub.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
const props = defineProps({
    entrenamientos: {
        type: Array,
        default: () => [],
    },
    entrenamientoHoy: {
        type: Object,
        default: null,
    },
    clubmates: {
        type: Array,
        default: () => [],
    },
    pendingInvitations: {
        type: Array,
        default: () => [],
    },
    notificaciones: {
        type: Array,
        default: () => [],
    },
});
const page = usePage();
const user = computed(() => page.props.auth.user);
const flash = computed(() => page.props.flash ?? {});
const currentClub = computed(() => user.value?.club ?? null);
const currentClubLogoUrl = computed(() => currentClub.value?.logo_path ? `/storage/${currentClub.value.logo_path}` : null);

const expandedTodayWorkout = ref(false);

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

function markWorkoutCompleted(workoutId) {
    router.patch(route('workouts.complete', workoutId), {}, { preserveScroll: true });
}

const todayKey = new Date().toISOString().split('T')[0];
const todayWorkout = computed(() => props.entrenamientoHoy ?? null);
</script>
<template>
    <Head title="Dashboard Socorrista" />
    <GeneralLayout>
        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <!-- Flash messages -->
                <div
                    v-if="flash.success"
                    class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800 shadow-sm dark:border-green-900/30 dark:bg-green-900/20 dark:text-green-400"
                >
                    {{ flash.success }}
                </div>
                <div
                    v-if="flash.error"
                    class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800 shadow-sm dark:border-red-900/30 dark:bg-red-900/20 dark:text-red-400"
                >
                    {{ flash.error }}
                </div>
                <!-- Invitaciones pendientes -->
                <div v-if="pendingInvitations.length > 0" class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-zinc-950 dark:border dark:border-zinc-800">
                    <div class="border-b border-gray-200 p-6 dark:border-zinc-800">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Invitaciones a clubs</h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-zinc-400">Tienes invitaciones pendientes de responder.</p>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-zinc-800">
                        <div
                            v-for="invitation in pendingInvitations"
                            :key="invitation.id"
                            class="flex items-center justify-between gap-4 px-6 py-4"
                        >
                            <div class="flex items-center gap-3">
                                <div v-if="invitation.club.logo_url" class="h-10 w-10 shrink-0 overflow-hidden rounded-lg border border-gray-200 dark:border-zinc-700">
                                    <img :src="invitation.club.logo_url" :alt="invitation.club.name" class="h-full w-full object-cover">
                                </div>
                                <div v-else class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-100 text-blue-700 font-bold text-sm dark:bg-blue-900/30 dark:text-blue-400">
                                    {{ invitation.club.name.charAt(0).toUpperCase() }}
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ invitation.club.name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-zinc-500">Invitado por {{ invitation.trainer.name }} · {{ invitation.created_at }}</p>
                                </div>
                            </div>
                            <div class="flex gap-2 shrink-0">
                                <button
                                    type="button"
                                    class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700 dark:hover:bg-blue-500"
                                    @click="acceptInvitation(invitation.id)"
                                >
                                    Aceptar
                                </button>
                                <button
                                    type="button"
                                    class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800"
                                    @click="rejectInvitation(invitation.id)"
                                >
                                    Rechazar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Info del club -->
                <div v-if="currentClub" class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-zinc-950 dark:border dark:border-zinc-800">
                    <div class="flex flex-col gap-4 p-6 md:flex-row md:items-start">
                        <div v-if="currentClubLogoUrl" class="h-20 w-20 shrink-0 overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                            <img :src="currentClubLogoUrl" :alt="currentClub.name" class="h-full w-full object-cover">
                        </div>
                        <div>
                            <p class="text-sm font-medium uppercase tracking-wide text-blue-600 dark:text-blue-400">Tu club deportivo</p>
                            <h3 class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">{{ currentClub.name }}</h3>
                            <p class="mt-2 text-sm text-gray-600 dark:text-zinc-400">
                                {{ currentClub.description || 'Tu entrenador todavía no ha añadido una descripción del club.' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Entrenamiento de hoy -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-zinc-950 dark:border dark:border-zinc-800">
                    <div class="border-b border-gray-200 p-6 dark:border-zinc-800">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Entrenamiento de hoy</h2>
                    </div>

                    <div v-if="todayWorkout" class="p-6">
                        <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-900/30 dark:bg-blue-900/20">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex-1">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-blue-700 dark:text-blue-400">Hoy · {{ formatDate(todayWorkout.workout_date) }}</p>
                                    <h3 class="mt-1 text-base font-semibold text-gray-900 dark:text-white">{{ todayWorkout.title }}</h3>
                                    <p class="mt-1 text-sm text-gray-600 dark:text-zinc-400">
                                        {{ todayWorkout.exercises.length }} {{ todayWorkout.exercises.length === 1 ? 'ejercicio' : 'ejercicios' }} programados
                                    </p>
                                </div>
                                <div class="flex shrink-0 items-center gap-2">
                                    <!-- Ya completado -->
                                    <span
                                        v-if="todayWorkout.completion_status === 'completed'"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-100 px-3 py-2 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Completado
                                    </span>
                                    <!-- Pendiente de marcar -->
                                    <button
                                        v-else
                                        type="button"
                                        class="inline-flex items-center rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-emerald-700 dark:hover:bg-emerald-500"
                                        @click="markWorkoutCompleted(todayWorkout.id)"
                                    >
                                        Marcar realizado
                                    </button>
                                    <a
                                        :href="route('entrenamientos.index')"
                                        class="inline-flex items-center rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-700 dark:hover:bg-blue-500"
                                    >
                                        Ver todos
                                    </a>
                                </div>
                            </div>

                            <!-- Ejercicios de hoy (expandible) -->
                            <div v-if="todayWorkout.exercises.length > 0" class="mt-4 space-y-2">
                                <button
                                    type="button"
                                    class="flex w-full items-center justify-between rounded-lg border border-blue-200 bg-white px-3 py-2 text-xs font-semibold text-blue-700 transition hover:bg-blue-50 dark:border-blue-900/30 dark:bg-zinc-900 dark:text-blue-400 dark:hover:bg-zinc-800/50"
                                    @click="expandedTodayWorkout = !expandedTodayWorkout"
                                >
                                    <span>{{ expandedTodayWorkout ? 'Ocultar' : 'Mostrar' }} ejercicios</span>
                                    <svg
                                        class="h-4 w-4 transition-transform"
                                        :class="expandedTodayWorkout ? 'rotate-180' : ''"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                    </svg>
                                </button>

                                <transition
                                    enter-active-class="transition duration-200 ease-out"
                                    enter-from-class="opacity-0 -translate-y-2"
                                    enter-to-class="opacity-100 translate-y-0"
                                    leave-active-class="transition duration-150 ease-in"
                                    leave-from-class="opacity-100 translate-y-0"
                                    leave-to-class="opacity-0 -translate-y-2"
                                >
                                    <div v-if="expandedTodayWorkout" class="mt-3 space-y-2 rounded-lg border border-blue-200 bg-white p-3 dark:border-blue-900/30 dark:bg-zinc-900">
                                        <div
                                            v-for="(exercise, index) in todayWorkout.exercises"
                                            :key="index"
                                            class="rounded bg-blue-50 p-2 dark:bg-blue-900/20"
                                        >
                                            <p class="text-xs font-semibold text-blue-900 dark:text-blue-400">{{ index + 1 }}. {{ exercise.name }}</p>
                                            <p class="mt-0.5 text-xs text-blue-700 dark:text-blue-500">{{ exercise.load_label }}</p>
                                        </div>
                                    </div>
                                </transition>
                            </div>
                        </div>
                    </div>

                    <div v-else class="p-6">
                        <p class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-4 py-3 text-sm font-medium text-gray-700 dark:border-zinc-700 dark:bg-zinc-900/30 dark:text-zinc-400">
                            Día de descanso
                        </p>
                    </div>
                </div>
                <!-- Compañeros de club -->
                <div v-if="currentClub" class="overflow-hidden bg-white shadow-sm sm:rounded-lg p-6 dark:bg-zinc-950 dark:border dark:border-zinc-800">
                    <SocorristasClub :clubmates="clubmates" :club-name="currentClub.name" />
                </div>
            </div>
        </div>
    </GeneralLayout>
</template>
