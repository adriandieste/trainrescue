<script setup>
import { computed } from 'vue';

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false,
    },
    formData: {
        type: Object,
        required: true,
    },
    formErrors: {
        type: Object,
        default: () => ({}),
    },
    isSubmitting: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:isOpen', 'update:formData', 'submit']);

const realtimeErrors = computed(() => {
    const errors = {};

    if (!props.formData.name?.trim()) {
        errors.name = 'El nombre es obligatorio.';
    }

    if (!props.formData.description?.trim()) {
        errors.description = 'La descripción es obligatoria.';
    }

    if (props.formData.video_url && !/^https?:\/\//i.test(props.formData.video_url)) {
        errors.video_url = 'El enlace debe empezar por http:// o https://';
    }

    return errors;
});

const hasRealtimeErrors = computed(() => Object.keys(realtimeErrors.value).length > 0);
</script>

<template>
    <div v-show="isOpen" class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
        <div class="border-b border-gray-200 p-6">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Crear ejercicio personalizado</h2>
                <p class="mt-1 text-sm text-gray-600">Amplia tu biblioteca privada con ejercicios adaptados a tu metodología.</p>
            </div>
        </div>

        <form class="grid gap-4 p-6 md:grid-cols-2" @submit.prevent="emit('submit')">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Nombre *</label>
                <input
                    :value="formData.name"
                    type="text"
                    class="w-full rounded-lg border px-3 py-2 text-sm focus:ring-2"
                    :class="(formErrors.name || realtimeErrors.name) ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'"
                    placeholder="Ej: Circuito apnea + rescate"
                    @input="$emit('update:formData', { ...formData, name: $event.target.value })"
                >
                <p v-if="formErrors.name || realtimeErrors.name" class="mt-1 text-xs text-red-600">
                    {{ formErrors.name || realtimeErrors.name }}
                </p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Enlace de video (opcional)</label>
                <input
                    :value="formData.video_url"
                    type="url"
                    class="w-full rounded-lg border px-3 py-2 text-sm focus:ring-2"
                    :class="(formErrors.video_url || realtimeErrors.video_url) ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'"
                    placeholder="https://..."
                    @input="$emit('update:formData', { ...formData, video_url: $event.target.value })"
                >
                <p v-if="formErrors.video_url || realtimeErrors.video_url" class="mt-1 text-xs text-red-600">
                    {{ formErrors.video_url || realtimeErrors.video_url }}
                </p>
            </div>

            <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-medium text-gray-700">Descripción técnica *</label>
                <textarea
                    :value="formData.description"
                    rows="4"
                    class="w-full rounded-lg border px-3 py-2 text-sm focus:ring-2"
                    :class="(formErrors.description || realtimeErrors.description) ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'"
                    placeholder="Describe el objetivo y la ejecución técnica del ejercicio"
                    @input="$emit('update:formData', { ...formData, description: $event.target.value })"
                />
                <p v-if="formErrors.description || realtimeErrors.description" class="mt-1 text-xs text-red-600">
                    {{ formErrors.description || realtimeErrors.description }}
                </p>
            </div>

            <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-medium text-gray-700">Material necesario</label>
                <textarea
                    :value="formData.materials"
                    rows="2"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                    placeholder="Separado por coma o salto de línea (ej: maniquí, aletas, tubo)"
                    @input="$emit('update:formData', { ...formData, materials: $event.target.value })"
                />
                <p v-if="formErrors.materials" class="mt-1 text-xs text-red-600">{{ formErrors.materials }}</p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Series por defecto</label>
                <input
                    :value="formData.default_sets"
                    type="number"
                    min="1"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                    @input="$emit('update:formData', { ...formData, default_sets: $event.target.value })"
                >
                <p v-if="formErrors.default_sets" class="mt-1 text-xs text-red-600">{{ formErrors.default_sets }}</p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Metros por defecto</label>
                <input
                    :value="formData.default_meters"
                    type="number"
                    min="1"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                    @input="$emit('update:formData', { ...formData, default_meters: $event.target.value })"
                >
                <p v-if="formErrors.default_meters" class="mt-1 text-xs text-red-600">{{ formErrors.default_meters }}</p>
            </div>

            <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-medium text-gray-700">Descanso por defecto (s)</label>
                <input
                    :value="formData.default_rest_seconds"
                    type="number"
                    min="0"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                    @input="$emit('update:formData', { ...formData, default_rest_seconds: $event.target.value })"
                >
                <p v-if="formErrors.default_rest_seconds" class="mt-1 text-xs text-red-600">{{ formErrors.default_rest_seconds }}</p>
            </div>

            <div class="md:col-span-2 flex justify-end gap-2">
                <button
                    type="button"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                    @click="emit('update:isOpen', false)"
                >
                    Cancelar
                </button>
                <button
                    type="submit"
                    :disabled="isSubmitting || hasRealtimeErrors"
                    class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700 disabled:opacity-50"
                >
                    <span v-if="isSubmitting">Guardando...</span>
                    <span v-else>Guardar ejercicio personalizado</span>
                </button>
            </div>
        </form>
    </div>
</template>
