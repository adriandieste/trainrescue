<script setup>
defineProps({
    exercise: {
        type: Object,
        required: true,
    },
    isSelected: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['select', 'add-to-training', 'edit', 'delete']);

function formatCategory(category) {
    return category.charAt(0).toUpperCase() + category.slice(1);
}
</script>

<template>
    <article
        class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:shadow-md hover:border-gray-300"
        :class="{ 'ring-2 ring-blue-500 border-blue-500': isSelected }"
    >
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h3 class="truncate text-sm font-semibold text-gray-900">{{ exercise.name }}</h3>
                <p class="mt-1 text-xs text-gray-500">{{ formatCategory(exercise.category) }}</p>
            </div>
            <span
                class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold shrink-0"
                :class="exercise.source === 'custom' ? 'bg-violet-100 text-violet-700' : 'bg-blue-100 text-blue-700'"
            >
                {{ exercise.source === 'custom' ? 'Personalizado' : 'RFESS' }}
            </span>
        </div>

        <p class="mt-2 line-clamp-2 text-xs text-gray-600">
            {{ exercise.technical_description }}
        </p>

        <div class="mt-3 flex gap-2">
            <button
                type="button"
                class="flex-1 rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-[11px] font-semibold text-slate-700 transition hover:bg-slate-100"
                @click="$emit('select')"
            >
                Ver
            </button>
            <button
                type="button"
                class="flex-1 rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-[11px] font-semibold text-slate-700 transition hover:bg-slate-100"
                @click="$emit('add-to-training')"
            >
                Añadir
            </button>
            <div v-if="exercise.can_edit || exercise.can_delete" class="flex gap-1">
                <button
                    v-if="exercise.can_edit"
                    type="button"
                    class="rounded p-1.5 text-blue-600 hover:bg-blue-50"
                    title="Editar"
                    @click="$emit('edit')"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </button>
                <button
                    v-if="exercise.can_delete"
                    type="button"
                    class="rounded p-1.5 text-red-600 hover:bg-red-50"
                    title="Eliminar"
                    @click="$emit('delete')"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            </div>
        </div>
    </article>
</template>
