<script setup>
import { computed } from 'vue';

const props = defineProps({
    exercise: {
        type: Object,
        default: null,
    },
    showEditForm: {
        type: Boolean,
        default: false,
    },
    editFormData: {
        type: Object,
        required: true,
    },
    editFormErrors: {
        type: Object,
        default: () => ({}),
    },
    isSubmitting: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['edit', 'delete', 'update:showEditForm', 'submit-edit', 'cancel-edit']);

function formatCategory(category) {
    return category.charAt(0).toUpperCase() + category.slice(1);
}

const hasErrors = computed(() => Object.keys(props.editFormErrors).length > 0);
</script>

<template>
    <div v-if="exercise" class="space-y-6">
        <!-- Header -->
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">{{ exercise.name }}</h2>
                <div class="mt-2 flex gap-2">
                    <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">
                        {{ formatCategory(exercise.category) }}
                    </span>
                    <span
                        class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                        :class="exercise.source === 'custom' ? 'bg-violet-100 text-violet-700' : 'bg-slate-100 text-slate-700'"
                    >
                        {{ exercise.source === 'custom' ? 'Ejercicio personalizado' : 'Plantilla RFESS' }}
                    </span>
                </div>
            </div>
            <div v-if="exercise.can_edit || exercise.can_delete" class="flex gap-2">
                <button
                    v-if="exercise.can_edit"
                    type="button"
                    class="inline-flex items-center rounded-lg border border-blue-300 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-100"
                    @click="emit('update:showEditForm', !showEditForm)"
                >
                    {{ showEditForm ? 'Cancelar' : 'Editar' }}
                </button>
                <button
                    v-if="exercise.can_delete"
                    type="button"
                    class="inline-flex items-center rounded-lg border border-red-300 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-100"
                    @click="emit('delete')"
                >
                    Eliminar
                </button>
            </div>
        </div>

        <!-- Edit Form -->
        <form
            v-if="showEditForm && exercise.can_edit"
            class="grid gap-4 rounded-lg border border-blue-100 bg-blue-50 p-4 md:grid-cols-2"
            @submit.prevent="emit('submit-edit')"
        >
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Nombre *</label>
                <input
                    :value="editFormData.name"
                    type="text"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                    @input="$emit('update:editFormData', { ...editFormData, name: $event.target.value })"
                >
                <p v-if="editFormErrors.name" class="mt-1 text-xs text-red-600">{{ editFormErrors.name }}</p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Enlace de video (opcional)</label>
                <input
                    :value="editFormData.video_url"
                    type="url"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                    @input="$emit('update:editFormData', { ...editFormData, video_url: $event.target.value })"
                >
                <p v-if="editFormErrors.video_url" class="mt-1 text-xs text-red-600">{{ editFormErrors.video_url }}</p>
            </div>

            <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-medium text-gray-700">Descripción técnica *</label>
                <textarea
                    :value="editFormData.description"
                    rows="3"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                    @input="$emit('update:editFormData', { ...editFormData, description: $event.target.value })"
                />
                <p v-if="editFormErrors.description" class="mt-1 text-xs text-red-600">{{ editFormErrors.description }}</p>
            </div>

            <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-medium text-gray-700">Material necesario</label>
                <textarea
                    :value="editFormData.materials"
                    rows="2"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                    @input="$emit('update:editFormData', { ...editFormData, materials: $event.target.value })"
                />
                <p v-if="editFormErrors.materials" class="mt-1 text-xs text-red-600">{{ editFormErrors.materials }}</p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Series por defecto</label>
                <input
                    :value="editFormData.default_sets"
                    type="number"
                    min="1"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                    @input="$emit('update:editFormData', { ...editFormData, default_sets: $event.target.value })"
                >
                <p v-if="editFormErrors.default_sets" class="mt-1 text-xs text-red-600">{{ editFormErrors.default_sets }}</p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Metros por defecto</label>
                <input
                    :value="editFormData.default_meters"
                    type="number"
                    min="1"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                    @input="$emit('update:editFormData', { ...editFormData, default_meters: $event.target.value })"
                >
                <p v-if="editFormErrors.default_meters" class="mt-1 text-xs text-red-600">{{ editFormErrors.default_meters }}</p>
            </div>

            <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-medium text-gray-700">Descanso por defecto (s)</label>
                <input
                    :value="editFormData.default_rest_seconds"
                    type="number"
                    min="0"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                    @input="$emit('update:editFormData', { ...editFormData, default_rest_seconds: $event.target.value })"
                >
                <p v-if="editFormErrors.default_rest_seconds" class="mt-1 text-xs text-red-600">{{ editFormErrors.default_rest_seconds }}</p>
            </div>

            <div class="md:col-span-2 flex justify-end gap-2">
                <button
                    type="button"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                    @click="emit('cancel-edit')"
                >
                    Cancelar
                </button>
                <button
                    type="submit"
                    :disabled="isSubmitting || hasErrors"
                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700 disabled:opacity-50"
                >
                    <span v-if="isSubmitting">Guardando...</span>
                    <span v-else>Guardar cambios</span>
                </button>
            </div>
        </form>

        <!-- Description -->
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-600">Descripción técnica</h3>
            <p class="mt-2 text-sm leading-relaxed text-gray-700">
                {{ exercise.technical_description }}
            </p>
        </div>

        <!-- Materials -->
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-600">Material necesario</h3>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-gray-700">
                <li v-for="material in exercise.materials" :key="material">{{ material }}</li>
                <li v-if="exercise.materials.length === 0" class="list-none text-gray-500">Sin material especificado.</li>
            </ul>
        </div>

        <!-- Video Link -->
        <div v-if="exercise.video_url" class="rounded-lg border border-gray-200 bg-gray-50 p-4">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-600">Enlace de video</h3>
            <a
                :href="exercise.video_url"
                target="_blank"
                rel="noopener noreferrer"
                class="mt-2 inline-flex items-center text-sm font-medium text-blue-700 hover:text-blue-800"
            >
                Ver recurso multimedia
            </a>
        </div>
    </div>
    <div v-else class="p-10 text-center text-sm text-gray-500">
        Selecciona un ejercicio para ver su detalle técnico.
    </div>
</template>
