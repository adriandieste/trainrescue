<script setup>
import GeneralLayout from '@/Layouts/GeneralLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
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

function formatCategory(category) {
    return category.charAt(0).toUpperCase() + category.slice(1);
}
</script>

<template>
    <Head title="Biblioteca de ejercicios" />

    <GeneralLayout>
        <div class="space-y-6">
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
                            <span class="mt-1 block text-xs text-gray-500">{{ formatCategory(exercise.category) }}</span>
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
                            <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">
                                {{ formatCategory(selectedExercise.category) }}
                            </span>
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
                    </div>

                    <div v-else class="p-10 text-center text-sm text-gray-500">
                        Selecciona un ejercicio para ver su detalle tecnico.
                    </div>
                </div>
            </div>
        </div>
    </GeneralLayout>
</template>


