<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
    club: {
        type: Object,
        default: () => ({}),
    },
    filters: {
        type: Object,
        default: () => ({
            view_mode: 'ranking',
            test_id: null,
            athlete_id: null,
            group_id: 'all',
        }),
    },
    options: {
        type: Object,
        default: () => ({
            age_categories: [],
            groups: [],
            tests: [],
            athletes: [],
        }),
    },
    records: {
        type: Array,
        default: () => [],
    },
});

const viewMode = ref(props.filters.view_mode ?? 'ranking');
const selectedTestId = ref(props.filters.test_id ? String(props.filters.test_id) : null);
const selectedAthleteId = ref(props.filters.athlete_id ? String(props.filters.athlete_id) : null);
const selectedGroupId = ref(props.filters.group_id ? String(props.filters.group_id) : 'all');

const tests = computed(() => props.options.tests ?? []);
const athletes = computed(() => props.options.athletes ?? []);
const groups = computed(() => props.options.groups ?? []);

const filteredAthletes = computed(() => athletes.value.filter((athlete) => selectedGroupId.value === 'all'
    || (athlete.group_ids ?? []).map(String).includes(String(selectedGroupId.value))));

const selectedTest = computed(() => tests.value.find((test) => String(test.id) === String(selectedTestId.value)) ?? null);
const selectedAthlete = computed(() => filteredAthletes.value.find((athlete) => String(athlete.id) === String(selectedAthleteId.value)) ?? null);

function medalInfo(rank) {
    if (rank === 1) {
        return { label: 'Oro', className: 'border-amber-200 bg-amber-100 text-amber-800' };
    }

    if (rank === 2) {
        return { label: 'Plata', className: 'border-slate-200 bg-slate-100 text-slate-700' };
    }

    if (rank === 3) {
        return { label: 'Bronce', className: 'border-orange-200 bg-orange-100 text-orange-800' };
    }

    return null;
}

function formatGroupNames(athlete) {
    const names = athlete?.group_names ?? [];
    return names.length > 0 ? names.join(' - ') : 'Sin grupo';
}

const rankingRows = computed(() => {
    if (viewMode.value !== 'ranking' || !selectedTest.value) {
        return [];
    }

    return props.records
        .filter((record) => String(record.test?.id) === String(selectedTest.value.id))
        .filter((record) => {
            if (selectedGroupId.value === 'all') {
                return true;
            }

            return (record.athlete?.group_ids ?? []).map(String).includes(String(selectedGroupId.value));
        })
        .slice()
        .sort((left, right) => left.time_centiseconds - right.time_centiseconds || left.athlete.name.localeCompare(right.athlete.name))
        .map((record, index) => ({
            ...record,
            rank: index + 1,
            medal: medalInfo(index + 1),
        }));
});

const athleteRows = computed(() => {
    if (viewMode.value !== 'athlete' || !selectedAthlete.value) {
        return [];
    }

    const athleteBests = props.records
        .filter((record) => String(record.athlete?.id) === String(selectedAthlete.value.id))
        .reduce((carry, record) => {
            carry.set(String(record.test.id), record);
            return carry;
        }, new Map());

    return tests.value.map((test) => {
        const record = athleteBests.get(String(test.id)) ?? null;

        return {
            ...test,
            personal_best: record ? {
                id: record.id,
                time: record.time,
                time_centiseconds: record.time_centiseconds,
                recorded_at: record.recorded_at,
            } : null,
        };
    });
});

watch([filteredAthletes, tests], () => {
    if (filteredAthletes.value.length > 0 && !filteredAthletes.value.some((athlete) => String(athlete.id) === String(selectedAthleteId.value))) {
        selectedAthleteId.value = String(filteredAthletes.value[0].id);
    }

    if (tests.value.length > 0 && !tests.value.some((test) => String(test.id) === String(selectedTestId.value))) {
        selectedTestId.value = String(tests.value[0].id);
    }

    if (filteredAthletes.value.length === 0) {
        selectedAthleteId.value = null;
    }

    if (tests.value.length === 0) {
        selectedTestId.value = null;
    }
}, { immediate: true });

watch(viewMode, (mode) => {
    if (mode === 'ranking' && !selectedTestId.value && tests.value.length > 0) {
        selectedTestId.value = String(tests.value[0].id);
    }

    if (mode === 'athlete' && !selectedAthleteId.value && filteredAthletes.value.length > 0) {
        selectedAthleteId.value = String(filteredAthletes.value[0].id);
    }
}, { immediate: true });
</script>

