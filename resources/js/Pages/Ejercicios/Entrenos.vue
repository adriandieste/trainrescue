<script setup>
import GeneralLayout from '@/Layouts/GeneralLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    exercises: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Array,
        default: () => [],
    },
    entrenamientos: {
        type: Array,
        default: () => [],
    },
    plantillas: {
        type: Array,
        default: () => [],
    },
    hasClub: {
        type: Boolean,
        default: false,
    },
});

const page = usePage();
const flash = computed(() => page.props.flash ?? {});
const hasClub = computed(() => props.hasClub);
const entrenamientos = computed(() => props.entrenamientos ?? []);
const plantillas = computed(() => props.plantillas ?? []);

const search = ref('');
const selectedCategory = ref('all');
const selectedExerciseId = ref(props.exercises[0]?.id ?? null);
const selectedSource = ref(sessionStorage.getItem('exerciseSourceFilter') ?? 'all');
watch(selectedSource, (val) => {
    sessionStorage.setItem('exerciseSourceFilter', val);
    selectedCategory.value = 'all';
});
const filteredExercises = computed(() => {
    const term = search.value.trim().toLowerCase();
    return props.exercises.filter((exercise) => {
        if (selectedSource.value !== 'all' && exercise.source !== selectedSource.value) {
            return false;
        }
        const matchesCategory = selectedCategory.value === 'all' || exercise.category === selectedCategory.value;
        if (!matchesCategory) {
            return false;
        }
        if (!term) {
            return true;
        }
        return (
            exercise.name.toLowerCase().includes(term)
            || exercise.technical_description.toLowerCase().includes(term)
            || exercise.materials.join(' ').toLowerCase().includes(term)
        );
    });
});

watch(filteredExercises, (items) => {
    if (!items.length) {
        selectedExerciseId.value = null;
        return;
    }

    const stillExists = items.some((item) => item.id === selectedExerciseId.value);
    if (!stillExists) {
        selectedExerciseId.value = items[0].id;
    }
}, { immediate: true });

const selectedExercise = computed(() =>
    filteredExercises.value.find((item) => item.id === selectedExerciseId.value) ?? null
);

const entrenamientoForm = useForm({
    title: '',
    workout_date: '',
    target_scope: hasClub.value ? 'club' : 'personal',
    is_template: false,
    exercises: [],
});

const draggedTrainingIndex = ref(null);
const showCreateTrainingForm = ref(false);
const editingTrainingId = ref(null);

const isTrainingInvalid = computed(() => {
    if (!entrenamientoForm.title.trim()) {
        return true;
    }

    if (!entrenamientoForm.is_template && !entrenamientoForm.workout_date) {
        return true;
    }

    if (!entrenamientoForm.exercises.length) {
        return true;
    }

    return entrenamientoForm.exercises.some((item) => !item.sets || item.sets < 1);
});

function sourceLabel(source) {
    return source === 'custom' ? 'Personalizado' : 'RFESS';
}

function resetTrainingForm() {
    entrenamientoForm.reset('title', 'workout_date', 'target_scope', 'is_template', 'exercises');
    entrenamientoForm.clearErrors();
    entrenamientoForm.target_scope = hasClub.value ? 'club' : 'personal';
    entrenamientoForm.is_template = false;
    editingTrainingId.value = null;
    pendingRemoveIndex.value = null;
}

function closeTrainingForm() {
    showCreateTrainingForm.value = false;
    resetTrainingForm();
}

function toggleTrainingForm() {
    if (showCreateTrainingForm.value) {
        closeTrainingForm();
        return;
    }

    showCreateTrainingForm.value = true;
}

function openTrainingEditor(entrenamiento) {
    showCreateTrainingForm.value = true;
    editingTrainingId.value = entrenamiento.id;
    entrenamientoForm.clearErrors();
    entrenamientoForm.title = entrenamiento.title ?? '';
    entrenamientoForm.workout_date = entrenamiento.workout_date ?? '';
    entrenamientoForm.target_scope = entrenamiento.target_scope ?? (hasClub.value ? 'club' : 'personal');
    entrenamientoForm.is_template = Boolean(entrenamiento.is_template);
    entrenamientoForm.exercises = (entrenamiento.exercises ?? []).map((item) => ({
        source: item.source,
        exercise_id: item.exercise_id,
        name: item.name,
        sets: item.sets ?? 3,
        meters: item.meters ?? null,
        rest_seconds: item.rest_seconds ?? 45,
    }));
}

