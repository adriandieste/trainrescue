<script setup>
import GeneralLayout from '@/Layouts/GeneralLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
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

const showCreateForm = ref(false);

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
                        <div class="mb-4 flex items-center justify-between">
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
    </GeneralLayout>
</template>