<template>
    <div class="space-y-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-slate-500">Vista</p>
                    <div class="mt-3 inline-flex rounded-2xl border border-slate-200 bg-slate-50 p-1">
                        <button
                            type="button"
                            class="rounded-xl px-4 py-2 text-sm font-semibold transition"
                            :class="viewMode === 'ranking' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                            @click="viewMode = 'ranking'"
                        >
                            Ranking por prueba
                        </button>
                        <button
                            type="button"
                            class="rounded-xl px-4 py-2 text-sm font-semibold transition"
                            :class="viewMode === 'athlete' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                            @click="viewMode = 'athlete'"
                        >
                            Vista por socorrista
                        </button>
                    </div>
                </div>

                <div class="grid gap-3 md:grid-cols-2 xl:min-w-[38rem]">
                    <label class="space-y-1 text-sm">
                        <span class="font-semibold text-slate-700">Grupo de entrenamiento</span>
                        <select v-model="selectedGroupId" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                            <option value="all">Todos</option>
                            <option v-for="group in groups" :key="group.id" :value="String(group.id)">
                                {{ group.name }}
                            </option>
                        </select>
                    </label>

                    <label v-if="viewMode === 'ranking'" class="space-y-1 text-sm">
                        <span class="font-semibold text-slate-700">Prueba oficial</span>
                        <select v-model="selectedTestId" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                            <option v-for="test in tests" :key="test.id" :value="String(test.id)">
                                {{ test.name }}
                            </option>
                        </select>
                    </label>

                    <label v-else class="space-y-1 text-sm">
                        <span class="font-semibold text-slate-700">Socorrista</span>
                        <select v-model="selectedAthleteId" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                            <option v-for="athlete in filteredAthletes" :key="athlete.id" :value="String(athlete.id)">
                                {{ athlete.name }}
                            </option>
                        </select>
                    </label>
                </div>
            </div>
        </section>

        <section v-if="viewMode === 'ranking'" class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 p-6">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-3xl font-bold text-slate-900">Ranking {{ selectedTest?.name ?? 'sin prueba seleccionada' }}</h2>
                    </div>
                    <div class="rounded-full bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700">
                        {{ rankingRows.length }} marca(s) visibles
                    </div>
                </div>
            </div>

            <div v-if="rankingRows.length === 0" class="p-6 text-sm text-slate-500">
                No hay marcas que coincidan con los filtros actuales.
            </div>

            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3 text-left">Posicion</th>
                            <th class="px-4 py-3 text-left">Socorrista</th>
                            <th class="px-4 py-3 text-left">Grupo</th>
                            <th class="px-4 py-3 text-left">Marca</th>
                            <th class="px-4 py-3 text-left">Registrada</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        <tr
                            v-for="row in rankingRows"
                            :key="row.id"
                            class="align-top"
                            :class="row.rank <= 3 ? 'bg-amber-50/50' : ''"
                        >
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-900 text-sm font-bold text-white">{{ row.rank }}</span>
                                    <span
                                        v-if="row.medal"
                                        class="inline-flex rounded-full border px-2 py-1 text-[11px] font-bold uppercase tracking-wide"
                                        :class="row.medal.className"
                                    >
                                        {{ row.medal.label }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-full bg-blue-100 text-sm font-bold text-blue-700">
                                        <img v-if="row.athlete.avatar_url" :src="row.athlete.avatar_url" :alt="row.athlete.name" class="h-full w-full object-cover">
                                        <span v-else>{{ row.athlete.name.slice(0, 1).toUpperCase() }}</span>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-900">{{ row.athlete.name }}</p>
                                        <p class="text-xs text-slate-500">{{ formatGroupNames(row.athlete) }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-sm text-slate-600">{{ formatGroupNames(row.athlete) }}</td>
                            <td class="px-4 py-4 font-semibold text-slate-900">{{ row.time }}</td>
                            <td class="px-4 py-4 text-sm text-slate-600">{{ row.recorded_at ?? 'Sin fecha' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section v-else class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 p-6">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900">Historial de {{ selectedAthlete?.name ?? 'socorrista' }}</h2>
                        <p class="mt-1 text-sm text-slate-600">Todas las pruebas oficiales con la mejor marca registrada para el deportista seleccionado.</p>
                    </div>
                    <div class="rounded-full bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700">
                        {{ athleteRows.length }} prueba(s)
                    </div>
                </div>
            </div>

            <div v-if="!selectedAthlete" class="p-6 text-sm text-slate-500">
                No hay socorristas disponibles con los filtros actuales.
            </div>

            <div v-else-if="athleteRows.length === 0" class="p-6 text-sm text-slate-500">
                No hay pruebas oficiales configuradas todavia.
            </div>

            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3 text-left">Prueba</th>
                            <th class="px-4 py-3 text-left">Estructura</th>
                            <th class="px-4 py-3 text-left">Marca personal</th>
                            <th class="px-4 py-3 text-left">Registrada</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        <tr v-for="row in athleteRows" :key="row.id">
                            <td class="px-4 py-4 font-semibold text-slate-900">{{ row.name }}</td>
                            <td class="px-4 py-4 text-sm text-slate-600">{{ row.structure }}</td>
                            <td class="px-4 py-4 font-semibold text-slate-900">{{ row.personal_best?.time ?? 'Sin registrar' }}</td>
                            <td class="px-4 py-4 text-sm text-slate-600">{{ row.personal_best?.recorded_at ?? '-' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>