function addExerciseToTraining(exercise) {
    entrenamientoForm.clearErrors();

    entrenamientoForm.exercises.push({
        source: exercise.source,
        exercise_id: exercise.exercise_id,
        name: exercise.name,
        sets: exercise.default_sets ?? 3,
        meters: exercise.default_meters ?? null,
        rest_seconds: exercise.default_rest_seconds ?? 45,
    });
}

function useTemplate(template) {
    showCreateTrainingForm.value = true;
    editingTrainingId.value = null;
    entrenamientoForm.clearErrors();
    entrenamientoForm.title = template.title ?? '';
    entrenamientoForm.workout_date = '';
    entrenamientoForm.target_scope = template.target_scope ?? (hasClub.value ? 'club' : 'personal');
    entrenamientoForm.is_template = false;
    entrenamientoForm.exercises = (template.exercises ?? []).map((item) => ({
        source: item.source,
        exercise_id: item.exercise_id,
        name: item.name,
        sets: item.sets ?? 3,
        meters: item.meters ?? null,
        rest_seconds: item.rest_seconds ?? 45,
    }));
}

const pendingRemoveIndex = ref(null);

function requestRemoveExercise(index) {
    if (editingTrainingId.value !== null) {
        pendingRemoveIndex.value = index;
        return;
    }
    entrenamientoForm.exercises.splice(index, 1);
}

function confirmRemoveExercise() {
    if (pendingRemoveIndex.value !== null) {
        entrenamientoForm.exercises.splice(pendingRemoveIndex.value, 1);
    }
    pendingRemoveIndex.value = null;
}

function cancelRemoveExercise() {
    pendingRemoveIndex.value = null;
}

function moveExerciseUp(index) {
    if (index === 0) return;
    const exercises = entrenamientoForm.exercises;
    [exercises[index - 1], exercises[index]] = [exercises[index], exercises[index - 1]];
}

function moveExerciseDown(index) {
    const exercises = entrenamientoForm.exercises;
    if (index === exercises.length - 1) return;
    [exercises[index], exercises[index + 1]] = [exercises[index + 1], exercises[index]];
}

function startTrainingDrag(index) {
    draggedTrainingIndex.value = index;
}

function dropTrainingExercise(targetIndex) {
    if (draggedTrainingIndex.value === null || draggedTrainingIndex.value === targetIndex) {
        draggedTrainingIndex.value = null;
        return;
    }

    const [item] = entrenamientoForm.exercises.splice(draggedTrainingIndex.value, 1);
    entrenamientoForm.exercises.splice(targetIndex, 0, item);
    draggedTrainingIndex.value = null;
}

function submitTraining() {
    entrenamientoForm.clearErrors();

    if (isTrainingInvalid.value) {
        return;
    }

    const request = entrenamientoForm.transform((data) => ({
        ...data,
        is_template: Boolean(data.is_template),
        workout_date: data.is_template ? null : data.workout_date,
        exercises: data.exercises.map((item) => ({
            source: item.source,
            exercise_id: item.exercise_id,
            sets: Number(item.sets),
            meters: item.meters ? Number(item.meters) : null,
            rest_seconds: item.rest_seconds === '' || item.rest_seconds === null
                ? null
                : Number(item.rest_seconds),
        })),
    }));

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            closeTrainingForm();
        },
    };

    if (editingTrainingId.value) {
        request.patch(route('workouts.update', editingTrainingId.value), options);
        return;
    }

    request.post(route('workouts.store'), options);
}

watch(() => entrenamientoForm.is_template, (isTemplate) => {
    if (isTemplate) {
        entrenamientoForm.workout_date = '';
    }
});

const form = useForm({
    name: '',
    description: '',
    materials: '',
    video_url: '',
    default_sets: 3,
    default_meters: null,
    default_rest_seconds: 45,
});

const editForm = useForm({
    name: '',
    description: '',
    materials: '',
    video_url: '',
    default_sets: 3,
    default_meters: null,
    default_rest_seconds: 45,
});

const showCreateForm = ref(false);
const showEditForm = ref(false);
const exerciseToDelete = ref(null);
const isDeletingExercise = ref(false);

const realtimeErrors = computed(() => {
    const errors = {};

    if (!form.name.trim()) {
        errors.name = 'El nombre es obligatorio.';
    }

    if (!form.description.trim()) {
        errors.description = 'La descripcion es obligatoria.';
    }

    if (form.video_url && !/^https?:\/\//i.test(form.video_url)) {
        errors.video_url = 'El enlace debe empezar por http:// o https://';
    }

    return errors;
});

const hasRealtimeErrors = computed(() => Object.keys(realtimeErrors.value).length > 0);

