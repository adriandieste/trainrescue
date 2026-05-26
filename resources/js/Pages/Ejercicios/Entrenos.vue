<script setup>
import GeneralLayout from '@/Layouts/GeneralLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import ExerciseForm from './Partials/ExerciseForm.vue';
import ExerciseCard from './Partials/ExerciseCard.vue';
import ExerciseDetailPanel from './Partials/ExerciseDetailPanel.vue';

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
    plantillasGlobales: {
        type: Array,
        default: () => [],
    },
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
    editWorkoutId: {
        type: Number,
        default: null,
    },
});

const page = usePage();
const flash = computed(() => page.props.flash ?? {});
const hasClub = computed(() => props.hasClub);
const entrenamientos = computed(() => props.entrenamientos ?? []);
const plantillas = computed(() => props.plantillas ?? []);
const plantillasGlobales = computed(() => props.plantillasGlobales ?? []);
const calendarEvents = computed(() => props.calendarEvents ?? []);

const calendarView = ref('month');
const calendarCursor = ref(new Date());
const calendarDateFilter = ref('');
const calendarAthleteFilter = ref('all');
const calendarGroupFilter = ref('all');

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

const activeTab = ref('entrenos');
const entrenosSearch = ref('');
const entrenosScopeFilter = ref('all');
const plantillasSearch = ref('');
const plantillasVisibilityFilter = ref('all');
const plantillasOwnerFilter = ref('all');

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

const filteredEntrenamientos = computed(() => {
    const term = entrenosSearch.value.trim().toLowerCase();

    return entrenamientos.value.filter((entrenamiento) => {
        const matchesTerm = !term || String(entrenamiento.title ?? '').toLowerCase().includes(term);
        if (!matchesTerm) {
            return false;
        }

        if (entrenosScopeFilter.value === 'all') {
            return true;
        }

        return entrenamiento.target_scope === entrenosScopeFilter.value;
    });
});

const filteredPlantillas = computed(() => {
    const term = plantillasSearch.value.trim().toLowerCase();
    const own = (plantillas.value ?? []).map((item) => ({ ...item, owner_type: 'mine' }));
    const community = (plantillasGlobales.value ?? []).map((item) => ({ ...item, owner_type: 'community' }));

    return [...own, ...community].filter((plantilla) => {
        const matchesTerm = !term || String(plantilla.title ?? '').toLowerCase().includes(term);
        if (!matchesTerm) {
            return false;
        }

        if (plantillasVisibilityFilter.value === 'public' && !plantilla.is_public) {
            return false;
        }

        if (plantillasVisibilityFilter.value === 'private' && plantilla.is_public) {
            return false;
        }

        if (plantillasOwnerFilter.value === 'mine' && plantilla.owner_type !== 'mine') {
            return false;
        }

        if (plantillasOwnerFilter.value === 'community' && plantilla.owner_type !== 'community') {
            return false;
        }

        return true;
    });
});

const filteredPlantillasPropias = computed(() =>
    filteredPlantillas.value.filter((item) => item.owner_type === 'mine')
);

const filteredPlantillasComunidad = computed(() =>
    filteredPlantillas.value.filter((item) => item.owner_type === 'community')
);

const entrenamientoForm = useForm({
    title: '',
    workout_date: '',
    target_scope: hasClub.value ? 'club' : 'personal',
    is_template: false,
    is_public: false,
    assigned_user_ids: [],
    exercises: [],
});

const draggedTrainingIndex = ref(null);
const showCreateTrainingForm = ref(false);
const editingTrainingId = ref(null);
const trainingBuilderSectionRef = ref(null);

// Group management
const showCreateGroupModal = ref(false);
const newGroupName = ref('');
const isCreatingGroup = ref(false);
const availableGroups = ref(props.groups);
const groupNoticeType = ref(null);
const groupNoticeMessage = ref('');

function setGroupNotice(type, message) {
    groupNoticeType.value = type;
    groupNoticeMessage.value = message;
}

function clearGroupNotice() {
    groupNoticeType.value = null;
    groupNoticeMessage.value = '';
}

function scrollToTrainingBuilder() {
    if (typeof window === 'undefined') {
        return;
    }

    window.requestAnimationFrame(() => {
        if (trainingBuilderSectionRef.value?.scrollIntoView) {
            trainingBuilderSectionRef.value.scrollIntoView({ behavior: 'smooth', block: 'start' });
            return;
        }

        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
}

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

    if (entrenamientoForm.target_scope === 'grupo' && entrenamientoForm.assigned_user_ids.length === 0) {
        return true;
    }

    return entrenamientoForm.exercises.some((item) => !item.sets || item.sets < 1);
});

function sourceLabel(source) {
    return source === 'custom' ? 'Personalizado' : 'RFESS';
}

