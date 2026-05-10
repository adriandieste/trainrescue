<script setup>
import GeneralLayout from '@/Layouts/GeneralLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    entrenamientos: {
        type: Array,
        default: () => [],
    },
});

const calendarWeekdayLabels = ['Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab', 'Dom'];

const calendarCursor = ref(new Date());
const selectedCalendarDate = ref(formatDateKey(new Date()));

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

const todayKey = computed(() => formatDateKey(new Date()));

const calendarEventsByDate = computed(() => {
    const map = new Map();
    for (const workout of props.entrenamientos) {
        if (!workout.workout_date) {
            continue;
        }
        if (!map.has(workout.workout_date)) {
            map.set(workout.workout_date, []);
        }
        map.get(workout.workout_date).push(workout);
    }
    return map;
});

const calendarDays = computed(() => {
    const year = calendarCursor.value.getFullYear();
    const month = calendarCursor.value.getMonth();
    const monthStart = new Date(year, month, 1);
    const monthEnd = new Date(year, month + 1, 0);
    const gridStart = startOfWeek(monthStart);
    const totalDays = Math.ceil((monthEnd.getDate() + (monthStart.getDay() === 0 ? 6 : monthStart.getDay() - 1)) / 7) * 7;

    return Array.from({ length: totalDays }, (_, index) => {
        const day = new Date(gridStart);
        day.setDate(gridStart.getDate() + index);
        const dateKey = formatDateKey(day);

        return {
            key: dateKey,
            day,
            inCurrentMonth: day.getMonth() === month,
            workouts: calendarEventsByDate.value.get(dateKey) ?? [],
        };
    });
});

const selectedDayWorkouts = computed(() => calendarEventsByDate.value.get(selectedCalendarDate.value) ?? []);
const calendarTitle = computed(() => calendarCursor.value.toLocaleDateString('es-ES', { month: 'long', year: 'numeric' }));

function shiftCalendarMonth(step) {
    const next = new Date(calendarCursor.value);
    next.setMonth(next.getMonth() + step);
    calendarCursor.value = next;
}

function goToCurrentMonth() {
    const today = new Date();
    calendarCursor.value = new Date(today.getFullYear(), today.getMonth(), 1);
}

function selectCalendarDay(dayKey) {
    selectedCalendarDate.value = dayKey;
}

function statusLabel(status) {
    if (status === 'completed') return 'Completado';
    if (status === 'pending') return 'Pendiente';
    return 'Futuro';
}

function markWorkoutCompleted(workoutId) {
    router.patch(route('workouts.complete', workoutId), {}, { preserveScroll: true });
}

watch(() => props.entrenamientos, () => {
    if (!calendarEventsByDate.value.has(selectedCalendarDate.value)) {
        selectedCalendarDate.value = todayKey.value;
    }
});

let calendarReloadInterval = null;
onMounted(() => {
    if (typeof window === 'undefined') {
        return;
    }

    calendarReloadInterval = window.setInterval(() => {
        router.reload({
            only: ['entrenamientos'],
            preserveScroll: true,
            preserveState: true,
        });
    }, 60000);
});

onUnmounted(() => {
    if (calendarReloadInterval) {
        clearInterval(calendarReloadInterval);
        calendarReloadInterval = null;
    }
});
</script>

