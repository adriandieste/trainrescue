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
});

const page = usePage();
const flash = computed(() => page.props.flash ?? {});

const search = ref('');
const selectedCategory = ref('all');
const selectedExerciseId = ref(props.exercises[0]?.id ?? null);

const filteredExercises = computed(() => {
    const term = search.value.trim().toLowerCase();

    return props.exercises.filter((exercise) => {
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

const form = useForm({
    name: '',
    description: '',
    materials: '',
    video_url: '',
});

const editForm = useForm({
    name: '',
    description: '',
    materials: '',
    video_url: '',
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
        onSuccess: () => {
            form.reset();
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
                        <button
                            v-for="exercise in filteredExercises"
                            :key="exercise.id"
                            type="button"
                            class="w-full border-b border-gray-100 px-4 py-3 text-left transition hover:bg-gray-50"
                            :class="selectedExerciseId === exercise.id ? 'bg-blue-50' : ''"
                            @click="selectedExerciseId = exercise.id"
                        >
                            <span class="block text-sm font-semibold text-gray-900">{{ exercise.name }}</span>
                            <span class="mt-1 inline-flex items-center gap-2">
                                <span class="block text-xs text-gray-500">{{ formatCategory(exercise.category) }}</span>
                                <span
                                    class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                    :class="exercise.source === 'custom' ? 'bg-violet-100 text-violet-700' : 'bg-blue-100 text-blue-700'"
                                >
                                    {{ exercise.source === 'custom' ? 'Personalizado' : 'RFESS' }}
                                </span>
                            </span>
                        </button>

                        <div v-if="filteredExercises.length === 0" class="p-4 text-sm text-gray-500">
                            No hay ejercicios que coincidan con los filtros actuales.
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
    </GeneralLayout>
</template>