function submitCustomExercise() {
    form.clearErrors();

    if (hasRealtimeErrors.value) {
        return;
    }

    form.post(route('exercises.custom.store'), {
        preserveScroll: true,
        preserveState: false,
        onSuccess: () => {
            showCreateForm.value = false;
            form.reset();
            form.clearErrors();
        },
    });
}

watch(selectedExercise, (exercise) => {
    showEditForm.value = false;

    if (!exercise || !exercise.can_edit || exercise.source !== 'custom') {
        editForm.reset();
        editForm.clearErrors();
        return;
    }

    editForm.name = exercise.name ?? '';
    editForm.description = exercise.technical_description ?? '';
    editForm.materials = (exercise.materials ?? []).join(', ');
    editForm.video_url = exercise.video_url ?? '';
    editForm.default_sets = exercise.default_sets ?? 3;
    editForm.default_meters = exercise.default_meters ?? null;
    editForm.default_rest_seconds = exercise.default_rest_seconds ?? 45;
    editForm.clearErrors();
}, { immediate: true });

function openEditForm() {
    if (!selectedExercise.value?.can_edit || selectedExercise.value.source !== 'custom') {
        return;
    }

    showEditForm.value = true;
}

function cancelEditForm() {
    showEditForm.value = false;
}

function submitEditExercise() {
    if (!selectedExercise.value?.custom_exercise_id) {
        return;
    }

    editForm.patch(route('exercises.custom.update', selectedExercise.value.custom_exercise_id), {
        preserveScroll: true,
        onSuccess: () => {
            showEditForm.value = false;
        },
    });
}

function confirmDeleteExercise() {
    if (!selectedExercise.value?.custom_exercise_id || !selectedExercise.value?.can_delete) {
        return;
    }

    exerciseToDelete.value = selectedExercise.value;
}

function cancelDeleteExercise() {
    exerciseToDelete.value = null;
}

function deleteExercise() {
    if (!exerciseToDelete.value?.custom_exercise_id) {
        return;
    }

    isDeletingExercise.value = true;

    router.delete(route('exercises.custom.destroy', exerciseToDelete.value.custom_exercise_id), {
        preserveScroll: true,
        onFinish: () => {
            isDeletingExercise.value = false;
            exerciseToDelete.value = null;
        },
    });
}

function formatCategory(category) {
    return category.charAt(0).toUpperCase() + category.slice(1);
}
</script>

