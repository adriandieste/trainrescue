<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage, Link, useForm } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';

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
</script>

<template>
    <Head title="Dashboard Entrenador" />

    <AuthenticatedLayout>
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

                        <Link
                            v-if="canManageClub"
                            :href="route('clubs.edit', { club: currentClub.id })"
                            class="inline-flex items-center justify-center rounded-lg border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-medium text-blue-700 transition hover:bg-blue-100"
                        >
                            Editar club
                        </Link>
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
                                Crea tu propio club deportivo y comienza a gestionar tus atletas y entrenamientos.
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
    </AuthenticatedLayout>
</template>
