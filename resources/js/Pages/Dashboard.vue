<script setup>
import GeneralLayout from '@/Layouts/GeneralLayout.vue';
import { Head, usePage, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import DeleteClubForm from '@/Pages/Clubs/Partials/DeleteClubForm.vue';

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
</script>

<template>
    <Head title="Dashboard Entrenador" />

    <GeneralLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Dashboard Entrenador
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <div
                    v-if="flash.success"
                    class="flex items-start gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800 shadow-sm"
                >
                    <p class="text-sm font-medium">{{ flash.success }}</p>
                </div>

                <div
                    v-if="flash.error"
                    class="flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800 shadow-sm"
                >
                    <p class="text-sm font-medium">{{ flash.error }}</p>
                </div>

                <div v-if="currentClub" class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="flex flex-col gap-6 p-6 md:flex-row md:items-start md:justify-between">
                        <div class="flex items-start gap-4">
                            <div v-if="currentClubLogoUrl" class="h-20 w-20 shrink-0 overflow-hidden rounded-xl border border-gray-200 bg-white">
                                <img :src="currentClubLogoUrl" :alt="currentClub.name" class="h-full w-full object-cover">
                            </div>

                            <div>
                                <p class="text-sm font-medium uppercase tracking-wide text-blue-600">Tu club</p>
                                <h3 class="mt-1 text-xl font-semibold text-gray-900">{{ currentClub.name }}</h3>
                                <p class="mt-2 text-sm text-gray-600">
                                    {{ currentClub.description || 'Aún no has añadido una descripción para tu club.' }}
                                </p>
                            </div>
                        </div>

                        <div v-if="canManageClub" class="flex flex-col gap-3 md:items-end">
                            <Link
                                :href="route('clubs.edit', { club: currentClub.id })"
                                class="inline-flex items-center justify-center rounded-lg border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-medium text-blue-700 transition hover:bg-blue-100"
                            >
                                Editar club
                            </Link>

                            <DeleteClubForm :club="currentClub" />
                        </div>
                    </div>
                </div>

                <div v-if="canManageClub && members" class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Socorristas del club</h3>
                                <p class="mt-1 text-sm text-gray-600">
                                    {{ members.total }} miembro(s) en <strong>{{ currentClub.name }}</strong>.
                                </p>
                            </div>

                            <button
                                class="inline-flex shrink-0 items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700"
                                @click="showInvitePanel = !showInvitePanel"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                                Invitar socorrista
                            </button>
                        </div>

                        <div v-if="showInvitePanel" class="mt-6 rounded-xl border border-blue-100 bg-blue-50 p-4">
                            <h4 class="mb-3 text-sm font-semibold text-blue-800">Buscar e invitar socorristas</h4>

                            <form class="flex flex-col gap-3 md:flex-row" @submit.prevent="submitMemberSearch">
                                <input
                                    v-model="memberSearch"
                                    type="text"
                                    class="w-full rounded-lg border border-blue-200 bg-white px-4 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Buscar por nombre o email"
                                >
                                <div class="flex gap-2">
                                    <button
                                        type="submit"
                                        class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700"
                                    >
                                        Buscar
                                    </button>
                                    <button
                                        v-if="filters.search"
                                        type="button"
                                        class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                                        @click="clearMemberSearch"
                                    >
                                        Limpiar
                                    </button>
                                </div>
                            </form>

                            <div v-if="$page.props.errors?.invited_user_id" class="mt-2 text-sm text-red-600">
                                {{ $page.props.errors.invited_user_id }}
                            </div>

                            <div class="mt-4 space-y-3">
                                <template v-if="searchResults.length > 0">
                                    <div
                                        v-for="result in searchResults"
                                        :key="result.id"
                                        class="flex flex-col gap-3 rounded-xl border border-white bg-white p-3 shadow-sm sm:flex-row sm:items-center sm:justify-between"
                                    >
                                        <div class="flex items-center gap-3">
                                            <div v-if="result.avatar_url" class="h-10 w-10 overflow-hidden rounded-full border border-gray-200 bg-white">
                                                <img :src="result.avatar_url" :alt="result.name" class="h-full w-full object-cover">
                                            </div>
                                            <div v-else class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-xs font-semibold text-blue-700">
                                                {{ initials(result.name) }}
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">{{ result.name }}</p>
                                                <p class="text-xs text-gray-500">{{ result.email }}</p>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-3">
                                            <span class="text-xs text-gray-500">{{ result.status_label }}</span>
                                            <button
                                                type="button"
                                                class="inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-medium text-white transition"
                                                :class="result.can_invite ? 'bg-blue-600 hover:bg-blue-700' : 'cursor-not-allowed bg-gray-300 text-gray-600'"
                                                :disabled="!result.can_invite || invitingUserId === result.id"
                                                @click="sendInvitation(result.id)"
                                            >
                                                <span v-if="invitingUserId === result.id">Enviando…</span>
                                                <span v-else>{{ result.can_invite ? 'Invitar' : 'No disponible' }}</span>
                                            </button>
                                        </div>
                                    </div>
                                </template>

                                <p v-else-if="filters.search" class="rounded-lg bg-white px-4 py-3 text-sm text-blue-700">
                                    No se encontraron socorristas con ese criterio.
                                </p>
                            </div>
                        </div>

                        <div v-if="pendingInvitations.length > 0" class="mt-6">
                            <h4 class="mb-3 text-sm font-semibold text-gray-700">Invitaciones pendientes</h4>
                            <div class="space-y-2">
                                <div
                                    v-for="invitation in pendingInvitations"
                                    :key="invitation.id"
                                    class="flex items-center justify-between rounded-xl border border-amber-200 bg-amber-50 px-4 py-3"
                                >
                                    <div class="flex items-center gap-3">
                                        <div v-if="invitation.invited_user.avatar_url" class="h-9 w-9 overflow-hidden rounded-full border border-amber-200 bg-white">
                                            <img :src="invitation.invited_user.avatar_url" :alt="invitation.invited_user.name" class="h-full w-full object-cover">
                                        </div>
                                        <div v-else class="flex h-9 w-9 items-center justify-center rounded-full bg-amber-100 text-xs font-semibold text-amber-700">
                                            {{ initials(invitation.invited_user.name) }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ invitation.invited_user.name }}</p>
                                            <p class="text-xs text-gray-500">{{ invitation.invited_user.email }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="inline-block rounded-full bg-amber-200 px-2 py-0.5 text-xs font-semibold text-amber-800">Pendiente</span>
                                        <p class="mt-0.5 text-xs text-gray-500">{{ invitation.created_at }}</p>
                                    </div>
                                </div>
                            </div>
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
                                                <div v-if="member.avatar_url" class="h-9 w-9 overflow-hidden rounded-full border border-gray-200 bg-white">
                                                    <img :src="member.avatar_url" :alt="member.name" class="h-full w-full object-cover">
                                                </div>
                                                <div v-else class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-100 text-xs font-semibold text-blue-700">
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

                        <!-- Paginación -->
                        <div class="mt-4 flex items-center justify-between gap-4">
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
    </GeneralLayout>
</template>