<template>
    <Head title="Entrenos" />

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
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">
                                {{ editingTrainingId ? 'Editar entrenamiento' : 'Crear entrenamiento o plantilla' }}
                            </h2>
                            <p class="mt-1 text-sm text-gray-600">Configura sesiones con ejercicios RFESS/personalizados y guárdalas como entrenamiento o plantilla reutilizable.</p>
                        </div>
                        <button
                            type="button"
                            class="inline-flex shrink-0 items-center gap-2 rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-900"
                            @click="toggleTrainingForm"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                            {{ showCreateTrainingForm ? 'Ocultar' : 'Crear' }}
                        </button>
                    </div>
                </div>

                <form v-if="showCreateTrainingForm" class="space-y-4 p-6" @submit.prevent="submitTraining">
                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-sm font-medium text-gray-700">Titulo del entrenamiento *</label>
                            <input
                                v-model="entrenamientoForm.title"
                                type="text"
                                class="w-full rounded-lg border px-3 py-2 text-sm focus:ring-2"
                                :class="entrenamientoForm.errors.title ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'"
                                placeholder="Ej: Entrenamiento de Resistencia"
                            >
                            <p v-if="entrenamientoForm.errors.title" class="mt-1 text-xs text-red-600">{{ entrenamientoForm.errors.title }}</p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Fecha {{ entrenamientoForm.is_template ? '(opcional)' : '*' }}</label>
                            <input
                                v-model="entrenamientoForm.workout_date"
                                type="date"
                                :disabled="entrenamientoForm.is_template"
                                class="w-full rounded-lg border px-3 py-2 text-sm focus:ring-2"
                                :class="[
                                    entrenamientoForm.errors.workout_date ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500',
                                    entrenamientoForm.is_template ? 'cursor-not-allowed bg-gray-100 text-gray-500' : '',
                                ]"
                            >
                            <p v-if="entrenamientoForm.errors.workout_date" class="mt-1 text-xs text-red-600">{{ entrenamientoForm.errors.workout_date }}</p>
                            <p v-if="entrenamientoForm.is_template" class="mt-1 text-xs text-gray-500">Las plantillas no se asignan a una fecha concreta.</p>
                        </div>
                    </div>

                    <div class="rounded-lg border border-indigo-200 bg-indigo-50 p-3">
                        <label class="inline-flex items-start gap-2 text-sm text-indigo-900">
                            <input
                                v-model="entrenamientoForm.is_template"
                                type="checkbox"
                                class="mt-0.5 rounded border-indigo-300 text-indigo-600 focus:ring-indigo-500"
                            >
                            <span>
                                <span class="font-semibold">Guardar como plantilla</span>
                                <span class="mt-0.5 block text-xs text-indigo-700">Se guarda en "Mis Plantillas" para reutilizarla en futuras sesiones.</span>
                            </span>
                        </label>
                    </div>

                    <div class="max-w-sm">
                        <label class="mb-1 block text-sm font-medium text-gray-700">Guardar para *</label>
                        <select
                            v-model="entrenamientoForm.target_scope"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="personal">Mis alumnos personales</option>
                            <option v-if="hasClub" value="club">Mi club</option>
                        </select>
                        <p v-if="entrenamientoForm.errors.target_scope" class="mt-1 text-xs text-red-600">{{ entrenamientoForm.errors.target_scope }}</p>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <div class="mb-3 flex items-center justify-between gap-2">
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-600">Ejercicios del entrenamiento</h3>
                            <span class="text-xs text-gray-500">{{ entrenamientoForm.exercises.length }} {{ entrenamientoForm.exercises.length === 1 ? 'ejercicio' : 'ejercicios' }}</span>
                        </div>

                        <div v-if="entrenamientoForm.exercises.length === 0" class="rounded-lg border border-dashed border-gray-300 bg-white p-4 text-sm text-gray-500">
                            Agrega ejercicios desde la biblioteca lateral para construir el entrenamiento.
                        </div>

                        <div v-else class="space-y-3">
                            <div
                                v-for="(item, index) in entrenamientoForm.exercises"
                                :key="`${item.source}-${item.exercise_id}-${index}`"
                                draggable="true"
                                class="rounded-lg border border-gray-200 bg-white p-3 transition-shadow"
                                :class="draggedTrainingIndex === index ? 'opacity-50 shadow-lg ring-2 ring-blue-300' : ''"
                                @dragstart="startTrainingDrag(index)"
                                @dragover.prevent
                                @drop.prevent="dropTrainingExercise(index)"
                                @dragend="draggedTrainingIndex = null"
                            >
                                <div class="mb-3 flex items-center justify-between gap-2">
                                    <!-- Drag handle + name -->
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="cursor-grab shrink-0 text-gray-300 hover:text-gray-500" title="Arrastrar para reordenar">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M7 2a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 2zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 8zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 14zm6-8a2 2 0 1 0-.001-4.001A2 2 0 0 0 13 6zm0 2a2 2 0 1 0 .001 4.001A2 2 0 0 0 13 8zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 13 14z"/>
                                            </svg>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-gray-900">{{ index + 1 }}. {{ item.name }}</p>
                                            <span
                                                class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                                :class="item.source === 'custom' ? 'bg-violet-100 text-violet-700' : 'bg-blue-100 text-blue-700'"
                                            >
                                                {{ sourceLabel(item.source) }}
                                            </span>
                                        </div>
                                    </div>
                                    <!-- Controls: arrows + delete -->
                                    <div class="flex shrink-0 items-center gap-1">
                                        <button
                                            type="button"
                                            :disabled="index === 0"
                                            class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-700 disabled:opacity-30 disabled:cursor-not-allowed"
                                            title="Subir"
                                            @click="moveExerciseUp(index)"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg>
                                        </button>
                                        <button
                                            type="button"
                                            :disabled="index === entrenamientoForm.exercises.length - 1"
                                            class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-700 disabled:opacity-30 disabled:cursor-not-allowed"
                                            title="Bajar"
                                            @click="moveExerciseDown(index)"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                        </button>
                                        <button
                                            type="button"
                                            class="ml-1 rounded-lg border border-red-200 bg-red-50 p-1.5 text-red-600 transition hover:bg-red-100"
                                            title="Eliminar ejercicio"
                                            @click="requestRemoveExercise(index)"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </div>

                                <div class="grid gap-2 md:grid-cols-3">
                                    <label class="text-xs font-medium text-gray-600">
                                        Series *
                                        <input
                                            v-model.number="item.sets"
                                            type="number"
                                            min="1"
                                            class="mt-1 w-full rounded-lg border px-2 py-1.5 text-sm focus:ring-2"
                                            :class="(!item.sets || item.sets < 1) ? 'border-red-400 focus:border-red-500 focus:ring-red-300' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-300'"
                                        >
                                        <span v-if="!item.sets || item.sets < 1" class="text-red-500 text-[10px]">Mínimo 1 serie</span>
                                    </label>
                                    <label class="text-xs font-medium text-gray-600">
                                        Metros
                                        <input
                                            v-model.number="item.meters"
                                            type="number"
                                            min="1"
                                            placeholder="Opcional"
                                            class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-300"
                                        >
                                    </label>
                                    <label class="text-xs font-medium text-gray-600">
                                        Descanso (s)
                                        <input
                                            v-model.number="item.rest_seconds"
                                            type="number"
                                            min="0"
                                            placeholder="Opcional"
                                            class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-300"
                                        >
                                    </label>
                                </div>
                            </div>
                        </div>

                        <p v-if="entrenamientoForm.errors.exercises" class="mt-2 text-xs text-red-600">{{ entrenamientoForm.errors.exercises }}</p>
                        <p v-if="isTrainingInvalid && !entrenamientoForm.processing" class="mt-2 text-xs text-amber-700">
                            Completa titulo, {{ entrenamientoForm.is_template ? 'al menos un ejercicio con series' : 'fecha y al menos un ejercicio con series' }}.
                        </p>
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            :disabled="entrenamientoForm.processing || isTrainingInvalid"
                            class="inline-flex items-center rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-900 disabled:opacity-50"
                        >
                            <span v-if="entrenamientoForm.processing">Guardando...</span>
                            <span v-else>
                                {{ editingTrainingId ? 'Guardar cambios' : (entrenamientoForm.is_template ? 'Guardar plantilla' : 'Guardar entrenamiento') }}
                            </span>
                        </button>
                    </div>
                </form>
            </div>

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="border-b border-gray-200 p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">Crear ejercicio personalizado</h2>
                            <p class="mt-1 text-sm text-gray-600">Amplia tu biblioteca privada con ejercicios adaptados a tu metodologia.</p>
                        </div>
                        <button
                            type="button"
                            class="inline-flex shrink-0 items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700"
                            @click="showCreateForm = !showCreateForm"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                            {{ showCreateForm ? 'Ocultar' : 'Crear' }}
                        </button>
                    </div>
                </div>

                <form v-if="showCreateForm" class="grid gap-4 p-6 md:grid-cols-2" @submit.prevent="submitCustomExercise">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Nombre *</label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full rounded-lg border px-3 py-2 text-sm focus:ring-2"
                            :class="(form.errors.name || realtimeErrors.name) ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'"
                            placeholder="Ej: Circuito apnea + rescate"
                        >
                        <p v-if="form.errors.name || realtimeErrors.name" class="mt-1 text-xs text-red-600">
                            {{ form.errors.name || realtimeErrors.name }}
                        </p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Enlace de video (opcional)</label>
                        <input
                            v-model="form.video_url"
                            type="url"
                            class="w-full rounded-lg border px-3 py-2 text-sm focus:ring-2"
                            :class="(form.errors.video_url || realtimeErrors.video_url) ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'"
                            placeholder="https://..."
                        >
                        <p v-if="form.errors.video_url || realtimeErrors.video_url" class="mt-1 text-xs text-red-600">
                            {{ form.errors.video_url || realtimeErrors.video_url }}
                        </p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-gray-700">Descripcion tecnica *</label>
                        <textarea
                            v-model="form.description"
                            rows="4"
                            class="w-full rounded-lg border px-3 py-2 text-sm focus:ring-2"
                            :class="(form.errors.description || realtimeErrors.description) ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'"
                            placeholder="Describe el objetivo y la ejecucion tecnica del ejercicio"
                        />
                        <p v-if="form.errors.description || realtimeErrors.description" class="mt-1 text-xs text-red-600">
                            {{ form.errors.description || realtimeErrors.description }}
                        </p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-gray-700">Material necesario</label>
                        <textarea
                            v-model="form.materials"
                            rows="2"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                            placeholder="Separado por coma o salto de linea (ej: maniqui, aletas, tubo)"
                        />
                        <p v-if="form.errors.materials" class="mt-1 text-xs text-red-600">{{ form.errors.materials }}</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Series por defecto</label>
                        <input
                            v-model.number="form.default_sets"
                            type="number"
                            min="1"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                        >
                        <p v-if="form.errors.default_sets" class="mt-1 text-xs text-red-600">{{ form.errors.default_sets }}</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Metros por defecto</label>
                        <input
                            v-model.number="form.default_meters"
                            type="number"
                            min="1"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                        >
                        <p v-if="form.errors.default_meters" class="mt-1 text-xs text-red-600">{{ form.errors.default_meters }}</p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-gray-700">Descanso por defecto (s)</label>
                        <input
                            v-model.number="form.default_rest_seconds"
                            type="number"
                            min="0"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                        >
                        <p v-if="form.errors.default_rest_seconds" class="mt-1 text-xs text-red-600">{{ form.errors.default_rest_seconds }}</p>
                    </div>

                    <div class="md:col-span-2 flex justify-end">
                        <button
                            type="submit"
                            :disabled="form.processing || hasRealtimeErrors"
                            class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700 disabled:opacity-50"
                        >
                            <span v-if="form.processing">Guardando...</span>
                            <span v-else>Guardar ejercicio personalizado</span>
                        </button>
                    </div>
                </form>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg lg:col-span-1">
                    <div class="border-b border-gray-200 p-4">
                        <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-500">Origen</label>
                        <div class="mb-4 flex gap-1.5">
                            <button
                                type="button"
                                class="flex-1 rounded-lg px-2 py-1.5 text-xs font-semibold transition"
                                :class="selectedSource === 'all' ? 'bg-gray-800 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                                @click="selectedSource = 'all'"
                            >
                                Todos
                            </button>
                            <button
                                type="button"
                                class="flex-1 rounded-lg px-2 py-1.5 text-xs font-semibold transition"
                                :class="selectedSource === 'predefined' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                                @click="selectedSource = 'predefined'"
                            >
                                RFESS
                            </button>
                            <button
                                type="button"
                                class="flex-1 rounded-lg px-2 py-1.5 text-xs font-semibold transition"
                                :class="selectedSource === 'custom' ? 'bg-violet-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                                @click="selectedSource = 'custom'"
                            >
                                Mis ejercicios
                            </button>
                        </div>

                        <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-gray-500">Buscar</label>
                        <input
                            v-model="search"
                            type="text"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                            placeholder="Nombre, descripcion o material"
                        >

                        <label class="mb-2 mt-4 block text-xs font-semibold uppercase tracking-wide text-gray-500">Categoria</label>
                        <select
                            v-model="selectedCategory"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="all">Todas</option>
                            <option v-for="category in categories" :key="category" :value="category">
                                {{ formatCategory(category) }}
                            </option>
                        </select>
                    </div>

                    <div class="max-h-[560px] overflow-y-auto">
                        <div
                            v-for="exercise in filteredExercises"
                            :key="exercise.id"
                            class="w-full border-b border-gray-100 px-4 py-3 text-left transition hover:bg-gray-50"
                            :class="[
                                selectedExerciseId === exercise.id ? 'bg-blue-50' : '',
                                exercise.source === 'custom' ? 'border-l-4 border-l-violet-400' : 'border-l-4 border-l-transparent',
                            ]"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <button
                                    type="button"
                                    class="min-w-0 text-left"
                                    @click="selectedExerciseId = exercise.id"
                                >
                                    <span class="block truncate text-sm font-semibold text-gray-900">{{ exercise.name }}</span>
                                </button>
                                <button
                                    type="button"
                                    class="shrink-0 rounded-md border border-slate-300 bg-white px-2 py-1 text-[10px] font-semibold text-slate-700 hover:bg-slate-100"
                                    @click.stop="addExerciseToTraining(exercise)"
                                >
                                    Anadir
                                </button>
                            </div>
                            <button
                                type="button"
                                class="mt-1 inline-flex items-center gap-2"
                                @click="selectedExerciseId = exercise.id"
                            >
                                <span class="block text-xs text-gray-500">{{ formatCategory(exercise.category) }}</span>
                                <span
                                    class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                    :class="exercise.source === 'custom' ? 'bg-violet-100 text-violet-700' : 'bg-blue-100 text-blue-700'"
                                >
                                    {{ exercise.source === 'custom' ? 'Personalizado' : 'RFESS' }}
                                </span>
                            </button>
                        </div>

                        <div v-if="filteredExercises.length === 0" class="p-4 text-sm text-gray-500">
                            No hay ejercicios que coincidan con los filtros actuales.
                        </div>

                        <div v-if="filteredExercises.length > 0" class="border-t border-gray-100 px-4 py-2 text-xs text-gray-400">
                            {{ filteredExercises.length }} {{ filteredExercises.length === 1 ? 'ejercicio' : 'ejercicios' }} encontrado{{ filteredExercises.length === 1 ? '' : 's' }}
                        </div>
                    </div>

                    <div v-if="entrenamientos.length > 0" class="border-t border-gray-200 p-4">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500">Cronograma de entrenamientos</h3>
                        <div class="mt-2 space-y-2">
                            <div
                                v-for="entrenamiento in entrenamientos"
                                :key="entrenamiento.id"
                                class="rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 text-xs"
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <p class="font-semibold text-gray-800">{{ entrenamiento.title }}</p>
                                        <p class="text-gray-500">{{ entrenamiento.workout_date }} · {{ entrenamiento.target_scope === 'club' ? 'Club' : 'Personal' }}</p>
                                    </div>
                                    <button
                                        v-if="entrenamiento.can_edit"
                                        type="button"
                                        class="rounded-md border border-slate-300 bg-white px-2 py-1 text-[10px] font-semibold text-slate-700 hover:bg-slate-100"
                                        @click="openTrainingEditor(entrenamiento)"
                                    >
                                        Editar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="plantillas.length > 0" class="border-t border-gray-200 p-4">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Mis Plantillas</h3>
                        <div class="mt-2 space-y-2">
                            <div
                                v-for="plantilla in plantillas"
                                :key="`tpl-${plantilla.id}`"
                                class="rounded-lg border border-indigo-100 bg-indigo-50/70 px-3 py-2 text-xs"
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <p class="font-semibold text-indigo-900">{{ plantilla.title }}</p>
                                        <p class="text-indigo-700">{{ plantilla.exercises.length }} {{ plantilla.exercises.length === 1 ? 'ejercicio' : 'ejercicios' }} · {{ plantilla.target_scope === 'club' ? 'Club' : 'Personal' }}</p>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <button
                                            type="button"
                                            class="rounded-md border border-indigo-300 bg-white px-2 py-1 text-[10px] font-semibold text-indigo-700 hover:bg-indigo-100"
                                            @click="useTemplate(plantilla)"
                                        >
                                            Usar
                                        </button>
                                        <button
                                            v-if="plantilla.can_edit"
                                            type="button"
                                            class="rounded-md border border-slate-300 bg-white px-2 py-1 text-[10px] font-semibold text-slate-700 hover:bg-slate-100"
                                            @click="openTrainingEditor(plantilla)"
                                        >
                                            Editar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg lg:col-span-2">
                    <div v-if="selectedExercise" class="p-6">
                        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                            <h2 class="text-xl font-semibold text-gray-900">{{ selectedExercise.name }}</h2>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">
                                    {{ formatCategory(selectedExercise.category) }}
                                </span>
                                <span
                                    class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                                    :class="selectedExercise.source === 'custom' ? 'bg-violet-100 text-violet-700' : 'bg-slate-100 text-slate-700'"
                                >
                                    {{ selectedExercise.source === 'custom' ? 'Ejercicio personalizado' : 'Plantilla RFESS' }}
                                </span>
                            </div>
                        </div>

                        <div v-if="selectedExercise.can_edit || selectedExercise.can_delete" class="mb-4 flex items-center gap-2">
                            <button
                                v-if="selectedExercise.can_edit"
                                type="button"
                                class="inline-flex items-center rounded-lg border border-blue-300 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-100"
                                @click="openEditForm"
                            >
                                Editar
                            </button>

                            <button
                                v-if="selectedExercise.can_delete"
                                type="button"
                                class="inline-flex items-center rounded-lg border border-red-300 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-100"
                                @click="confirmDeleteExercise"
                            >
                                Eliminar
                            </button>
                        </div>

                        <form
                            v-if="showEditForm && selectedExercise.can_edit"
                            class="mb-4 grid gap-4 rounded-lg border border-blue-100 bg-blue-50 p-4 md:grid-cols-2"
                            @submit.prevent="submitEditExercise"
                        >
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Nombre *</label>
                                <input
                                    v-model="editForm.name"
                                    type="text"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                >
                                <p v-if="editForm.errors.name" class="mt-1 text-xs text-red-600">{{ editForm.errors.name }}</p>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Enlace de video (opcional)</label>
                                <input
                                    v-model="editForm.video_url"
                                    type="url"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                >
                                <p v-if="editForm.errors.video_url" class="mt-1 text-xs text-red-600">{{ editForm.errors.video_url }}</p>
                            </div>

                            <div class="md:col-span-2">
                                <label class="mb-1 block text-sm font-medium text-gray-700">Descripcion tecnica *</label>
                                <textarea
                                    v-model="editForm.description"
                                    rows="3"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                />
                                <p v-if="editForm.errors.description" class="mt-1 text-xs text-red-600">{{ editForm.errors.description }}</p>
                            </div>

                            <div class="md:col-span-2">
                                <label class="mb-1 block text-sm font-medium text-gray-700">Material necesario</label>
                                <textarea
                                    v-model="editForm.materials"
                                    rows="2"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                />
                                <p v-if="editForm.errors.materials" class="mt-1 text-xs text-red-600">{{ editForm.errors.materials }}</p>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Series por defecto</label>
                                <input
                                    v-model.number="editForm.default_sets"
                                    type="number"
                                    min="1"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                >
                                <p v-if="editForm.errors.default_sets" class="mt-1 text-xs text-red-600">{{ editForm.errors.default_sets }}</p>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Metros por defecto</label>
                                <input
                                    v-model.number="editForm.default_meters"
                                    type="number"
                                    min="1"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                >
                                <p v-if="editForm.errors.default_meters" class="mt-1 text-xs text-red-600">{{ editForm.errors.default_meters }}</p>
                            </div>

                            <div class="md:col-span-2">
                                <label class="mb-1 block text-sm font-medium text-gray-700">Descanso por defecto (s)</label>
                                <input
                                    v-model.number="editForm.default_rest_seconds"
                                    type="number"
                                    min="0"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                >
                                <p v-if="editForm.errors.default_rest_seconds" class="mt-1 text-xs text-red-600">{{ editForm.errors.default_rest_seconds }}</p>
                            </div>

                            <div class="md:col-span-2 flex justify-end gap-2">
                                <button
                                    type="button"
                                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                                    @click="cancelEditForm"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    :disabled="editForm.processing"
                                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700 disabled:opacity-50"
                                >
                                    <span v-if="editForm.processing">Guardando...</span>
                                    <span v-else>Guardar cambios</span>
                                </button>
                            </div>
                        </form>

                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-600">Descripcion tecnica</h3>
                            <p class="mt-2 text-sm leading-relaxed text-gray-700">
                                {{ selectedExercise.technical_description }}
                            </p>
                        </div>

                        <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-600">Material necesario</h3>
                            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-gray-700">
                                <li v-for="material in selectedExercise.materials" :key="material">{{ material }}</li>
                                <li v-if="selectedExercise.materials.length === 0" class="list-none text-gray-500">Sin material especificado.</li>
                            </ul>
                        </div>

                        <div v-if="selectedExercise.video_url" class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-600">Enlace de video</h3>
                            <a
                                :href="selectedExercise.video_url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mt-2 inline-flex items-center text-sm font-medium text-blue-700 hover:text-blue-800"
                            >
                                Ver recurso multimedia
                            </a>
                        </div>
                    </div>

                    <div v-else class="p-10 text-center text-sm text-gray-500">
                        Selecciona un ejercicio para ver su detalle tecnico.
                    </div>
                </div>
            </div>
        </div>

        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="exerciseToDelete" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="cancelDeleteExercise" />
                    <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
                            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">Confirmar eliminación</h3>
                        <p class="mt-2 text-sm text-gray-600">
                            ¿Estás seguro de que deseas eliminar el ejercicio
                            <strong class="text-gray-900">{{ exerciseToDelete.name }}</strong>?
                            Esta acción no se puede deshacer.
                        </p>
                        <div class="mt-6 flex gap-3">
                            <button
                                type="button"
                                class="flex-1 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                                @click="cancelDeleteExercise"
                            >
                                Cancelar
                            </button>
                            <button
                                type="button"
                                class="flex-1 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700 disabled:opacity-50"
                                :disabled="isDeletingExercise"
                                @click="deleteExercise"
                            >
                                <span v-if="isDeletingExercise">Eliminando...</span>
                                <span v-else>Sí, eliminar</span>
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Confirmacion de eliminar ejercicio de sesión en modo edicion -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="pendingRemoveIndex !== null" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="cancelRemoveExercise" />
                    <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-amber-100">
                            <svg class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">Quitar ejercicio de la sesion</h3>
                        <p class="mt-2 text-sm text-gray-600">
                            Estas editando un entrenamiento ya guardado. Al guardar los cambios, este ejercicio sera eliminado permanentemente de la sesion.
                            <strong class="text-gray-900">{{ pendingRemoveIndex !== null ? entrenamientoForm.exercises[pendingRemoveIndex]?.name : '' }}</strong>
                        </p>
                        <p class="mt-2 text-xs text-amber-700 font-medium">El ejercicio seguira disponible en la biblioteca.</p>
                        <div class="mt-6 flex gap-3">
                            <button
                                type="button"
                                class="flex-1 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                                @click="cancelRemoveExercise"
                            >
                                Cancelar
                            </button>
                            <button
                                type="button"
                                class="flex-1 rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-amber-700"
                                @click="confirmRemoveExercise"
                            >
                                Si, quitar
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </GeneralLayout>
</template>


