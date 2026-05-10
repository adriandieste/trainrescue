<script setup>
import GeneralLayout from '@/Layouts/GeneralLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    entrenamientos: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const flash = computed(() => page.props.flash ?? {});

const expandedWorkoutId = ref(null);

function formatDate(dateStr) {
    if (!dateStr) return '';
    const [y, m, d] = dateStr.split('-');
    return `${d}/${m}/${y}`;
}

function statusLabel(status) {
    if (status === 'completed') return 'Completado';
    if (status === 'pending') return 'Pendiente';
    return 'Futuro';
}

function statusColor(status) {
    if (status === 'completed') return 'bg-emerald-100 text-emerald-700';
    if (status === 'pending') return 'bg-rose-100 text-rose-700';
    return 'bg-blue-100 text-blue-700';
}

function markWorkoutCompleted(workoutId) {
    router.patch(route('workouts.complete', workoutId), {}, { preserveScroll: true });
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
    <Head title="Entrenamientos" />
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

                <!-- Próximos entrenamientos -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-gray-900">Próximos entrenamientos</h2>
                        <p class="mt-1 text-sm text-gray-600">Sesiones programadas por tu entrenador. Expande cada sesión para ver el detalle de todos los ejercicios, series, metros y descansos.</p>
                    </div>

                    <div v-if="upcomingWorkouts.length === 0" class="p-6 text-center text-sm text-gray-500">
                        <p>No hay entrenamientos programados próximamente.</p>
                    </div>

                    <div v-else class="divide-y divide-gray-100">
                        <div
                            v-for="workout in upcomingWorkouts"
                            :key="workout.id"
                            class="bg-white"
                        >
                            <!-- Header del entrenamiento -->
                            <div
                                role="button"
                                tabindex="0"
                                class="flex cursor-pointer items-center justify-between gap-4 p-5 hover:bg-gray-50 transition"
                                @click="expandedWorkoutId = expandedWorkoutId === workout.id ? null : workout.id"
                                @keydown.enter="expandedWorkoutId = expandedWorkoutId === workout.id ? null : workout.id"
                                @keydown.space="expandedWorkoutId = expandedWorkoutId === workout.id ? null : workout.id"
                            >
                                <div class="flex-1">
                                    <h3 class="text-sm font-semibold text-gray-900">{{ workout.title }}</h3>
                                    <div class="mt-2 flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3">
                                        <p class="text-xs font-medium text-blue-600">
                                            📅 {{ formatDate(workout.workout_date) }}
                                        </p>
                                        <span :class="`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ${statusColor(workout.completion_status)}`">
                                            {{ statusLabel(workout.completion_status) }}
                                        </span>
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">
                                            {{ workout.exercises.length }} {{ workout.exercises.length === 1 ? 'ejercicio' : 'ejercicios' }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Indicador de expansión -->
                                <div class="flex shrink-0 items-center gap-4">
                                    <button
                                        v-if="workout.completion_status !== 'completed' && workout.workout_date <= new Date().toISOString().split('T')[0]"
                                        type="button"
                                        class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-emerald-700"
                                        @click.stop="markWorkoutCompleted(workout.id)"
                                    >
                                        Marcar realizado
                                    </button>
                                    <svg
                                        class="h-5 w-5 shrink-0 transition-transform"
                                        :class="expandedWorkoutId === workout.id ? 'rotate-180' : ''"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                    </svg>
                                </div>
                            </div>

                            <!-- Detalle expandible -->
                            <transition
                                enter-active-class="transition duration-200 ease-out"
                                enter-from-class="opacity-0 -translate-y-2"
                                enter-to-class="opacity-100 translate-y-0"
                                leave-active-class="transition duration-150 ease-in"
                                leave-from-class="opacity-100 translate-y-0"
                                leave-to-class="opacity-0 -translate-y-2"
                            >
                                <div v-if="expandedWorkoutId === workout.id" class="border-t border-gray-100 bg-gray-50 p-5">
                                    <div class="space-y-3">
                                        <div
                                            v-for="(exercise, index) in workout.exercises"
                                            :key="`${workout.id}-${index}`"
                                            class="rounded-lg border border-gray-200 bg-white p-4"
                                        >
                                            <div class="mb-2 flex items-start justify-between gap-3">
                                                <div>
                                                    <h4 class="text-sm font-semibold text-gray-900">
                                                        {{ index + 1 }}. {{ exercise.name }}
                                                    </h4>
                                                </div>
                                            </div>

                                            <!-- Parámetros técnicos -->
                                            <div class="grid gap-2 sm:grid-cols-3">
                                                <div class="rounded bg-blue-50 px-3 py-2">
                                                    <p class="text-xs font-medium text-blue-700">Series</p>
                                                    <p class="text-base font-semibold text-blue-900">{{ exercise.sets }}</p>
                                                </div>

                                                <div v-if="exercise.meters" class="rounded bg-purple-50 px-3 py-2">
                                                    <p class="text-xs font-medium text-purple-700">Metros</p>
                                                    <p class="text-base font-semibold text-purple-900">{{ exercise.meters }}m</p>
                                                </div>

                                                <div v-if="exercise.rest_seconds" class="rounded bg-orange-50 px-3 py-2">
                                                    <p class="text-xs font-medium text-orange-700">Descanso</p>
                                                    <p class="text-base font-semibold text-orange-900">{{ exercise.rest_seconds }}s</p>
                                                </div>
                                            </div>

                                            <!-- Label de carga -->
                                            <div class="mt-3 rounded border border-gray-200 bg-gray-50 px-3 py-2">
                                                <p class="text-xs text-gray-600">Resumen de carga</p>
                                                <p class="text-sm font-semibold text-gray-900">{{ exercise.load_label }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </transition>
                        </div>
                    </div>
                </div>

                <!-- Entrenamientos pasados (colapsable) -->
                <div v-if="pastWorkouts.length > 0" class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <details>
                        <summary class="cursor-pointer border-b border-gray-200 p-6 hover:bg-gray-50 transition">
                            <span class="text-base font-semibold text-gray-700">Historial de entrenamientos</span>
                            <span class="ml-2 text-sm text-gray-400">({{ pastWorkouts.length }})</span>
                        </summary>

                        <div class="divide-y divide-gray-100">
                            <div
                                v-for="workout in pastWorkouts"
                                :key="workout.id"
                                class="bg-white opacity-75"
                            >
                                <!-- Header del entrenamiento -->
                                <div
                                    role="button"
                                    tabindex="0"
                                    class="flex cursor-pointer items-center justify-between gap-4 p-5 hover:bg-gray-50 transition"
                                    @click="expandedWorkoutId = expandedWorkoutId === `past-${workout.id}` ? null : `past-${workout.id}`"
                                    @keydown.enter="expandedWorkoutId = expandedWorkoutId === `past-${workout.id}` ? null : `past-${workout.id}`"
                                    @keydown.space="expandedWorkoutId = expandedWorkoutId === `past-${workout.id}` ? null : `past-${workout.id}`"
                                >
                                    <div class="flex-1">
                                        <h3 class="text-sm font-semibold text-gray-900">{{ workout.title }}</h3>
                                        <div class="mt-2 flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3">
                                            <p class="text-xs font-medium text-gray-500">
                                                📅 {{ formatDate(workout.workout_date) }}
                                            </p>
                                            <span :class="`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ${statusColor(workout.completion_status)}`">
                                                {{ statusLabel(workout.completion_status) }}
                                            </span>
                                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">
                                                {{ workout.exercises.length }} ejercicios
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Indicador de expansión -->
                                    <svg
                                        class="h-5 w-5 shrink-0 transition-transform"
                                        :class="expandedWorkoutId === `past-${workout.id}` ? 'rotate-180' : ''"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                    </svg>
                                </div>

                                <!-- Detalle expandible -->
                                <transition
                                    enter-active-class="transition duration-200 ease-out"
                                    enter-from-class="opacity-0 -translate-y-2"
                                    enter-to-class="opacity-100 translate-y-0"
                                    leave-active-class="transition duration-150 ease-in"
                                    leave-from-class="opacity-100 translate-y-0"
                                    leave-to-class="opacity-0 -translate-y-2"
                                >
                                    <div v-if="expandedWorkoutId === `past-${workout.id}`" class="border-t border-gray-100 bg-gray-100/50 p-5">
                                        <div class="space-y-3">
                                            <div
                                                v-for="(exercise, index) in workout.exercises"
                                                :key="`past-${workout.id}-${index}`"
                                                class="rounded-lg border border-gray-300 bg-white p-4"
                                            >
                                                <div class="mb-2 flex items-start justify-between gap-3">
                                                    <div>
                                                        <h4 class="text-sm font-semibold text-gray-900">
                                                            {{ index + 1 }}. {{ exercise.name }}
                                                        </h4>
                                                    </div>
                                                </div>

                                                <!-- Parámetros técnicos -->
                                                <div class="grid gap-2 sm:grid-cols-3">
                                                    <div class="rounded bg-blue-50 px-3 py-2">
                                                        <p class="text-xs font-medium text-blue-700">Series</p>
                                                        <p class="text-base font-semibold text-blue-900">{{ exercise.sets }}</p>
                                                    </div>

                                                    <div v-if="exercise.meters" class="rounded bg-purple-50 px-3 py-2">
                                                        <p class="text-xs font-medium text-purple-700">Metros</p>
                                                        <p class="text-base font-semibold text-purple-900">{{ exercise.meters }}m</p>
                                                    </div>

                                                    <div v-if="exercise.rest_seconds" class="rounded bg-orange-50 px-3 py-2">
                                                        <p class="text-xs font-medium text-orange-700">Descanso</p>
                                                        <p class="text-base font-semibold text-orange-900">{{ exercise.rest_seconds }}s</p>
                                                    </div>
                                                </div>

                                                <!-- Label de carga -->
                                                <div class="mt-3 rounded border border-gray-300 bg-gray-100 px-3 py-2">
                                                    <p class="text-xs text-gray-700">Resumen de carga</p>
                                                    <p class="text-sm font-semibold text-gray-900">{{ exercise.load_label }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </transition>
                            </div>
                        </div>
                    </details>
                </div>

                <!-- Vacío -->
                <div v-if="entrenamientos.length === 0" class="overflow-hidden bg-white shadow-sm sm:rounded-lg p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900">No tienes entrenamientos asignados</h3>
                    <p class="mt-1 text-sm text-gray-600">Tu entrenador aún no ha creado entrenamientos para ti.</p>
                </div>
            </div>
        </div>
    </GeneralLayout>
</template>