function resetTrainingForm() {
    entrenamientoForm.reset('title', 'workout_date', 'target_scope', 'is_template', 'is_public', 'assigned_user_ids', 'exercises');
    entrenamientoForm.clearErrors();
    entrenamientoForm.target_scope = hasClub.value ? 'club' : 'personal';
    entrenamientoForm.is_template = false;
    entrenamientoForm.is_public = false;
    entrenamientoForm.assigned_user_ids = [];
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
    activeTab.value = 'entrenos';
    showCreateTrainingForm.value = true;
    editingTrainingId.value = entrenamiento.id;
    entrenamientoForm.clearErrors();
    entrenamientoForm.title = entrenamiento.title ?? '';
    entrenamientoForm.workout_date = entrenamiento.workout_date ?? '';
    entrenamientoForm.target_scope = entrenamiento.target_scope ?? (hasClub.value ? 'club' : 'personal');
    entrenamientoForm.is_template = Boolean(entrenamiento.is_template);
    entrenamientoForm.is_public = Boolean(entrenamiento.is_public);
    entrenamientoForm.assigned_user_ids = entrenamiento.assigned_user_ids ?? [];
    entrenamientoForm.exercises = (entrenamiento.exercises ?? []).map((item) => ({
        source: item.source,
        exercise_id: item.exercise_id,
        name: item.name,
        sets: item.sets ?? 3,
        meters: item.meters ?? null,
        rest_seconds: item.rest_seconds ?? 45,
    }));

    scrollToTrainingBuilder();
}

function openTrainingEditorById(trainingId) {
    if (!trainingId) {
        return;
    }

    const toEdit = [...entrenamientos.value, ...plantillas.value]
        .find((item) => Number(item.id) === Number(trainingId) && item.can_edit);

    if (toEdit) {
        openTrainingEditor(toEdit);
        return;
    }

    router.get(route('exercises.library'), { edit_workout_id: Number(trainingId) }, {
        preserveScroll: false,
    });
}

function openCalendarEvent(event) {
    if (!event?.can_edit) {
        return;
    }

    openTrainingEditorById(event.id);
}

watch(() => props.editWorkoutId, (trainingId) => {
    openTrainingEditorById(trainingId);
}, { immediate: true });

watch(calendarDateFilter, (dateKey) => {
    if (!dateKey) {
        return;
    }

    calendarCursor.value = parseDateKey(dateKey);
});

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
    activeTab.value = 'entrenos';
    showCreateTrainingForm.value = true;
    editingTrainingId.value = null;
    entrenamientoForm.clearErrors();
    entrenamientoForm.title = template.title ?? '';
    entrenamientoForm.workout_date = '';
    entrenamientoForm.target_scope = template.target_scope === 'grupo'
        ? (hasClub.value ? 'club' : 'personal')
        : (template.target_scope ?? (hasClub.value ? 'club' : 'personal'));
    entrenamientoForm.is_template = false;
    entrenamientoForm.is_public = false;
    entrenamientoForm.assigned_user_ids = [];
    entrenamientoForm.exercises = (template.exercises ?? []).map((item) => ({
        source: item.source,
        exercise_id: item.exercise_id,
        name: item.name,
        sets: item.sets ?? 3,
        meters: item.meters ?? null,
        rest_seconds: item.rest_seconds ?? 45,
    }));

    scrollToTrainingBuilder();
}

function duplicateTraining(training) {
    scrollToTrainingBuilder();

    router.post(route('workouts.duplicate', training.id), {}, {
        preserveScroll: false,
        onSuccess: () => {
            scrollToTrainingBuilder();
        },
    });
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
        is_public: data.is_template ? Boolean(data.is_public) : false,
        workout_date: data.is_template ? null : data.workout_date,
        assigned_user_ids: data.target_scope === 'grupo' ? data.assigned_user_ids : [],
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
        return;
    }

    entrenamientoForm.is_public = false;
});

watch(() => entrenamientoForm.target_scope, (scope) => {
    if (scope !== 'grupo') {
        entrenamientoForm.assigned_user_ids = [];
    }
});

function openCreateGroupModal() {
    newGroupName.value = '';
    clearGroupNotice();
    showCreateGroupModal.value = true;
}

function closeCreateGroupModal() {
    showCreateGroupModal.value = false;
    newGroupName.value = '';
}

async function submitCreateGroup() {
    if (!newGroupName.value.trim()) {
        setGroupNotice('error', 'Por favor ingresa un nombre para el grupo.');
        return;
    }

    if (entrenamientoForm.assigned_user_ids.length === 0) {
        setGroupNotice('error', 'Por favor selecciona al menos un atleta.');
        return;
    }

    isCreatingGroup.value = true;
    clearGroupNotice();

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        if (!csrfToken) {
            setGroupNotice('error', 'No se encontró el token CSRF. Recarga la página e inténtalo de nuevo.');
            return;
        }

        const response = await fetch(route('clubs.groups.store'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                name: newGroupName.value.trim(),
                user_ids: entrenamientoForm.assigned_user_ids,
            }),
        });

        const contentType = response.headers.get('content-type') || '';
        const data = contentType.includes('application/json') ? await response.json() : null;

        if (response.ok && data) {
            availableGroups.value.push(data);
            setGroupNotice('success', `Grupo "${data.name}" creado exitosamente.`);
            closeCreateGroupModal();
            return;
        }

        if (response.status === 419) {
            setGroupNotice('error', 'Tu sesión expiró. Recarga la página e inténtalo de nuevo.');
            return;
        }

        const errorMsg = data?.error || data?.message || 'Error al crear el grupo.';
        setGroupNotice('error', errorMsg);
    } catch (error) {
        console.error('Error creating group:', error);
        setGroupNotice('error', 'Error de red al crear el grupo.');
    } finally {
        isCreatingGroup.value = false;
    }
}

function selectGroup(groupId) {
    const group = availableGroups.value.find((g) => g.id === groupId);
    if (group) {
        entrenamientoForm.assigned_user_ids = group.user_ids;
    }
}

