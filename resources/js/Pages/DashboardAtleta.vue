<script setup>
import GeneralLayout from '@/Layouts/GeneralLayout.vue';
import SocorristasClub from '@/Components/SocorristasClub.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
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
function acceptInvitation(invitationId) {
    router.post(route('club-invitations.accept', invitationId), {}, { preserveScroll: true });
}
function rejectInvitation(invitationId) {
    router.post(route('club-invitations.reject', invitationId), {}, { preserveScroll: true });
}
function dismissNotification(id) {
    router.patch(route('notifications.mark-read', id), {}, { preserveScroll: true });
}
function dismissAllNotifications() {
    router.patch(route('notifications.mark-all-read'), {}, { preserveScroll: true });
}
function markWorkoutCompleted(workoutId) {
    router.patch(route('workouts.complete', workoutId), {}, { preserveScroll: true });
}
function formatDate(dateStr) {
    if (!dateStr) return '';
    const [y, m, d] = dateStr.split('-');
    return `${d}/${m}/${y}`;
}
function formatDateKey(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}
function startOfWeek(date) {
    const base = new Date(date.getFullYear(), date.getMonth(), date.getDate());
    const day = base.getDay();
    const diffToMonday = day === 0 ? -6 : 1 - day;
    base.setDate(base.getDate() + diffToMonday);
    return base;
}
const upcomingWorkouts = computed(() => {
    const today = new Date().toISOString().split('T')[0];
    return props.entrenamientos.filter(e => e.workout_date >= today);
});
const pastWorkouts = computed(() => {
    const today = new Date().toISOString().split('T')[0];
    return props.entrenamientos.filter(e => e.workout_date < today);
});
const todayWorkout = computed(() => props.entrenamientoHoy ?? null);

function statusLabel(status) {
    if (status === 'completed') return 'Completado';
    if (status === 'pending') return 'Pendiente';
    return 'Futuro';
}

let dashboardReloadInterval = null;
onMounted(() => {
    if (typeof window === 'undefined') {
        return;
    }

    dashboardReloadInterval = window.setInterval(() => {
        router.reload({
            only: ['entrenamientos', 'entrenamientoHoy', 'notificaciones'],
            preserveScroll: true,
            preserveState: true,
        });
    }, 60000);
});

onUnmounted(() => {
    if (dashboardReloadInterval) {
        clearInterval(dashboardReloadInterval);
        dashboardReloadInterval = null;
    }
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
                <!-- Notificaciones de entrenamientos asignados -->
                <div v-if="notificaciones.length > 0" class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="border-b border-orange-100 bg-orange-50 p-4">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-orange-500 text-xs font-bold text-white">
                                    {{ notificaciones.length }}
                                </span>
                                <h2 class="text-sm font-semibold text-orange-900">Nuevos entrenamientos asignados</h2>
                            </div>
                            <button
                                type="button"
                                class="text-xs font-medium text-orange-700 underline hover:text-orange-900"
                                @click="dismissAllNotifications"
                            >
                                Marcar todas como leídas
                            </button>
                        </div>
                    </div>
                    <div class="divide-y divide-gray-100">
                        <div
                            v-for="notif in notificaciones"
                            :key="notif.id"
                            class="flex items-center justify-between gap-4 px-5 py-3"
                        >
                            <div class="flex items-start gap-3">
                                <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-orange-100 text-orange-600">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </span>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">{{ notif.workout_title }}</p>
                                    <p class="text-xs text-gray-500">
                                        <span v-if="notif.workout_date_formatted">📅 {{ notif.workout_date_formatted }} · </span>
                                        {{ notif.created_at }}
                                    </p>
                                </div>
                            </div>
                            <button
                                type="button"
                                class="shrink-0 rounded-md border border-gray-200 bg-white px-2 py-1 text-xs font-medium text-gray-600 hover:bg-gray-50"
                                @click="dismissNotification(notif.id)"
                                title="Marcar como leída"
                            >
                                ✓ Leído
                            </button>
                        </div>
                    </div>
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

                <!-- Entrenamiento de hoy -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-gray-900">Entrenamiento de hoy</h2>
                        <p class="mt-1 text-sm text-gray-600">Acceso rápido a la sesión asignada para la fecha actual.</p>
                    </div>

                    <div v-if="todayWorkout" class="p-6">
                        <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Hoy · {{ formatDate(todayWorkout.workout_date) }}</p>
                                    <h3 class="mt-1 text-base font-semibold text-gray-900">{{ todayWorkout.title }}</h3>
                                    <p class="mt-1 text-sm text-gray-600">
                                        {{ todayWorkout.exercises.length }} {{ todayWorkout.exercises.length === 1 ? 'ejercicio' : 'ejercicios' }} programados
                                    </p>
                                </div>
                                <a
                                    :href="`#workout-${todayWorkout.id}`"
                                    class="inline-flex items-center rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-700"
                                >
                                    Ver detalle completo
                                </a>
                            </div>
                        </div>
                    </div>

                    <div v-else class="p-6">
                        <p class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-4 py-3 text-sm font-medium text-gray-700">
                            Día de descanso
                        </p>
                    </div>
                </div>

                <!-- Entrenamientos programados -->
                <div id="workouts-detail" v-if="currentClub" class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
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
                            :id="`workout-${workout.id}`"
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