<template>
    <Head title="Calendario de Entrenamientos" />
    <GeneralLayout>
        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <!-- Calendario de planificación e historial -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-200 p-6">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h1 class="text-2xl font-semibold text-gray-900">Calendario de planificación</h1>
                                <p class="mt-1 text-sm text-gray-600">Revisa histórico y sesiones futuras con estados de cumplimiento.</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50"
                                    @click="shiftCalendarMonth(-1)"
                                >
                                    Mes anterior
                                </button>
                                <button
                                    type="button"
                                    class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50"
                                    @click="goToCurrentMonth"
                                >
                                    Hoy
                                </button>
                                <button
                                    type="button"
                                    class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50"
                                    @click="shiftCalendarMonth(1)"
                                >
                                    Mes siguiente
                                </button>
                            </div>
                        </div>
                        <p class="mt-3 text-sm font-semibold capitalize text-gray-800">{{ calendarTitle }}</p>
                    </div>

                    <div class="p-4 sm:p-6">
                        <div class="grid grid-cols-7 gap-2 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <div v-for="label in calendarWeekdayLabels" :key="label" class="rounded bg-gray-50 py-2">{{ label }}</div>
                        </div>

                        <div class="mt-2 grid grid-cols-7 gap-2">
                            <div
                                v-for="day in calendarDays"
                                :key="day.key"
                                class="min-h-28 rounded-lg border p-2 text-left transition"
                                :class="[
                                    day.inCurrentMonth ? 'border-gray-200 bg-white' : 'border-gray-100 bg-gray-50/70 text-gray-400',
                                    selectedCalendarDate === day.key ? 'ring-2 ring-blue-300' : '',
                                ]"
                                role="button"
                                tabindex="0"
                                @click="selectCalendarDay(day.key)"
                                @keydown.enter.prevent="selectCalendarDay(day.key)"
                                @keydown.space.prevent="selectCalendarDay(day.key)"
                            >
                                <div class="mb-1 flex items-center justify-between">
                                    <span class="text-xs font-semibold">{{ day.day.getDate() }}</span>
                                    <span v-if="day.workouts.length" class="rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-semibold text-blue-700">
                                        {{ day.workouts.length }}
                                    </span>
                                </div>

                                <div class="space-y-1">
                                    <div
                                        v-for="workout in day.workouts.slice(0, 2)"
                                        :key="`cal-${day.key}-${workout.id}`"
                                        class="truncate rounded px-2 py-1 text-[10px] font-semibold"
                                        :class="workout.completion_status === 'completed'
                                            ? 'bg-emerald-100 text-emerald-700'
                                            : workout.completion_status === 'pending'
                                                ? 'bg-rose-100 text-rose-700'
                                                : 'bg-blue-100 text-blue-700'"
                                    >
                                        {{ workout.title }}
                                    </div>
                                    <p v-if="day.workouts.length > 2" class="px-1 text-[10px] text-gray-500">+{{ day.workouts.length - 2 }} más</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 rounded-xl border border-gray-200 bg-gray-50 p-4">
                            <div class="mb-2 flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-gray-900">Detalle del día {{ formatDate(selectedCalendarDate) }}</h3>
                                <div class="flex items-center gap-2 text-[10px] font-semibold uppercase tracking-wide">
                                    <span class="inline-flex items-center gap-1 text-emerald-700"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>Completado</span>
                                    <span class="inline-flex items-center gap-1 text-rose-700"><span class="h-2 w-2 rounded-full bg-rose-500"></span>Pendiente</span>
                                    <span class="inline-flex items-center gap-1 text-blue-700"><span class="h-2 w-2 rounded-full bg-blue-500"></span>Futuro</span>
                                </div>
                            </div>

                            <div v-if="selectedDayWorkouts.length === 0" class="text-sm text-gray-500">
                                No hay entrenamientos programados en este día.
                            </div>

                            <div v-else class="space-y-2">
                                <div
                                    v-for="workout in selectedDayWorkouts"
                                    :key="`selected-${workout.id}`"
                                    class="flex flex-col gap-2 rounded-lg border border-gray-200 bg-white p-3 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">{{ workout.title }}</p>
                                        <p class="text-xs text-gray-500">{{ workout.exercises.length }} {{ workout.exercises.length === 1 ? 'ejercicio' : 'ejercicios' }}</p>
                                        <span
                                            class="mt-1 inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                            :class="workout.completion_status === 'completed'
                                                ? 'bg-emerald-100 text-emerald-700'
                                                : workout.completion_status === 'pending'
                                                    ? 'bg-rose-100 text-rose-700'
                                                    : 'bg-blue-100 text-blue-700'"
                                        >
                                            {{ statusLabel(workout.completion_status) }}
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <a
                                            :href="route('entrenamientos.index')"
                                            class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50"
                                        >
                                            Ver detalles
                                        </a>
                                        <button
                                            v-if="workout.completion_status !== 'completed' && workout.workout_date <= todayKey"
                                            type="button"
                                            class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-emerald-700"
                                            @click="markWorkoutCompleted(workout.id)"
                                        >
                                            Marcar realizado
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </GeneralLayout>
</template>