function selectAllClubMembers() {
    entrenamientoForm.assigned_user_ids = props.clubMembers.map((m) => m.id);
}

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

function switchTab(tab) {
    activeTab.value = tab;
}

function openTemplateBuilder() {
    activeTab.value = 'entrenos';
    showCreateTrainingForm.value = true;
    entrenamientoForm.is_template = true;
    scrollToTrainingBuilder();
}

function formatCategory(category) {
    return category.charAt(0).toUpperCase() + category.slice(1);
}

function formatTargetScope(scope) {
    if (scope === 'club') {
        return 'Todo el club';
    }

    if (scope === 'grupo') {
        return 'Grupo';
    }

    return 'Personal';
}
</script>

<template>
    <Head title="Entrenos" />

    <GeneralLayout>
        <div class="space-y-6">
            <div
                v-if="flash.success"
                class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800 shadow-sm dark:border-green-900/30 dark:bg-green-900/20 dark:text-green-400"
            >
                {{ flash.success }}
            </div>

            <div
                v-if="flash.error"
                class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800 shadow-sm dark:border-red-900/30 dark:bg-red-900/20 dark:text-red-400"
            >
                {{ flash.error }}
            </div>

            <div
                v-if="groupNoticeMessage"
                class="rounded-lg border px-4 py-3 text-sm font-medium shadow-sm"
                :class="groupNoticeType === 'success'
                    ? 'border-green-200 bg-green-50 text-green-800 dark:border-green-900/30 dark:bg-green-900/20 dark:text-green-400'
                    : 'border-red-200 bg-red-50 text-red-800 dark:border-red-900/30 dark:bg-red-900/20 dark:text-red-400'"
            >
                {{ groupNoticeMessage }}
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-2 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                <div class="grid gap-2 sm:grid-cols-3">
                    <button
                        type="button"
                        class="rounded-lg px-4 py-2 text-sm font-semibold transition"
                        :class="activeTab === 'entrenos' ? 'bg-slate-800 text-white dark:bg-zinc-800' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800'"
                        @click="switchTab('entrenos')"
                    >
                        Entrenos
                    </button>
                    <button
                        type="button"
                        class="rounded-lg px-4 py-2 text-sm font-semibold transition"
                        :class="activeTab === 'ejercicios' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800'"
                        @click="switchTab('ejercicios')"
                    >
                        Ejercicios
                    </button>
                    <button
                        type="button"
                        class="rounded-lg px-4 py-2 text-sm font-semibold transition"
                        :class="activeTab === 'plantillas' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800'"
                        @click="switchTab('plantillas')"
                    >
                        Plantillas
                    </button>
                </div>
            </div>

            <div v-show="activeTab === 'entrenos' && showCreateTrainingForm" ref="trainingBuilderSectionRef" class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:border dark:border-zinc-800 dark:bg-zinc-950">
                <div class="border-b border-gray-200 p-6 dark:border-zinc-800">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-zinc-100">
                                {{ editingTrainingId ? 'Editar entrenamiento' : 'Crear entrenamiento o plantilla' }}
                            </h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-zinc-400">Configura sesiones con ejercicios RFESS/personalizados y guárdalas como entrenamiento o plantilla reutilizable.</p>
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

                <form class="space-y-4 p-6" @submit.prevent="submitTraining">
                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="md:col-span-2">
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-zinc-300">Titulo del entrenamiento *</label>
                            <input
                                v-model="entrenamientoForm.title"
                                type="text"
                                class="w-full rounded-lg border bg-white px-3 py-2 text-sm text-gray-900 focus:ring-2 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                                :class="entrenamientoForm.errors.title ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'"
                                placeholder="Ej: Entrenamiento de Resistencia"
                            >
                            <p v-if="entrenamientoForm.errors.title" class="mt-1 text-xs text-red-600">{{ entrenamientoForm.errors.title }}</p>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-zinc-300">Fecha {{ entrenamientoForm.is_template ? '(opcional)' : '*' }}</label>
                            <input
                                v-model="entrenamientoForm.workout_date"
                                type="date"
                                :disabled="entrenamientoForm.is_template"
                                class="w-full rounded-lg border bg-white px-3 py-2 text-sm text-gray-900 focus:ring-2 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                                :class="[
                                    entrenamientoForm.errors.workout_date ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500',
                                    entrenamientoForm.is_template ? 'cursor-not-allowed bg-gray-100 text-gray-500 dark:bg-zinc-800 dark:text-zinc-500' : '',
                                ]"
                            >
                            <p v-if="entrenamientoForm.errors.workout_date" class="mt-1 text-xs text-red-600">{{ entrenamientoForm.errors.workout_date }}</p>
                            <p v-if="entrenamientoForm.is_template" class="mt-1 text-xs text-gray-500 dark:text-zinc-500">Las plantillas no se asignan a una fecha concreta.</p>
                        </div>
                    </div>

                    <div class="rounded-lg border border-indigo-200 bg-indigo-50 p-4 dark:border-indigo-900/30 dark:bg-indigo-900/20">
                        <div class="flex items-center gap-4">
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input
                                    v-model="entrenamientoForm.is_template"
                                    type="checkbox"
                                    class="rounded border-indigo-300 text-indigo-600 focus:ring-indigo-500"
                                >
                                <span class="text-sm font-semibold text-indigo-900 dark:text-indigo-300">Guardar como plantilla</span>
                            </label>

                            <div class="h-6 w-px bg-indigo-200 dark:bg-indigo-900/30"></div>

                            <label class="inline-flex items-center gap-3" :class="{'opacity-50 pointer-events-none': !entrenamientoForm.is_template}">
                                <span class="text-xs font-semibold uppercase text-indigo-700 dark:text-indigo-400">Visibilidad:</span>
                                <input
                                    v-model="entrenamientoForm.is_public"
                                    type="checkbox"
                                    class="sr-only peer"
                                    :disabled="!entrenamientoForm.is_template"
                                >
                                <span class="h-5 w-9 rounded-full bg-slate-300 transition peer-focus:ring-2 peer-focus:ring-blue-300 peer-checked:bg-blue-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:h-4 after:w-4 after:rounded-full after:bg-white after:transition-all peer-checked:after:translate-x-full relative dark:bg-zinc-700 dark:after:bg-zinc-200"></span>
                                <span class="text-xs font-medium text-indigo-800 dark:text-indigo-300">{{ entrenamientoForm.is_public ? 'Pública' : 'Privada' }}</span>
                            </label>
                        </div>

                        <p class="mt-2 text-xs text-indigo-600 ml-7 dark:text-indigo-400">
                            {{ entrenamientoForm.is_template ? 'Configuración de plantilla activa.' : 'Marca "Guardar como plantilla" para gestionar su visibilidad.' }}
                        </p>
                    </div>

                    <div class="max-w-sm">
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-zinc-300">Guardar para *</label>
                        <select
                            v-model="entrenamientoForm.target_scope"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:focus:ring-blue-500/40"
                        >
                            <option value="personal">Mis alumnos personales</option>
                            <option v-if="hasClub" value="club">Todo mi club</option>
                            <option v-if="hasClub" value="grupo">Grupo específico del club</option>
                        </select>
                        <p v-if="entrenamientoForm.errors.target_scope" class="mt-1 text-xs text-red-600">{{ entrenamientoForm.errors.target_scope }}</p>
                    </div>

                    <!-- Selector de miembros del grupo -->
                    <div v-if="entrenamientoForm.target_scope === 'grupo' && hasClub" class="max-w-sm rounded-xl border border-purple-200 bg-purple-50 p-4 dark:border-purple-900/30 dark:bg-purple-900/20">
                        <div class="mb-3 flex flex-col gap-3">
                            <label class="text-sm font-semibold text-purple-900 dark:text-purple-300">
                                Usar grupo pre-guardado
                            </label>
                            <div v-if="availableGroups.length === 0" class="text-xs italic text-purple-600 dark:text-purple-400">
                                No hay grupos guardados aún.
                            </div>
                            <div v-else class="flex flex-wrap gap-2">
                                <button
                                    v-for="group in availableGroups"
                                    :key="group.id"
                                    type="button"
                                    class="rounded-full px-3 py-1 text-xs font-semibold transition"
                                    :class="JSON.stringify(entrenamientoForm.assigned_user_ids) === JSON.stringify(group.user_ids)
                                        ? 'bg-purple-600 text-white'
                                        : 'border border-purple-300 bg-white text-purple-700 hover:bg-purple-100 dark:border-purple-700 dark:bg-zinc-900 dark:text-purple-300 dark:hover:bg-purple-900/20'"
                                    @click="selectGroup(group.id)"
                                >
                                    {{ group.name }} ({{ group.member_count }})
                                </button>
                            </div>
                        </div>

                        <div class="border-t border-purple-200 pt-3 dark:border-purple-900/30">
                            <div class="mb-2 flex items-center justify-between gap-2">
                                <label class="text-sm font-semibold text-purple-900 dark:text-purple-300">
                                    Seleccionar atletas manualmente *
                                </label>
                                <button
                                    type="button"
                                    class="text-xs font-medium text-purple-700 hover:underline dark:text-purple-300"
                                    @click="openCreateGroupModal"
                                >
                                    Guardar como grupo
                                </button>
                            </div>
                            <div v-if="clubMembers.length === 0" class="rounded-lg border border-dashed border-purple-300 bg-white p-3 text-sm italic text-purple-600 dark:border-purple-700 dark:bg-zinc-900 dark:text-purple-300">
                                No hay otros miembros en tu club.
                            </div>
                            <div v-else class="max-h-44 overflow-y-auto space-y-1 rounded-lg border border-purple-200 bg-white p-2 dark:border-purple-700 dark:bg-zinc-900">
                                <label
                                    v-for="member in clubMembers"
                                    :key="member.id"
                                    class="flex cursor-pointer items-center gap-2 rounded px-2 py-1.5 transition hover:bg-purple-50 dark:hover:bg-purple-900/20"
                                >
                                    <input
                                        type="checkbox"
                                        :value="member.id"
                                        v-model="entrenamientoForm.assigned_user_ids"
                                        class="rounded border-gray-300 text-purple-600 focus:ring-purple-500 dark:border-zinc-700 dark:bg-zinc-900"
                                    >
                                    <span class="flex-1 text-sm text-gray-800 dark:text-zinc-200">{{ member.name }}</span>
                                    <span
                                        class="rounded-full px-1.5 py-0.5 text-[10px] font-semibold"
                                        :class="member.role_label === 'Entrenador' ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700'"
                                    >
                                        {{ member.role_label }}
                                    </span>
                                </label>
                            </div>
                            <div class="mt-2 flex items-center gap-1 text-xs">
                                <span
                                    class="font-medium"
                                    :class="entrenamientoForm.assigned_user_ids.length === 0 ? 'text-red-600' : 'text-green-700'"
                                >
                                    {{ entrenamientoForm.assigned_user_ids.length }}
                                    {{ entrenamientoForm.assigned_user_ids.length === 1 ? 'atleta seleccionado' : 'atletas seleccionados' }}
                                </span>
                            </div>
                            <p v-if="entrenamientoForm.errors.assigned_user_ids" class="mt-1 text-xs text-red-600">
                                {{ entrenamientoForm.errors.assigned_user_ids }}
                            </p>
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-zinc-800 dark:bg-zinc-900/60">
                        <div class="mb-3 flex items-center justify-between gap-2">
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-600 dark:text-zinc-400">Ejercicios del entrenamiento</h3>
                            <span class="text-xs text-gray-500 dark:text-zinc-500">{{ entrenamientoForm.exercises.length }} {{ entrenamientoForm.exercises.length === 1 ? 'ejercicio' : 'ejercicios' }}</span>
                        </div>

                        <div v-if="entrenamientoForm.exercises.length === 0" class="rounded-lg border border-dashed border-gray-300 bg-white p-4 text-sm text-gray-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-500">
                            Agrega ejercicios desde el aprartado de ejercicios para construir el entrenamiento.
                        </div>

                        <div v-else class="space-y-3">
                            <div
                                v-for="(item, index) in entrenamientoForm.exercises"
                                :key="`${item.source}-${item.exercise_id}-${index}`"
                                draggable="true"
                                class="rounded-lg border border-gray-200 bg-white p-3 transition-shadow dark:border-zinc-700 dark:bg-zinc-900"
                                :class="draggedTrainingIndex === index ? 'opacity-50 shadow-lg ring-2 ring-blue-300' : ''"
                                @dragstart="startTrainingDrag(index)"
                                @dragover.prevent
                                @drop.prevent="dropTrainingExercise(index)"
                                @dragend="draggedTrainingIndex = null"
                            >
                                <div class="mb-3 flex items-center justify-between gap-2">
                                    <!-- Drag handle + name -->
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="cursor-grab shrink-0 text-gray-300 hover:text-gray-500 dark:text-zinc-600 dark:hover:text-zinc-400" title="Arrastrar para reordenar">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M7 2a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 2zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 8zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 7 14zm6-8a2 2 0 1 0-.001-4.001A2 2 0 0 0 13 6zm0 2a2 2 0 1 0 .001 4.001A2 2 0 0 0 13 8zm0 6a2 2 0 1 0 .001 4.001A2 2 0 0 0 13 14z"/>
                                            </svg>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-gray-900 dark:text-zinc-100">{{ index + 1 }}. {{ item.name }}</p>
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
                                            class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-700 disabled:opacity-30 disabled:cursor-not-allowed dark:text-zinc-500 dark:hover:bg-zinc-800 dark:hover:text-zinc-300"
                                            title="Subir"
                                            @click="moveExerciseUp(index)"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg>
                                        </button>
                                        <button
                                            type="button"
                                            :disabled="index === entrenamientoForm.exercises.length - 1"
                                            class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-700 disabled:opacity-30 disabled:cursor-not-allowed dark:text-zinc-500 dark:hover:bg-zinc-800 dark:hover:text-zinc-300"
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
                                    <label class="text-xs font-medium text-gray-600 dark:text-zinc-400">
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
                                    <label class="text-xs font-medium text-gray-600 dark:text-zinc-400">
                                        Metros
                                        <input
                                            v-model.number="item.meters"
                                            type="number"
                                            min="1"
                                            placeholder="Opcional"
                                            class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-300"
                                        >
                                    </label>
                                    <label class="text-xs font-medium text-gray-600 dark:text-zinc-400">
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

            <div v-show="activeTab === 'entrenos'" class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:border dark:border-zinc-800 dark:bg-zinc-950">
                <div class="border-b border-gray-200 p-6 dark:border-zinc-800">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-zinc-100">Entrenos guardados</h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-zinc-400">Busca por titulo y filtra por destinatario para gestionar sesiones rapidamente.</p>
                        </div>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-900"
                            @click="toggleTrainingForm"
                        >
                            {{ showCreateTrainingForm ? 'Ocultar' : 'Crear' }}
                        </button>
                        <div class="grid w-full gap-3 sm:grid-cols-2 lg:max-w-2xl">
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-zinc-500">Buscar</label>
                                <input
                                    v-model="entrenosSearch"
                                    type="text"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-slate-500 focus:ring-2 focus:ring-slate-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                                    placeholder="Ej: Resistencia, Potencia..."
                                >
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-zinc-500">Destinatario</label>
                                <select
                                    v-model="entrenosScopeFilter"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-slate-500 focus:ring-2 focus:ring-slate-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                                >
                                    <option value="all">Todos</option>
                                    <option value="club">Todo el club</option>
                                    <option value="grupo">Grupo</option>
                                    <option value="personal">Personal</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <div v-if="filteredEntrenamientos.length === 0" class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-6 text-sm text-gray-600 dark:border-zinc-700 dark:bg-zinc-900/40 dark:text-zinc-400">
                        No hay entrenos que coincidan con los filtros actuales.
                    </div>

                    <div v-else class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        <article
                            v-for="entrenamiento in filteredEntrenamientos"
                            :key="`training-tab-${entrenamiento.id}`"
                            class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h3 class="truncate text-sm font-semibold text-gray-900 dark:text-zinc-100">{{ entrenamiento.title }}</h3>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-zinc-500">{{ entrenamiento.workout_date || 'Sin fecha' }} · {{ formatTargetScope(entrenamiento.target_scope) }}</p>
                                </div>
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-700 dark:bg-zinc-800 dark:text-zinc-300">
                                    {{ entrenamiento.exercises?.length ?? 0 }} ej.
                                </span>
                            </div>
                            <div class="mt-3 flex items-center gap-2">
                                <button
                                    v-if="entrenamiento.can_edit"
                                    type="button"
                                    class="rounded-md border border-slate-300 bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-700 hover:bg-slate-100 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800"
                                    @click="openTrainingEditor(entrenamiento)"
                                >
                                    Editar
                                </button>
                                <button
                                    type="button"
                                    class="rounded-md border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 hover:bg-emerald-100"
                                    @click="duplicateTraining(entrenamiento)"
                                >
                                    Duplicar
                                </button>
                            </div>
                        </article>
                    </div>
                </div>
            </div>

            <!-- Formulario crear ejercicio -->
            <ExerciseForm
                :isOpen="showCreateForm"
                :formData="form"
                :formErrors="form.errors"
                :isSubmitting="form.processing"
                @update:isOpen="showCreateForm = $event"
                @update:formData="Object.assign(form, $event)"
                @submit="submitCustomExercise"
            />

            <!-- Seccion ejercicios guardados -->
            <div v-show="activeTab === 'ejercicios'" class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:border dark:border-zinc-800 dark:bg-zinc-950">
                <div class="border-b border-gray-200 p-6 dark:border-zinc-800">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-zinc-100">Biblioteca de ejercicios</h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-zinc-400">Busca, filtra y gestiona tu catálogo de ejercicios para agregar a entrenamientos.</p>
                        </div>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700"
                            @click="showCreateForm = !showCreateForm"
                        >
                            {{ showCreateForm ? 'Ocultar' : 'Crear' }}
                        </button>
                    </div>

                    <!-- Filtros toolbar -->
                    <div class="mt-4 grid gap-3 md:grid-cols-4">
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-zinc-500">Origen</label>
                            <div class="flex gap-1">
                                <button
                                    type="button"
                                    class="flex-1 rounded-lg px-2.5 py-1.5 text-xs font-semibold transition"
                                    :class="selectedSource === 'all' ? 'bg-gray-800 text-white dark:bg-zinc-800' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800'"
                                    @click="selectedSource = 'all'"
                                >
                                    Todos
                                </button>
                                <button
                                    type="button"
                                    class="flex-1 rounded-lg px-2.5 py-1.5 text-xs font-semibold transition"
                                    :class="selectedSource === 'predefined' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800'"
                                    @click="selectedSource = 'predefined'"
                                >
                                    RFESS
                                </button>
                                <button
                                    type="button"
                                    class="flex-1 rounded-lg px-2.5 py-1.5 text-xs font-semibold transition"
                                    :class="selectedSource === 'custom' ? 'bg-violet-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800'"
                                    @click="selectedSource = 'custom'"
                                >
                                    Míos
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-zinc-500">Categoría</label>
                            <select
                                v-model="selectedCategory"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                            >
                                <option value="all">Todas</option>
                                <option v-for="category in categories" :key="category" :value="category">
                                    {{ formatCategory(category) }}
                                </option>
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-zinc-500">Buscar</label>
                            <input
                                v-model="search"
                                type="text"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                                placeholder="Nombre, descripción o material"
                            >
                        </div>
                    </div>
                </div>

                <!-- Grid de ejercicios -->
                <div class="p-6">
                    <div v-if="filteredExercises.length === 0" class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-6 text-sm text-gray-600 dark:border-zinc-700 dark:bg-zinc-900/40 dark:text-zinc-400">
                        No hay ejercicios que coincidan con los filtros actuales.
                    </div>

                    <div v-else class="grid gap-3 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        <ExerciseCard
                            v-for="exercise in filteredExercises"
                            :key="exercise.id"
                            :exercise="exercise"
                            :isSelected="selectedExerciseId === exercise.id"
                            @select="selectedExerciseId = exercise.id"
                            @add-to-training="addExerciseToTraining(exercise)"
                            @edit="selectedExerciseId = exercise.id; openEditForm()"
                            @delete="selectedExerciseId = exercise.id; confirmDeleteExercise()"
                        />
                    </div>
                </div>
            </div>

            <!-- Panel detalle ejercicio: modal solo en mobile -->
            <Teleport v-if="selectedExercise && activeTab === 'ejercicios'" to="body">
                <Transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="opacity-0"
                    enter-to-class="opacity-100"
                    leave-active-class="transition ease-in duration-150"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <!-- Overlay modal -->
                    <div class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm lg:hidden" @click="selectedExerciseId = null" />
                </Transition>

                <Transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="translate-x-full"
                    enter-to-class="translate-x-0"
                    leave-active-class="transition ease-in duration-150"
                    leave-from-class="translate-x-0"
                    leave-to-class="translate-x-full"
                >
                    <div class="fixed right-0 top-0 z-50 h-screen max-h-screen w-full overflow-y-auto bg-white shadow-xl sm:max-w-2xl md:max-w-3xl lg:hidden dark:bg-zinc-950">
                        <div class="sticky top-0 z-40 flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 lg:hidden dark:border-zinc-800 dark:bg-zinc-950">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-zinc-100">{{ selectedExercise.name }}</h2>
                            <button
                                type="button"
                                class="text-gray-400 hover:text-gray-600 dark:text-zinc-500 dark:hover:text-zinc-300"
                                @click="selectedExerciseId = null"
                            >
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <div class="p-6">
                            <ExerciseDetailPanel
                                :exercise="selectedExercise"
                                :showEditForm="showEditForm"
                                :editFormData="editForm"
                                :editFormErrors="editForm.errors"
                                :isSubmitting="editForm.processing"
                                @edit="showEditForm = !showEditForm"
                                @delete="confirmDeleteExercise"
                                @update:showEditForm="showEditForm = $event"
                                @submit-edit="submitEditExercise"
                                @cancel-edit="cancelEditForm"
                            />
                        </div>
                    </div>
                </Transition>
            </Teleport>

            <!-- Panel detalle ejercicio en desktop (inline para evitar espacios en blanco laterales) -->
            <div
                v-if="selectedExercise && activeTab === 'ejercicios'"
                class="hidden overflow-hidden bg-white shadow-sm sm:rounded-lg lg:block dark:border dark:border-zinc-800 dark:bg-zinc-950"
            >
                <div class="border-b border-gray-200 px-6 py-4 dark:border-zinc-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-zinc-100">Detalle del ejercicio</h2>
                </div>
                <div class="p-6">
                    <ExerciseDetailPanel
                        :exercise="selectedExercise"
                        :showEditForm="showEditForm"
                        :editFormData="editForm"
                        :editFormErrors="editForm.errors"
                        :isSubmitting="editForm.processing"
                        @edit="showEditForm = !showEditForm"
                        @delete="confirmDeleteExercise"
                        @update:showEditForm="showEditForm = $event"
                        @submit-edit="submitEditExercise"
                        @cancel-edit="cancelEditForm"
                    />
                </div>
            </div>

            <div v-show="activeTab === 'plantillas'" class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:border dark:border-zinc-800 dark:bg-zinc-950">
                <div class="border-b border-gray-200 p-6 dark:border-zinc-800">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-zinc-100">Plantillas</h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-zinc-400">Filtra por nombre, visibilidad y origen para reutilizar sesiones con rapidez.</p>
                        </div>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700"
                            @click="openTemplateBuilder"
                        >
                            Crear plantilla
                        </button>
                    </div>
                    <div class="mt-4 grid gap-3 md:grid-cols-3">
                        <input
                            v-model="plantillasSearch"
                            type="text"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                            placeholder="Buscar plantilla por nombre"
                        >
                        <select
                            v-model="plantillasVisibilityFilter"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                        >
                            <option value="all">Visibilidad: Todas</option>
                            <option value="public">Solo publicas</option>
                            <option value="private">Solo privadas</option>
                        </select>
                        <select
                            v-model="plantillasOwnerFilter"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                        >
                            <option value="all">Origen: Todas</option>
                            <option value="mine">Mis plantillas</option>
                            <option value="community">Comunidad</option>
                        </select>
                    </div>
                </div>

                <div class="grid gap-6 p-6 lg:grid-cols-2">
                    <section>
                         <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-indigo-700 dark:text-indigo-300">Mis Plantillas</h3>
                         <div v-if="filteredPlantillasPropias.length === 0" class="rounded-lg border border-dashed border-indigo-200 bg-indigo-50 p-4 text-sm text-indigo-800 dark:border-indigo-900/30 dark:bg-indigo-900/20 dark:text-indigo-300">
                            No hay plantillas propias que coincidan con los filtros.
                        </div>
                        <div v-else class="space-y-3">
                            <article
                                v-for="plantilla in filteredPlantillasPropias"
                                :key="`tpl-own-tab-${plantilla.id}`"
                                class="rounded-xl border border-indigo-100 bg-indigo-50/70 p-4 dark:border-indigo-900/30 dark:bg-indigo-900/20"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <h4 class="truncate text-sm font-semibold text-indigo-900 dark:text-indigo-300">{{ plantilla.title }}</h4>
                                        <p class="mt-1 text-xs text-indigo-700 dark:text-indigo-400">{{ plantilla.exercises.length }} {{ plantilla.exercises.length === 1 ? 'ejercicio' : 'ejercicios' }} · {{ formatTargetScope(plantilla.target_scope) }}</p>
                                        <p class="mt-1 text-[10px] font-semibold uppercase tracking-wide" :class="plantilla.is_public ? 'text-emerald-700' : 'text-slate-700'">
                                            {{ plantilla.is_public ? 'Publica' : 'Privada' }}
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <button
                                            type="button"
                                            class="rounded-md border border-indigo-300 bg-white px-2 py-1 text-[11px] font-semibold text-indigo-700 hover:bg-indigo-100 dark:border-indigo-700 dark:bg-zinc-900 dark:text-indigo-300 dark:hover:bg-indigo-900/30"
                                            @click="useTemplate(plantilla)"
                                        >
                                            Usar
                                        </button>
                                        <button
                                            v-if="plantilla.can_edit"
                                            type="button"
                                            class="rounded-md border border-slate-300 bg-white px-2 py-1 text-[11px] font-semibold text-slate-700 hover:bg-slate-100 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800"
                                            @click="openTrainingEditor(plantilla)"
                                        >
                                            Editar
                                        </button>
                                    </div>
                                </div>
                            </article>
                        </div>
                    </section>

                    <section>
                        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Plantillas Comunidad</h3>
                        <div v-if="filteredPlantillasComunidad.length === 0" class="rounded-lg border border-dashed border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 dark:border-emerald-900/30 dark:bg-emerald-900/20 dark:text-emerald-300">
                            No hay plantillas de comunidad que coincidan con los filtros.
                        </div>
                        <div v-else class="space-y-3">
                            <article
                                v-for="plantilla in filteredPlantillasComunidad"
                                :key="`tpl-community-tab-${plantilla.id}`"
                                class="rounded-xl border border-emerald-100 bg-emerald-50/70 p-4 dark:border-emerald-900/30 dark:bg-emerald-900/20"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <h4 class="truncate text-sm font-semibold text-emerald-900 dark:text-emerald-300">{{ plantilla.title }}</h4>
                                        <p class="mt-1 text-xs text-emerald-700 dark:text-emerald-400">{{ plantilla.exercises.length }} {{ plantilla.exercises.length === 1 ? 'ejercicio' : 'ejercicios' }} · {{ formatTargetScope(plantilla.target_scope) }}</p>
                                        <p class="mt-1 text-[10px] font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-400">Por {{ plantilla.creator_name ?? 'Entrenador' }}</p>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <button
                                            type="button"
                                            class="rounded-md border border-emerald-300 bg-white px-2 py-1 text-[11px] font-semibold text-emerald-700 hover:bg-emerald-100 dark:border-emerald-700 dark:bg-zinc-900 dark:text-emerald-300 dark:hover:bg-emerald-900/30"
                                            @click="useTemplate(plantilla)"
                                        >
                                            Usar
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded-md border border-emerald-300 bg-white px-2 py-1 text-[11px] font-semibold text-emerald-700 hover:bg-emerald-100 dark:border-emerald-700 dark:bg-zinc-900 dark:text-emerald-300 dark:hover:bg-emerald-900/30"
                                            @click="duplicateTraining(plantilla)"
                                        >
                                            Clonar
                                        </button>
                                    </div>
                                </div>
                            </article>
                        </div>
                    </section>
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
                    <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:border dark:border-zinc-800 dark:bg-zinc-950">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
                            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-zinc-100">Confirmar eliminación</h3>
                        <p class="mt-2 text-sm text-gray-600 dark:text-zinc-400">
                            ¿Estás seguro de que deseas eliminar el ejercicio
                            <strong class="text-gray-900 dark:text-zinc-100">{{ exerciseToDelete.name }}</strong>?
                            Esta acción no se puede deshacer.
                        </p>
                        <div class="mt-6 flex gap-3">
                            <button
                                type="button"
                                class="flex-1 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800"
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
                    <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:border dark:border-zinc-800 dark:bg-zinc-950">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-amber-100">
                            <svg class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-zinc-100">Quitar ejercicio de la sesion</h3>
                        <p class="mt-2 text-sm text-gray-600 dark:text-zinc-400">
                            Estas editando un entrenamiento ya guardado. Al guardar los cambios, este ejercicio sera eliminado permanentemente de la sesion.
                            <strong class="text-gray-900 dark:text-zinc-100">{{ pendingRemoveIndex !== null ? entrenamientoForm.exercises[pendingRemoveIndex]?.name : '' }}</strong>
                        </p>
                        <p class="mt-2 text-xs text-amber-700 font-medium">El ejercicio seguira disponible en la biblioteca.</p>
                        <div class="mt-6 flex gap-3">
                            <button
                                type="button"
                                class="flex-1 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800"
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

        <!-- Modal para crear grupo -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showCreateGroupModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeCreateGroupModal" />
                    <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:border dark:border-zinc-800 dark:bg-zinc-950">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-zinc-100">Guardar selección como grupo</h3>
                        <p class="mt-2 text-sm text-gray-600 dark:text-zinc-400">
                            Dale un nombre a este grupo para poder reutilizarlo en futuros entrenamientos.
                        </p>
                        <div
                            v-if="groupNoticeMessage"
                            class="mt-3 rounded-lg border px-4 py-3 text-sm font-medium shadow-sm"
                            :class="groupNoticeType === 'success'
                                ? 'border-green-200 bg-green-50 text-green-800 dark:border-green-900/30 dark:bg-green-900/20 dark:text-green-400'
                                : 'border-red-200 bg-red-50 text-red-800 dark:border-red-900/30 dark:bg-red-900/20 dark:text-red-400'"
                        >
                            {{ groupNoticeMessage }}
                        </div>
                        <div class="mt-4">
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-zinc-300">Nombre del grupo *</label>
                            <input
                                v-model="newGroupName"
                                type="text"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-purple-500 focus:ring-2 focus:ring-purple-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                                placeholder="Ej: Equipo A, Grupo rescate, etc."
                                @keyup.enter="submitCreateGroup"
                            >
                        </div>
                        <div class="mt-4 flex gap-3">
                            <button
                                type="button"
                                class="flex-1 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800"
                                @click="closeCreateGroupModal"
                            >
                                Cancelar
                            </button>
                            <button
                                type="button"
                                :disabled="!newGroupName.trim() || isCreatingGroup"
                                class="flex-1 rounded-lg bg-purple-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-purple-700 disabled:opacity-50"
                                @click="submitCreateGroup"
                            >
                                <span v-if="isCreatingGroup">Creando...</span>
                                <span v-else>Guardar grupo</span>
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </GeneralLayout>
</template>


