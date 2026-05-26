<script setup>
import Dropdown from '@/Components/Dropdown.vue';
import GeneralLayout from '@/Layouts/GeneralLayout.vue';
import { Head, usePage, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import DeleteClubForm from '@/Pages/Clubes/Partials/FormularioEliminarClub.vue';

const props = defineProps({
    members:            { type: Object, default: null },
    pendingInvitations: { type: Array,  default: () => [] },
    searchResults:      { type: Array,  default: () => [] },
    filters:            { type: Object, default: () => ({ search: '' }) },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const flash = computed(() => page.props.flash ?? {});
const pendingRequestsCount = computed(() => page.props.pendingRequestsCount ?? 0);
const currentClub = computed(() => user.value?.club ?? null);
const canManageClub = computed(() => currentClub.value?.admin_user_id === user.value?.id);
const currentClubLogoUrl = computed(() => currentClub.value?.logo_path ? `/storage/${currentClub.value.logo_path}` : null);

const showClubOptions = computed(() => !user.value.club_id);

const activeTab = ref('create');
const clubs = ref([]);
const selectedClub = ref(null);
const joinMessage = ref('');

const form = useForm({
    club_id: null,
    message: '',
});

onMounted(async () => {
    if (!showClubOptions.value) {
        return;
    }

    try {
        const response = await fetch(route('clubs.available'));
        clubs.value = await response.json();
    } catch (error) {
        console.error('Error cargando clubs:', error);
    }
});

function submitJoinRequest() {
    if (!selectedClub.value) {
        alert('Selecciona un club');
        return;
    }

    form.club_id = selectedClub.value;
    form.message = joinMessage.value;

    form.post(route('clubs.join-request'), {
        onSuccess: () => {
            selectedClub.value = null;
            joinMessage.value = '';
        },
    });
}

const showInvitePanel  = ref((props.filters?.search ?? '') !== '');
const memberSearch     = ref(props.filters?.search ?? '');
const invitingUserId   = ref(null);

function submitMemberSearch() {
    router.get(route('dashboard'), { search: memberSearch.value }, {
        preserveState: true,
        replace: true,
        onSuccess: () => { showInvitePanel.value = true; },
    });
}

function clearMemberSearch() {
    memberSearch.value = '';
    router.get(route('dashboard'), {}, {
        preserveState: true,
        replace: true,
    });
}

function sendInvitation(userId) {
    invitingUserId.value = userId;
    router.post(route('club-invitations.store'), {
        invited_user_id: userId,
        source: 'dashboard',
        search: memberSearch.value,
    }, {
        preserveScroll: true,
        onFinish: () => { invitingUserId.value = null; },
    });
}

function initials(name) {
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((p) => p.charAt(0).toUpperCase())
        .join('');
}
const memberToRemove = ref(null);
const isRemoving = ref(false);
const memberToPromote = ref(null);
const memberToPromoteTargetRole = ref(null);
const isPromoting = ref(false);
const promotingMemberId = ref(null);

function confirmChangeRole(member, targetRole) {
    memberToPromote.value = member;
    memberToPromoteTargetRole.value = targetRole;
}

function confirmPromote(member) {
    if (!member || member.role === 'entrenador') return;
    confirmChangeRole(member, 'entrenador');
}

function confirmDegrade(member) {
    if (!member || member.role !== 'entrenador') return;
    confirmChangeRole(member, 'socorrista');
}

function cancelPromote() {
    memberToPromote.value = null;
    memberToPromoteTargetRole.value = null;
}

function promoteMember() {
    if (!memberToPromote.value) return;

    isPromoting.value = true;
    promotingMemberId.value = memberToPromote.value.id;

    router.patch(route('clubs.members.update-role', memberToPromote.value.id), {
        rol: memberToPromoteTargetRole.value,
    }, {
        preserveScroll: true,
        onFinish: () => {
            isPromoting.value = false;
            promotingMemberId.value = null;
            memberToPromote.value = null;
            memberToPromoteTargetRole.value = null;
        },
    });
}

function confirmRemove(member) {
    memberToRemove.value = member;
}
function cancelRemove() {
    memberToRemove.value = null;
}
function removeMember() {
    if (!memberToRemove.value) return;
    isRemoving.value = true;
    router.delete(route('clubs.members.remove', memberToRemove.value.id), {
        preserveScroll: true,
        onFinish: () => {
            isRemoving.value = false;
            memberToRemove.value = null;
        },
    });
}
</script>

<template>
    <Head title="Dashboard Entrenador" />

    <GeneralLayout>
        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <div
                    v-if="flash.success"
                    class="flex items-start gap-3 rounded-lg border border-green-200 dark:border-green-900 bg-green-50 dark:bg-green-900/20 px-4 py-3 text-green-800 dark:text-green-400 shadow-sm"
                >
                    <p class="text-sm font-medium">{{ flash.success }}</p>
                </div>

                <div
                    v-if="flash.error"
                    class="flex items-start gap-3 rounded-lg border border-red-200 dark:border-red-900 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-red-800 dark:text-red-400 shadow-sm"
                >
                    <p class="text-sm font-medium">{{ flash.error }}</p>
                </div>

                <div v-if="currentClub" class="overflow-hidden bg-white dark:bg-zinc-950 dark:border dark:border-zinc-800 shadow-sm sm:rounded-lg">
                    <div class="flex flex-col gap-6 p-6 md:flex-row md:items-start md:justify-between">
                        <div class="flex items-start gap-4">
                            <div v-if="currentClubLogoUrl" class="h-20 w-20 shrink-0 overflow-hidden rounded-xl border border-gray-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                                <img :src="currentClubLogoUrl" :alt="currentClub.name" class="h-full w-full object-cover">
                            </div>

                            <div>
                                <p class="text-sm font-medium uppercase tracking-wide text-blue-600 dark:text-blue-400">Tu club</p>
                                <h3 class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">{{ currentClub.name }}</h3>
                                <p class="mt-2 text-sm text-gray-600 dark:text-zinc-400">
                                    {{ currentClub.description || 'Aún no has añadido una descripción para tu club.' }}
                                </p>
                            </div>
                        </div>

                        <div v-if="canManageClub" class="flex flex-col gap-3 md:items-end">
                            <Link
                                :href="route('clubs.edit', { club: currentClub.id })"
                                class="inline-flex items-center justify-center rounded-lg border border-blue-200 dark:border-blue-900 bg-blue-50 dark:bg-blue-900/20 px-4 py-2 text-sm font-medium text-blue-700 dark:text-blue-400 transition hover:bg-blue-100 dark:hover:bg-blue-900/30"
                            >
                                Editar club
                            </Link>

                            <DeleteClubForm :club="currentClub" />
                        </div>
                    </div>
                </div>

                <div v-if="canManageClub && members" class="overflow-hidden bg-white dark:bg-zinc-950 dark:border dark:border-zinc-800 shadow-sm sm:rounded-lg">
                    <div class="p-6">

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Socorristas del club</h3>
                                <p class="mt-1 text-sm text-gray-600 dark:text-zinc-400">
                                    {{ members.total }} miembro(s) en <strong>{{ currentClub.name }}</strong>.
                                </p>
                            </div>

                            <button
                                class="inline-flex shrink-0 items-center gap-2 rounded-lg bg-blue-600 dark:bg-blue-700 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700 dark:hover:bg-blue-800"
                                @click="showInvitePanel = !showInvitePanel"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                                Invitar socorrista
                            </button>
                        </div>

                        <div v-if="showInvitePanel" class="mt-6 rounded-xl border border-blue-100 dark:border-blue-900 bg-blue-50 dark:bg-blue-900/20 p-4">
                            <h4 class="mb-3 text-sm font-semibold text-blue-800 dark:text-blue-400">Buscar e invitar socorristas</h4>

                            <form class="flex flex-col gap-3 md:flex-row" @submit.prevent="submitMemberSearch">
                                <input
                                    v-model="memberSearch"
                                    type="text"
                                    class="w-full rounded-lg border border-blue-200 dark:border-blue-900 bg-white dark:bg-zinc-900 px-4 py-2 text-sm dark:text-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:focus:border-blue-400"
                                    placeholder="Buscar por nombre o email"
                                >
                                <div class="flex gap-2">
                                    <button
                                        type="submit"
                                        class="inline-flex items-center rounded-lg bg-blue-600 dark:bg-blue-700 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700 dark:hover:bg-blue-800"
                                    >
                                        Buscar
                                    </button>
                                    <button
                                        v-if="filters.search"
                                        type="button"
                                        class="inline-flex items-center rounded-lg border border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-4 py-2 text-sm font-medium text-gray-700 dark:text-zinc-400 transition hover:bg-gray-50 dark:hover:bg-zinc-800"
                                        @click="clearMemberSearch"
                                    >
                                        Limpiar
                                    </button>
                                </div>
                            </form>

                            <div v-if="$page.props.errors?.invited_user_id" class="mt-2 text-sm text-red-600 dark:text-red-400">
                                {{ $page.props.errors.invited_user_id }}
                            </div>

                            <div class="mt-4 space-y-3">
                                <template v-if="searchResults.length > 0">
                                    <div
                                        v-for="result in searchResults"
                                        :key="result.id"
                                        class="flex flex-col gap-3 rounded-xl border border-white dark:border-zinc-800 bg-white dark:bg-zinc-900 p-3 shadow-sm sm:flex-row sm:items-center sm:justify-between"
                                    >
                                        <div class="flex items-center gap-3">
                                            <div v-if="result.avatar_url" class="h-10 w-10 overflow-hidden rounded-full border border-gray-200 dark:border-zinc-800 bg-white dark:bg-zinc-800">
                                                <img :src="result.avatar_url" :alt="result.name" class="h-full w-full object-cover">
                                            </div>
                                            <div v-else class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900 text-xs font-semibold text-blue-700 dark:text-blue-400">
                                                {{ initials(result.name) }}
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ result.name }}</p>
                                                <p class="text-xs text-gray-500 dark:text-zinc-500">{{ result.email }}</p>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-3">
                                            <span class="text-xs text-gray-500 dark:text-zinc-500">{{ result.status_label }}</span>
                                            <button
                                                type="button"
                                                class="inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-medium text-white transition"
                                                :class="result.can_invite ? 'bg-blue-600 dark:bg-blue-700 hover:bg-blue-700 dark:hover:bg-blue-800' : 'cursor-not-allowed bg-gray-300 dark:bg-zinc-700 text-gray-600 dark:text-zinc-400'"
                                                :disabled="!result.can_invite || invitingUserId === result.id"
                                                @click="sendInvitation(result.id)"
                                            >
                                                <span v-if="invitingUserId === result.id">Enviando…</span>
                                                <span v-else>{{ result.can_invite ? 'Invitar' : 'No disponible' }}</span>
                                            </button>
                                        </div>
                                    </div>
                                </template>

                                <p v-else-if="filters.search" class="rounded-lg bg-white dark:bg-zinc-900 px-4 py-3 text-sm text-blue-700 dark:text-blue-400">
                                    No se encontraron socorristas con ese criterio.
                                </p>
                            </div>
                        </div>

                        <div v-if="pendingInvitations.length > 0" class="mt-6">
                            <h4 class="mb-3 text-sm font-semibold text-gray-700 dark:text-zinc-300">Invitaciones pendientes</h4>
                            <div class="space-y-2">
                                <div
                                    v-for="invitation in pendingInvitations"
                                    :key="invitation.id"
                                    class="flex items-center justify-between rounded-xl border border-amber-200 dark:border-amber-900 bg-amber-50 dark:bg-amber-900/20 px-4 py-3"
                                >
                                    <div class="flex items-center gap-3">
                                        <div v-if="invitation.invited_user.avatar_url" class="h-9 w-9 overflow-hidden rounded-full border border-amber-200 dark:border-amber-900 bg-white dark:bg-zinc-900">
                                            <img :src="invitation.invited_user.avatar_url" :alt="invitation.invited_user.name" class="h-full w-full object-cover">
                                        </div>
                                        <div v-else class="flex h-9 w-9 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900 text-xs font-semibold text-amber-700 dark:text-amber-400">
                                            {{ initials(invitation.invited_user.name) }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ invitation.invited_user.name }}</p>
                                            <p class="text-xs text-gray-500 dark:text-zinc-500">{{ invitation.invited_user.email }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="inline-block rounded-full bg-amber-200 dark:bg-amber-900 px-2 py-0.5 text-xs font-semibold text-amber-800 dark:text-amber-400">Pendiente</span>
                                        <p class="mt-0.5 text-xs text-gray-500 dark:text-zinc-500">{{ invitation.created_at }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-800">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-zinc-500">Miembro</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-zinc-500">Email</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-zinc-500">Rol</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-zinc-500">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-zinc-800 bg-white dark:bg-zinc-950">
                                    <tr v-for="member in members.data" :key="member.id">
                                        <td class="whitespace-nowrap px-4 py-4">
                                            <div class="flex items-center gap-3">
                                                <div v-if="member.avatar_url" class="h-9 w-9 overflow-hidden rounded-full border border-gray-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                                                    <img :src="member.avatar_url" :alt="member.name" class="h-full w-full object-cover">
                                                </div>
                                                <div v-else class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900 text-xs font-semibold text-blue-700 dark:text-blue-400">
                                                    {{ initials(member.name) }}
                                                </div>
                                                <span class="font-medium text-gray-900 dark:text-white">
                                                    {{ member.name }}
                                                    <span v-if="member.id === user.id" class="ml-1 text-xs font-normal text-blue-500 dark:text-blue-400">(yo)</span>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600 dark:text-zinc-400">{{ member.email }}</td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700 dark:text-zinc-300">{{ member.role_label }}</td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm">
                                            <Dropdown
                                                v-if="member.id !== currentClub.admin_user_id"
                                                align="right"
                                                width="48"
                                                content-classes="py-2 bg-white dark:bg-zinc-900 dark:border dark:border-zinc-800"
                                            >
                                                <template #trigger>
                                                    <button
                                                        type="button"
                                                        class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 dark:border-zinc-800 text-gray-500 dark:text-zinc-500 transition hover:bg-gray-50 dark:hover:bg-zinc-900 hover:text-gray-700 dark:hover:text-zinc-300"
                                                    >
                                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 6a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 6a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" />
                                                        </svg>
                                                    </button>
                                                </template>

                                                <template #content>
                                                    <!-- Promover a Entrenador (solo si es socorrista) -->
                                                    <button
                                                        v-if="member.role !== 'entrenador'"
                                                        type="button"
                                                        class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-blue-700 dark:text-blue-400 transition hover:bg-blue-50 dark:hover:bg-blue-900/20 disabled:cursor-not-allowed disabled:opacity-60"
                                                        :disabled="promotingMemberId === member.id"
                                                        @click="confirmPromote(member)"
                                                    >
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9A2.25 2.25 0 0 1 5.25 16.5v-9A2.25 2.25 0 0 1 7.5 5.25h5.379a2.25 2.25 0 0 1 1.591.659l3.621 3.621a2.25 2.25 0 0 1 .659 1.591V16.5a2.25 2.25 0 0 1-2.25 2.25Z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75h6m-3-3v6" />
                                                        </svg>
                                                        <span v-if="promotingMemberId === member.id">Actualizando...</span>
                                                        <span v-else>↑ Promover a Entrenador</span>
                                                    </button>

                                                    <!-- Degradar a Socorrista (solo si es entrenador no-admin) -->
                                                    <button
                                                        v-if="member.role === 'entrenador'"
                                                        type="button"
                                                        class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-amber-700 dark:text-amber-400 transition hover:bg-amber-50 dark:hover:bg-amber-900/20 disabled:cursor-not-allowed disabled:opacity-60"
                                                        :disabled="promotingMemberId === member.id"
                                                        @click="confirmDegrade(member)"
                                                    >
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9A2.25 2.25 0 0 1 5.25 16.5v-9A2.25 2.25 0 0 1 7.5 5.25h5.379a2.25 2.25 0 0 1 1.591.659l3.621 3.621a2.25 2.25 0 0 1 .659 1.591V16.5a2.25 2.25 0 0 1-2.25 2.25Z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75h6" />
                                                        </svg>
                                                        <span v-if="promotingMemberId === member.id">Actualizando...</span>
                                                        <span v-else>↓ Degradar a Socorrista</span>
                                                    </button>

                                                    <button
                                                        type="button"
                                                        class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-red-600 dark:text-red-400 transition hover:bg-red-50 dark:hover:bg-red-900/20"
                                                        @click="confirmRemove(member)"
                                                    >
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M22 10.5h-6m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM4 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 10.374 21c-2.331 0-4.512-.645-6.374-1.766Z" />
                                                        </svg>
                                                        Expulsar
                                                    </button>
                                                </template>
                                            </Dropdown>
                                            <span v-else class="text-xs text-gray-400 dark:text-zinc-600 italic">Admin</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginación -->
                        <div class="mt-4 flex items-center justify-between gap-4">
                            <Link
                                v-if="members.prev_page_url"
                                :href="members.prev_page_url"
                                class="inline-flex items-center rounded-lg border border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-4 py-2 text-sm font-medium text-gray-700 dark:text-zinc-400 transition hover:bg-gray-50 dark:hover:bg-zinc-800"
                            >
                                Anterior
                            </Link>
                            <span v-else class="inline-flex items-center rounded-lg border border-gray-200 dark:border-zinc-800 bg-gray-100 dark:bg-zinc-900 px-4 py-2 text-sm font-medium text-gray-400 dark:text-zinc-600">
                                Anterior
                            </span>

                            <span class="text-sm text-gray-500 dark:text-zinc-500">
                                Página {{ members.current_page }} de {{ members.last_page }}
                            </span>

                            <Link
                                v-if="members.next_page_url"
                                :href="members.next_page_url"
                                class="inline-flex items-center rounded-lg border border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-4 py-2 text-sm font-medium text-gray-700 dark:text-zinc-400 transition hover:bg-gray-50 dark:hover:bg-zinc-800"
                            >
                                Siguiente
                            </Link>
                            <span v-else class="inline-flex items-center rounded-lg border border-gray-200 dark:border-zinc-800 bg-gray-100 dark:bg-zinc-900 px-4 py-2 text-sm font-medium text-gray-400 dark:text-zinc-600">
                                Siguiente
                            </span>
                        </div>

                    </div>
                </div>

                <div v-if="canManageClub && pendingRequestsCount > 0" class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="flex items-center justify-between p-6">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">Solicitudes de union pendientes</h3>
                            <p class="mt-0.5 text-sm text-gray-600">
                                Tienes <strong>{{ pendingRequestsCount }}</strong> solicitud(es) pendiente(s) de revision.
                            </p>
                        </div>

                        <Link
                            :href="route('clubs.join-requests.index')"
                            class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700"
                        >
                            Ver solicitudes
                        </Link>
                    </div>
                </div>

                <div v-if="showClubOptions" class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="mb-4 text-lg font-semibold">Bienvenido, Entrenador</h3>
                        <p class="mb-6 text-gray-600">
                            Para comenzar, necesitas crear un club deportivo o unirte a uno existente.
                        </p>

                        <div class="mb-6 grid grid-cols-2 gap-4">
                            <button
                                :class="[
                                    'rounded-lg border-2 p-4 transition',
                                    activeTab === 'create' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300',
                                ]"
                                @click="activeTab = 'create'"
                            >
                                <span class="block font-semibold text-gray-900">Crear Club</span>
                                <span class="block text-sm text-gray-600">Registra tu propio club</span>
                            </button>

                            <button
                                :class="[
                                    'rounded-lg border-2 p-4 transition',
                                    activeTab === 'join' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300',
                                ]"
                                @click="activeTab = 'join'"
                            >
                                <span class="block font-semibold text-gray-900">Unirme a un Club</span>
                                <span class="block text-sm text-gray-600">Solicita acceso a un club</span>
                            </button>
                        </div>

                        <div v-if="activeTab === 'create'" class="rounded-lg bg-blue-50 p-4">
                            <p class="mb-4 text-gray-700">
                                Crea tu propio club deportivo y comienza a gestionar tus socorristas y entrenamientos.
                            </p>
                            <Link
                                :href="route('clubs.create')"
                                class="inline-block rounded-lg bg-blue-600 px-6 py-2 text-white transition hover:bg-blue-700"
                            >
                                Crear Club Ahora
                            </Link>
                        </div>

                        <div v-if="activeTab === 'join'" class="space-y-4">
                            <p class="text-gray-700">
                                Selecciona un club para enviar una solicitud de union. El administrador recibira un correo para aceptarla o rechazarla.
                            </p>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700">Seleccionar Club</label>
                                <select
                                    v-model="selectedClub"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                >
                                    <option value="">-- Elige un club --</option>
                                    <option v-for="club in clubs" :key="club.id" :value="club.id">
                                        {{ club.name }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-700">Mensaje (opcional)</label>
                                <textarea
                                    v-model="joinMessage"
                                    class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                    rows="3"
                                    maxlength="500"
                                    placeholder="Cuentale al admin del club por que quieres unirte..."
                                />
                                <p class="mt-1 text-xs text-gray-500">{{ joinMessage.length }}/500</p>
                            </div>

                            <button
                                :disabled="form.processing || !selectedClub"
                                class="w-full rounded-lg bg-blue-600 px-4 py-2 text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                                @click="submitJoinRequest"
                            >
                                <span v-if="form.processing">Enviando solicitud...</span>
                                <span v-else>Enviar Solicitud</span>
                            </button>

                            <div v-if="form.errors.club_id" class="text-sm text-red-600">{{ form.errors.club_id }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal confirmación expulsión -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="memberToRemove" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="cancelRemove" />
                    <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
                            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">Confirmar expulsión</h3>
                        <p class="mt-2 text-sm text-gray-600">
                            ¿Estás seguro de que deseas expulsar a
                            <strong class="text-gray-900">{{ memberToRemove.name }}</strong>
                            del club? Esta acción romperá su vínculo con el equipo de forma inmediata.
                        </p>
                        <div class="mt-6 flex gap-3">
                            <button
                                type="button"
                                class="flex-1 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                                @click="cancelRemove"
                            >
                                Cancelar
                            </button>
                            <button
                                type="button"
                                class="flex-1 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700 disabled:opacity-50"
                                :disabled="isRemoving"
                                @click="removeMember"
                            >
                                <span v-if="isRemoving">Expulsando...</span>
                                <span v-else>Sí, expulsar</span>
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="memberToPromote" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="cancelPromote" />
                    <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                        <div
                            class="mb-4 flex h-12 w-12 items-center justify-center rounded-full"
                            :class="memberToPromoteTargetRole === 'entrenador' ? 'bg-blue-100' : 'bg-amber-100'"
                        >
                            <svg
                                class="h-6 w-6"
                                :class="memberToPromoteTargetRole === 'entrenador' ? 'text-blue-600' : 'text-amber-600'"
                                fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 9.75V6.75A2.25 2.25 0 0 0 15.75 4.5h-7.5A2.25 2.25 0 0 0 6 6.75v10.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 17.25v-3m-6-2.25h7.5m0 0-2.25-2.25M19.5 12l-2.25 2.25" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">
                            {{ memberToPromoteTargetRole === 'entrenador' ? 'Confirmar promoción' : 'Confirmar degradación' }}
                        </h3>
                        <p class="mt-2 text-sm text-gray-600">
                            <template v-if="memberToPromoteTargetRole === 'entrenador'">
                                ¿Quieres promover a
                                <strong class="text-gray-900">{{ memberToPromote.name }}</strong>
                                para que pase a ser Entrenador del club? Tendrá acceso inmediato a las herramientas de gestión disponibles para este rol.
                            </template>
                            <template v-else>
                                ¿Quieres revocar el rol de Entrenador a
                                <strong class="text-gray-900">{{ memberToPromote.name }}</strong>?
                                Perderá inmediatamente el acceso a la gestión del club y pasará a ser Socorrista.
                            </template>
                        </p>
                        <div class="mt-6 flex gap-3">
                            <button
                                type="button"
                                class="flex-1 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                                @click="cancelPromote"
                            >
                                Cancelar
                            </button>
                            <button
                                type="button"
                                class="flex-1 rounded-lg px-4 py-2 text-sm font-medium text-white transition disabled:opacity-50"
                                :class="memberToPromoteTargetRole === 'entrenador' ? 'bg-blue-600 hover:bg-blue-700' : 'bg-amber-600 hover:bg-amber-700'"
                                :disabled="isPromoting"
                                @click="promoteMember"
                            >
                                <span v-if="isPromoting">Actualizando...</span>
                                <span v-else-if="memberToPromoteTargetRole === 'entrenador'">Sí, promover</span>
                                <span v-else>Sí, degradar</span>
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </GeneralLayout>
</template>
