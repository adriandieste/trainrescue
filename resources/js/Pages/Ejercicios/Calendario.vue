<script setup>
import GeneralLayout from '@/Layouts/GeneralLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    calendarEvents: {
        type: Array,
        default: () => [],
    },
    hasClub: {
        type: Boolean,
        default: false,
    },
    clubMembers: {
        type: Array,
        default: () => [],
    },
    groups: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const flash = computed(() => page.props.flash ?? {});

const calendarEvents = ref([...(props.calendarEvents ?? [])]);
const calendarView = ref('month');
const calendarCursor = ref(new Date());
const calendarDateFilter = ref('');
const calendarAthleteFilter = ref('all');
const calendarGroupFilter = ref('all');

const draggedEvent = ref(null);
const dragOverDate = ref(null);
const toastMessage = ref('');
const toastType = ref('success');
const showToast = ref(false);
const rescheduleInProgress = ref(false);

const weekdayLabels = ['Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab', 'Dom'];

function formatDateKey(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function parseDateKey(dateKey) {
    const [year, month, day] = String(dateKey).split('-').map(Number);
    return new Date(year, (month || 1) - 1, day || 1);
}

function startOfWeek(date) {
    const base = new Date(date.getFullYear(), date.getMonth(), date.getDate());
    const day = base.getDay();
    const diffToMonday = day === 0 ? -6 : 1 - day;
    base.setDate(base.getDate() + diffToMonday);
    return base;
}

function sameMemberSet(a, b) {
    const left = [...new Set((a ?? []).map(Number))].sort((x, y) => x - y);
    const right = [...new Set((b ?? []).map(Number))].sort((x, y) => x - y);
    if (left.length !== right.length) {
        return false;
    }
    return left.every((value, index) => value === right[index]);
}

const filteredCalendarEvents = computed(() => {
    const athleteId = calendarAthleteFilter.value === 'all' ? null : Number(calendarAthleteFilter.value);
    const group = calendarGroupFilter.value === 'all'
        ? null
        : props.groups.find((item) => Number(item.id) === Number(calendarGroupFilter.value));

    return calendarEvents.value.filter((event) => {
        if (!event.workout_date) {
            return false;
        }

        if (calendarDateFilter.value && event.workout_date !== calendarDateFilter.value) {
            return false;
        }

        if (athleteId) {
            if (event.target_scope === 'club') {
                // Entrenamiento de club aplica a todos los miembros
            } else if (event.target_scope === 'grupo') {
                if (!event.assigned_user_ids?.includes(athleteId)) {
                    return false;
                }
            } else {
                return false;
            }
        }

        if (group) {
            if (event.target_scope !== 'grupo') {
                return false;
            }

            if (!sameMemberSet(event.assigned_user_ids, group.user_ids)) {
                return false;
            }
        }

        return true;
    });
});

const eventsByDate = computed(() => {
    const map = new Map();
    for (const event of filteredCalendarEvents.value) {
        if (!map.has(event.workout_date)) {
            map.set(event.workout_date, []);
        }
        map.get(event.workout_date).push(event);
    }
    return map;
});

const calendarDays = computed(() => {
    if (calendarView.value === 'week') {
        const start = startOfWeek(calendarCursor.value);
        return Array.from({ length: 7 }, (_, index) => {
            const day = new Date(start);
            day.setDate(start.getDate() + index);
            return {
                key: formatDateKey(day),
                day,
                inCurrentMonth: true,
                events: eventsByDate.value.get(formatDateKey(day)) ?? [],
            };
        });
    }

    const year = calendarCursor.value.getFullYear();
    const month = calendarCursor.value.getMonth();
    const monthStart = new Date(year, month, 1);
    const monthEnd = new Date(year, month + 1, 0);
    const gridStart = startOfWeek(monthStart);
    const totalDays = Math.ceil((monthEnd.getDate() + (monthStart.getDay() === 0 ? 6 : monthStart.getDay() - 1)) / 7) * 7;

    return Array.from({ length: totalDays }, (_, index) => {
        const day = new Date(gridStart);
        day.setDate(gridStart.getDate() + index);
        return {
            key: formatDateKey(day),
            day,
            inCurrentMonth: day.getMonth() === month,
            events: eventsByDate.value.get(formatDateKey(day)) ?? [],
        };
    });
});

const calendarTitle = computed(() => {
    if (calendarView.value === 'week') {
        const start = calendarDays.value[0]?.day;
        const end = calendarDays.value[6]?.day;
        if (!start || !end) {
            return 'Semana';
        }
        return `${start.toLocaleDateString('es-ES', { day: '2-digit', month: 'short' })} - ${end.toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' })}`;
    }

    return calendarCursor.value.toLocaleDateString('es-ES', { month: 'long', year: 'numeric' });
});

function shiftCalendar(step) {
    const next = new Date(calendarCursor.value);
    if (calendarView.value === 'week') {
        next.setDate(next.getDate() + (step * 7));
    } else {
        next.setMonth(next.getMonth() + step);
    }
    calendarCursor.value = next;
}

function goToToday() {
    const today = new Date();
    calendarCursor.value = new Date(today.getFullYear(), today.getMonth(), today.getDate());
}

function openCalendarEvent(event) {
    if (!event?.can_edit) {
        return;
    }

    router.get(route('exercises.library'), { edit_workout_id: Number(event.id) }, {
        preserveScroll: false,
    });
}

function onDragStart(event, workoutEvent) {
    draggedEvent.value = {
        id: workoutEvent.id,
        originalDate: workoutEvent.workout_date,
        title: workoutEvent.title,
    };
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', String(workoutEvent.id));
}

function onDragEnd() {
    draggedEvent.value = null;
    dragOverDate.value = null;
}

function onDragOver(event, dateKey) {
    event.preventDefault();
    event.dataTransfer.dropEffect = 'move';
    dragOverDate.value = dateKey;
}

function onDragLeave() {
    dragOverDate.value = null;
}

async function onDrop(event, targetDateKey) {
    event.preventDefault();
    dragOverDate.value = null;

    if (!draggedEvent.value) {
        return;
    }

    const { id: workoutId, originalDate, title } = draggedEvent.value;

    if (targetDateKey === originalDate) {
        draggedEvent.value = null;
        return;
    }

    const eventIndex = calendarEvents.value.findIndex((e) => e.id === workoutId);
    if (eventIndex === -1) {
        draggedEvent.value = null;
        return;
    }

    const oldEvent = { ...calendarEvents.value[eventIndex] };
    calendarEvents.value[eventIndex].workout_date = targetDateKey;

    draggedEvent.value = null;
    rescheduleInProgress.value = true;

    try {
        const response = await axios.patch(route('workouts.reschedule', workoutId), {
            workout_date: targetDateKey,
        });

        showToastMessage(`✓ ${title} reprogramado al ${formatDateForDisplay(targetDateKey)}`, 'success');
    } catch (error) {
        calendarEvents.value[eventIndex] = oldEvent;
        showToastMessage(`✗ Error al reprogramar: ${error.response?.data?.message || 'Intenta de nuevo'}`, 'error');
    } finally {
        rescheduleInProgress.value = false;
    }
}

function showToastMessage(message, type = 'success') {
    toastMessage.value = message;
    toastType.value = type;
    showToast.value = true;

    setTimeout(() => {
        showToast.value = false;
    }, 4000);
}

function formatDateForDisplay(dateKey) {
    const [year, month, day] = String(dateKey).split('-');
    return `${day}/${month}/${year}`;
}

watch(calendarDateFilter, (dateKey) => {
    if (!dateKey) {
        return;
    }

    calendarCursor.value = parseDateKey(dateKey);
});
</script>

<template>
    <Head title="Calendario" />

    <GeneralLayout>
        <div class="space-y-6">
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

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="border-b border-gray-200 p-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">Calendario de entrenamientos</h2>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <button
                                type="button"
                                class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50"
                                @click="shiftCalendar(-1)"
                            >
                                Anterior
                            </button>
                            <button
                                type="button"
                                class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50"
                                @click="goToToday"
                            >
                                Hoy
                            </button>
                            <button
                                type="button"
                                class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50"
                                @click="shiftCalendar(1)"
                            >
                                Siguiente
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <p class="text-sm font-semibold capitalize text-gray-800">{{ calendarTitle }}</p>

                        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                            <select
                                v-model="calendarView"
                                class="rounded-lg border border-gray-300 px-3 py-2 text-xs focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                            >
                                <option value="month">Vista mensual</option>
                                <option value="week">Vista semanal</option>
                            </select>

                            <input
                                v-model="calendarDateFilter"
                                type="date"
                                class="rounded-lg border border-gray-300 px-3 py-2 text-xs focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                            >

                            <select
                                v-model="calendarAthleteFilter"
                                class="rounded-lg border border-gray-300 px-3 py-2 text-xs focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                :disabled="!hasClub"
                            >
                                <option value="all">Todos los atletas</option>
                                <option v-for="member in clubMembers" :key="`filter-athlete-${member.id}`" :value="String(member.id)">
                                    {{ member.name }}
                                </option>
                            </select>

                            <select
                                v-model="calendarGroupFilter"
                                class="rounded-lg border border-gray-300 px-3 py-2 text-xs focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                :disabled="!hasClub"
                            >
                                <option value="all">Todos los grupos</option>
                                <option v-for="group in groups" :key="`filter-group-${group.id}`" :value="String(group.id)">
                                    {{ group.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="p-4 sm:p-6">
                    <div class="grid grid-cols-7 gap-2 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <div v-for="label in weekdayLabels" :key="label" class="rounded bg-gray-50 py-2">{{ label }}</div>
                    </div>

                    <div class="mt-2 grid grid-cols-7 gap-2">
                        <div
                            v-for="day in calendarDays"
                            :key="day.key"
                            class="min-h-28 rounded-lg border p-2 transition"
                            :class="[
                                day.inCurrentMonth ? 'border-gray-200 bg-white' : 'border-gray-100 bg-gray-50/60 text-gray-400',
                                dragOverDate === day.key ? 'border-blue-400 bg-blue-50 ring-2 ring-blue-300' : '',
                            ]"
                            @dragover="onDragOver($event, day.key)"
                            @dragleave="onDragLeave"
                            @drop="onDrop($event, day.key)"
                        >
                            <div class="mb-1 flex items-center justify-between">
                                <span class="text-xs font-semibold">{{ day.day.getDate() }}</span>
                                <span v-if="day.events.length" class="rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-semibold text-blue-700">
                                    {{ day.events.length }}
                                </span>
                            </div>

                            <div class="space-y-1">
                                <button
                                    v-for="event in day.events.slice(0, 3)"
                                    :key="`calendar-event-${event.id}`"
                                    type="button"
                                    draggable="true"
                                    class="block w-full truncate rounded-md px-2 py-1 text-left text-[11px] font-medium transition"
                                    :class="[
                                        draggedEvent?.id === event.id ? 'opacity-50 shadow-lg ring-2 ring-blue-300' : '',
                                        event.target_scope === 'club'
                                            ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200 cursor-grab active:cursor-grabbing'
                                            : event.target_scope === 'grupo'
                                                ? 'bg-purple-100 text-purple-700 hover:bg-purple-200 cursor-grab active:cursor-grabbing'
                                                : 'bg-slate-100 text-slate-700 hover:bg-slate-200 cursor-grab active:cursor-grabbing'
                                    ]"
                                    @dragstart="onDragStart($event, event)"
                                    @dragend="onDragEnd"
                                    @click="openCalendarEvent(event)"
                                >
                                    {{ event.title }}
                                </button>
                                <p v-if="day.events.length > 3" class="px-1 text-[10px] font-medium text-gray-500">
                                    +{{ day.events.length - 3 }} mas
                                </p>
                            </div>
                        </div>
                    </div>

                    <p v-if="filteredCalendarEvents.length === 0" class="mt-4 rounded-lg border border-dashed border-gray-300 bg-gray-50 p-3 text-sm text-gray-600">
                        No hay entrenamientos que coincidan con los filtros seleccionados.
                    </p>
                </div>
            </div>
            <transition name="toast">
                <div v-if="showToast" class="fixed bottom-4 right-4 max-w-sm rounded-lg shadow-lg" :class="[
                    toastType === 'success'
                        ? 'border border-green-200 bg-green-50 text-green-800'
                        : 'border border-red-200 bg-red-50 text-red-800'
                ]">
                    <div class="flex items-center gap-3 p-4">
                        <span v-if="toastType === 'success'" class="text-green-600">✓</span>
                        <span v-else class="text-red-600">✕</span>
                        <p class="text-sm font-medium">{{ toastMessage }}</p>
                    </div>
                </div>
            </transition>
        </div>
    </GeneralLayout>
</template>

