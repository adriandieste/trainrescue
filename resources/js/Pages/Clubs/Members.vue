<script setup>
import GeneralLayout from '@/Layouts/GeneralLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    club: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    },
    searchResults: {
        type: Array,
        required: true,
    },
    pendingInvitations: {
        type: Array,
        required: true,
    },
    members: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const flash = computed(() => page.props.flash ?? {});
const search = ref(props.filters.search ?? '');
const invitingUserId = ref(null);

function submitSearch() {
    router.get(route('clubs.members.index'), {
        search: search.value,
    }, {
        preserveState: true,
        replace: true,
    });
}

function clearSearch() {
    search.value = '';
    submitSearch();
}

function sendInvitation(userId) {
    invitingUserId.value = userId;

    router.post(route('club-invitations.store'), {
        invited_user_id: userId,
        search: search.value,
    }, {
        preserveScroll: true,
        onFinish: () => {
            invitingUserId.value = null;
        },
    });
}

function initials(name) {
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');
}
</script>

<template>
    <Head :title="`Miembros - ${club.name}`" />

    <GeneralLayout>
        <template #header>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">
                        Miembros e invitaciones del club
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Gestiona tu equipo, invita socorristas y revisa el listado completo de integrantes.
                    </p>
                </div>

                <Link
                    :href="route('dashboard')"
                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                >
                    Volver al dashboard
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
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
                    <div class="p-6">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Invitar socorristas</h3>
                                <p class="mt-1 text-sm text-gray-600">
                                    Busca por nombre o email a usuarios registrados y envíales una invitación a <strong>{{ club.name }}</strong>.
                                </p>
                            </div>
                        </div>

                        <form class="mt-6 flex flex-col gap-3 md:flex-row" @submit.prevent="submitSearch">
                            <input
                                v-model="search"
                                type="text"
                                class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                placeholder="Buscar por nombre o email"
                            >

                            <div class="flex gap-3">
                                <PrimaryButton type="submit">Buscar</PrimaryButton>
                                <SecondaryButton v-if="filters.search" type="button" @click="clearSearch">
                                    Limpiar
                                </SecondaryButton>
                            </div>
                        </form>

                        <div v-if="$page.props.errors.invited_user_id" class="mt-3 text-sm text-red-600">
                            {{ $page.props.errors.invited_user_id }}
                        </div>

                        <div class="mt-6 space-y-4">
                            <template v-if="searchResults.length > 0">
                                <div
                                    v-for="result in searchResults"
                                    :key="result.id"
                                    class="flex flex-col gap-4 rounded-xl border border-gray-200 p-4 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div class="flex items-center gap-4">
                                        <div v-if="result.avatar_url" class="h-12 w-12 overflow-hidden rounded-full border border-gray-200 bg-white">
                                            <img :src="result.avatar_url" :alt="result.name" class="h-full w-full object-cover">
                                        </div>
                                        <div v-else class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 text-sm font-semibold text-blue-700">
                                            {{ initials(result.name) }}
                                        </div>

                                        <div>
                                            <p class="font-medium text-gray-900">{{ result.name }}</p>
                                            <p class="text-sm text-gray-500">{{ result.email }}</p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <span class="text-sm text-gray-500">{{ result.status_label }}</span>
                                        <button
                                            type="button"
                                            class="inline-flex items-center rounded-lg px-4 py-2 text-sm font-medium text-white transition"
                                            :class="result.can_invite
                                                ? 'bg-blue-600 hover:bg-blue-700'
                                                : 'cursor-not-allowed bg-gray-300 text-gray-600'"
                                            :disabled="!result.can_invite || invitingUserId === result.id"
                                            @click="sendInvitation(result.id)"
                                        >
                                            <span v-if="invitingUserId === result.id">Enviando...</span>
                                            <span v-else>{{ result.can_invite ? 'Invitar' : 'No disponible' }}</span>
                                        </button>
                                    </div>
                                </div>
                            </template>

                            <p v-else-if="filters.search" class="rounded-lg bg-gray-50 px-4 py-3 text-sm text-gray-600">
                                No se han encontrado socorristas con ese criterio.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900">Invitaciones pendientes</h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Revisa a quién has invitado y el estado actual de cada propuesta enviada.
                        </p>

                        <div class="mt-6 space-y-4">
                            <div
                                v-for="invitation in pendingInvitations"
                                :key="invitation.id"
                                class="flex items-center justify-between rounded-xl border border-amber-200 bg-amber-50 p-4"
                            >
                                <div class="flex items-center gap-4">
                                    <div v-if="invitation.invited_user.avatar_url" class="h-12 w-12 overflow-hidden rounded-full border border-amber-200 bg-white">
                                        <img :src="invitation.invited_user.avatar_url" :alt="invitation.invited_user.name" class="h-full w-full object-cover">
                                    </div>
                                    <div v-else class="flex h-12 w-12 items-center justify-center rounded-full bg-white text-sm font-semibold text-amber-700">
                                        {{ initials(invitation.invited_user.name) }}
                                    </div>

                                    <div>
                                        <p class="font-medium text-gray-900">{{ invitation.invited_user.name }}</p>
                                        <p class="text-sm text-gray-600">{{ invitation.invited_user.email }}</p>
                                    </div>
                                </div>

                                <div class="text-right text-sm text-amber-800">
                                    <p class="font-semibold">Pendiente</p>
                                    <p>{{ invitation.created_at }}</p>
                                </div>
                            </div>

                            <p v-if="pendingInvitations.length === 0" class="rounded-lg bg-gray-50 px-4 py-3 text-sm text-gray-600">
                                No hay invitaciones pendientes en este momento.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Miembros del club</h3>
                                <p class="mt-1 text-sm text-gray-600">
                                    Listado completo de integrantes de <strong>{{ club.name }}</strong> con su avatar y rol.
                                </p>
                            </div>

                            <p class="text-sm text-gray-500">
                                {{ members.total }} miembro(s) registrados
                            </p>
                        </div>

                        <div class="mt-6 overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Miembro</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Email</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Rol</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    <tr v-for="member in members.data" :key="member.id">
                                        <td class="whitespace-nowrap px-4 py-4">
                                            <div class="flex items-center gap-3">
                                                <div v-if="member.avatar_url" class="h-10 w-10 overflow-hidden rounded-full border border-gray-200 bg-white">
                                                    <img :src="member.avatar_url" :alt="member.name" class="h-full w-full object-cover">
                                                </div>
                                                <div v-else class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-xs font-semibold text-blue-700">
                                                    {{ initials(member.name) }}
                                                </div>

                                                <span class="font-medium text-gray-900">{{ member.name }}</span>
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">{{ member.email }}</td>
                                        <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-700">{{ member.role_label }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-6 flex items-center justify-between gap-4">
                            <Link
                                v-if="members.prev_page_url"
                                :href="members.prev_page_url"
                                class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                            >
                                Anterior
                            </Link>
                            <span v-else class="inline-flex items-center rounded-lg border border-gray-200 bg-gray-100 px-4 py-2 text-sm font-medium text-gray-400">
                                Anterior
                            </span>

                            <span class="text-sm text-gray-500">
                                Página {{ members.current_page }} de {{ members.last_page }}
                            </span>

                            <Link
                                v-if="members.next_page_url"
                                :href="members.next_page_url"
                                class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                            >
                                Siguiente
                            </Link>
                            <span v-else class="inline-flex items-center rounded-lg border border-gray-200 bg-gray-100 px-4 py-2 text-sm font-medium text-gray-400">
                                Siguiente
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </GeneralLayout>
</template>
