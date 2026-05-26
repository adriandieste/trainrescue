<script setup>
import axios from 'axios';
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps({
    tests: {
        type: Array,
        default: () => [],
    },
    canEdit: {
        type: Boolean,
        default: true,
    },
});

const TIME_PATTERN = /^\d{1,2}:\d{2}\.\d{2}$/;

const rows = ref([]);
const editingTestId = ref(null);
const savingTestId = ref(null);
const drafts = reactive({});
const rowErrors = reactive({});
const toast = ref({ show: false, type: 'success', message: '' });

function mapTests(tests) {
    return tests.map((test) => ({
        ...test,
        personal_best: test.personal_best ? { ...test.personal_best } : null,
    }));
}

watch(
    () => props.tests,
    (tests) => {
        rows.value = mapTests(tests ?? []);
    },
    { immediate: true, deep: true },
);

const hasRows = computed(() => rows.value.length > 0);

function showToast(message, type = 'success') {
    toast.value = { show: true, message, type };
    window.clearTimeout(showToast.timeoutId);
    showToast.timeoutId = window.setTimeout(() => {
        toast.value.show = false;
    }, 2500);
}

function normalizeChronoInput(value) {
    const digits = String(value ?? '').replace(/\D/g, '').slice(0, 6);

    if (digits.length <= 2) {
        return digits;
    }

    if (digits.length <= 4) {
        return `${digits.slice(0, digits.length - 2)}:${digits.slice(-2)}`;
    }

    return `${digits.slice(0, digits.length - 4)}:${digits.slice(-4, -2)}.${digits.slice(-2)}`;
}

function beginEdit(row) {
    editingTestId.value = row.id;
    drafts[row.id] = row.personal_best?.time ?? '';
    rowErrors[row.id] = '';
}

function cancelEdit(rowId) {
    editingTestId.value = null;
    drafts[rowId] = '';
    rowErrors[rowId] = '';
}

function updateDraft(rowId, value) {
    drafts[rowId] = normalizeChronoInput(value);
    rowErrors[rowId] = '';
}

async function saveRow(row) {
    const draft = String(drafts[row.id] ?? '').trim();

    if (!TIME_PATTERN.test(draft)) {
        rowErrors[row.id] = 'Usa el formato MM:SS.mm';
        return;
    }

    savingTestId.value = row.id;
    rowErrors[row.id] = '';

    try {
        const { data } = await axios.patch(route('profile.personal-bests.update', row.id), {
            time: draft,
        });

        const targetRow = rows.value.find((item) => item.id === row.id);
        if (targetRow) {
            targetRow.personal_best = data.personal_best;
        }

        editingTestId.value = null;
        showToast(data.message ?? 'Marca actualizada.', 'success');
    } catch (error) {
        rowErrors[row.id] = error.response?.data?.errors?.time?.[0]
            ?? error.response?.data?.message
            ?? 'No se pudo guardar la marca.';
        showToast('No se pudo actualizar la marca.', 'error');
    } finally {
        savingTestId.value = null;
    }
}
</script>

<template>
    <div class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h3 class="text-xl font-bold text-neutral-900 dark:text-zinc-100">Marcas personales</h3>
                <p class="text-sm text-neutral-600 dark:text-zinc-400">
                    Registra tu mejor marca en cada prueba oficial con formato cronómetro.
                </p>
            </div>
            <p class="text-xs font-semibold uppercase tracking-wide text-neutral-500 dark:text-zinc-500">
                Formato: MM:SS.mm
            </p>
        </div>

        <div v-if="!hasRows" class="mt-4 rounded-xl border border-dashed border-neutral-300 p-4 text-sm text-neutral-500 dark:border-zinc-700 dark:text-zinc-500">
            No hay pruebas configuradas todavía.
        </div>

        <div v-else class="mt-5 overflow-x-auto rounded-2xl border border-neutral-200 dark:border-zinc-800">
            <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-800">
                <thead class="bg-neutral-50 dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-neutral-500 dark:text-zinc-400">Prueba</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-neutral-500 dark:text-zinc-400">Distancia / Estructura</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-neutral-500 dark:text-zinc-400">Mejor marca personal</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-neutral-500 dark:text-zinc-400">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200 bg-white dark:divide-zinc-800 dark:bg-zinc-950">
                    <tr v-for="row in rows" :key="row.id" class="align-top">
                        <td class="px-4 py-4">
                            <p class="text-sm font-semibold text-neutral-900 dark:text-zinc-100">{{ row.name }}</p>
                        </td>
                        <td class="px-4 py-4 text-sm text-neutral-600 dark:text-zinc-400">
                            {{ row.structure }}
                        </td>
                        <td class="px-4 py-4">
                            <div v-if="editingTestId === row.id" class="max-w-[180px] space-y-2">
                                <input
                                    :value="drafts[row.id]"
                                    type="text"
                                    inputmode="numeric"
                                    placeholder="00:00.00"
                                    class="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm text-neutral-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-300 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                                    @input="updateDraft(row.id, $event.target.value)"
                                >
                                <p v-if="rowErrors[row.id]" class="text-xs font-medium text-red-600 dark:text-red-400">
                                    {{ rowErrors[row.id] }}
                                </p>
                            </div>
                            <div v-else>
                                <p class="text-sm font-semibold text-neutral-900 dark:text-zinc-100">
                                    {{ row.personal_best?.time ?? 'Sin registrar' }}
                                </p>
                                <p v-if="row.personal_best?.recorded_at" class="mt-1 text-xs text-neutral-500 dark:text-zinc-500">
                                    Actualizado: {{ row.personal_best.recorded_at }}
                                </p>
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <div v-if="canEdit && row.can_edit" class="flex justify-end gap-2">
                                <template v-if="editingTestId === row.id">
                                    <button
                                        type="button"
                                        class="rounded-lg border border-neutral-300 px-3 py-2 text-xs font-semibold text-neutral-700 transition hover:bg-neutral-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800"
                                        :disabled="savingTestId === row.id"
                                        @click="cancelEdit(row.id)"
                                    >
                                        Cancelar
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-700 disabled:opacity-60"
                                        :disabled="savingTestId === row.id"
                                        @click="saveRow(row)"
                                    >
                                        {{ savingTestId === row.id ? 'Guardando...' : 'Guardar' }}
                                    </button>
                                </template>
                                <button
                                    v-else
                                    type="button"
                                    class="inline-flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 transition hover:bg-blue-100 dark:border-blue-900/30 dark:bg-blue-900/20 dark:text-blue-400 dark:hover:bg-blue-900/30"
                                    @click="beginEdit(row)"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232 18.768 8.768M9 11l6.768-6.768a2.5 2.5 0 1 1 3.536 3.536L12.536 14.536A4 4 0 0 1 10.8 15.56L7 17l1.44-3.8a4 4 0 0 1 1.024-1.736Z" />
                                    </svg>
                                    Editar
                                </button>
                            </div>
                            <span v-else class="block text-right text-xs font-medium text-neutral-400 dark:text-zinc-500">Solo lectura</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-2"
        >
            <div
                v-if="toast.show"
                class="fixed bottom-4 right-4 z-50 max-w-sm rounded-xl border px-4 py-3 shadow-lg"
                :class="toast.type === 'success'
                    ? 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900/30 dark:bg-emerald-900/20 dark:text-emerald-400'
                    : 'border-red-200 bg-red-50 text-red-800 dark:border-red-900/30 dark:bg-red-900/20 dark:text-red-400'"
            >
                <p class="text-sm font-semibold">{{ toast.message }}</p>
            </div>
        </transition>
    </div>
</template>


















