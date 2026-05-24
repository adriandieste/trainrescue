<script setup>
import { computed, ref } from 'vue';
import GeneralLayout from '@/Layouts/GeneralLayout.vue';
import ClubTimesPanel from './Partials/ClubTimesPanel.vue';
import PersonalBestsTable from './Partials/PersonalBestsTable.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    userProfile: {
        type: Object,
        required: true,
    },
    personalBestTests: {
        type: Array,
        default: () => [],
    },
    clubTimePanel: {
        type: Object,
        default: null,
    },
});

const activeTab = ref('perfil');
const isEntrenador = computed(() => props.userProfile.rol === 'entrenador');
const roleLabel = computed(() => props.userProfile.role_label ?? (isEntrenador.value ? 'Entrenador' : 'Socorrista'));

// Avatar reactivo: se actualiza cuando Inertia actualiza los shared props tras guardar [CA-4]
const authUser = computed(() => usePage().props.auth.user);
const avatarUrl = computed(() => authUser.value.avatar ? `/storage/${authUser.value.avatar}` : null);
const userInitials = computed(() =>
    authUser.value.name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2)
);
</script>

<template>
    <Head title="Perfil" />

    <GeneralLayout>
        <div class="mx-auto flex w-full max-w-6xl flex-col gap-6">
            <div class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm">
                <div class="bg-white">
                    <div class="flex flex-col items-center text-center">
                        <div class="mb-4 flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-full border-4 border-neutral-200 bg-indigo-100 text-2xl font-semibold text-indigo-600">
                            <img
                                v-if="avatarUrl"
                                :src="avatarUrl"
                                :alt="authUser.name"
                                class="h-full w-full object-cover"
                            />
                            <span v-else>{{ userInitials }}</span>
                        </div>
                        <h3 class="text-3xl font-bold text-neutral-900">{{ userProfile.name }}</h3>
                        <p class="mt-1 text-lg text-neutral-600">{{ userProfile.email }}</p>
                    </div>

                    <div class="mt-6 flex flex-wrap justify-center gap-4">
                        <div class="min-w-[140px] rounded-xl border border-neutral-200 bg-neutral-50 p-3 px-6 text-center shadow-sm">
                            <p class="text-xs font-medium uppercase tracking-wider text-neutral-500">Rol</p>
                            <p class="mt-0.5 text-lg font-bold text-neutral-900">
                                {{ roleLabel }}
                            </p>
                        </div>

                        <div class="min-w-[140px] rounded-xl border border-neutral-200 bg-neutral-50 p-3 px-6 text-center shadow-sm">
                            <p class="text-xs font-medium uppercase tracking-wider text-neutral-500">Club</p>
                            <p class="mt-0.5 text-lg font-bold text-neutral-900">
                                {{ userProfile.club ?? 'Sin club' }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="mx-auto mt-6 grid w-full max-w-md grid-cols-2 gap-1 rounded-xl border border-neutral-200 bg-neutral-100/80 p-1 shadow-sm">
                    <button
                        type="button"
                        class="rounded-lg px-3 py-2 text-sm font-semibold transition-all duration-200"
                        :class="activeTab === 'perfil'
                            ? 'bg-white text-blue-600 shadow-sm ring-1 ring-neutral-200'
                            : 'text-neutral-500 hover:text-neutral-700'"
                        @click="activeTab = 'perfil'"
                    >
                        Perfil
                    </button>
                    <button
                        type="button"
                        class="rounded-lg px-3 py-2 text-sm font-semibold transition-all duration-200"
                        :class="activeTab === 'ajustes'
                            ? 'bg-white text-blue-600 shadow-sm ring-1 ring-neutral-200'
                            : 'text-neutral-500 hover:text-neutral-700'"
                        @click="activeTab = 'ajustes'"
                    >
                        Ajustes
                    </button>
                </div>
            </div>

            <section v-if="activeTab === 'perfil'" class="space-y-6">
                <ClubTimesPanel
                    v-if="isEntrenador && clubTimePanel"
                    :club="clubTimePanel.club"
                    :filters="clubTimePanel.filters"
                    :options="clubTimePanel.options"
                    :records="clubTimePanel.records"
                />

                <PersonalBestsTable
                    v-if="!isEntrenador"
                    :tests="personalBestTests"
                    :can-edit="true"
                />
            </section>

            <section v-else class="space-y-6">
                <div class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm">
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="text-xl font-semibold text-red-600 transition hover:text-red-700"
                    >
                        Cerrar Sesión
                    </Link>
                </div>
                <div id="editar-perfil" class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="max-w-xl"
                    />
                </div>

                <div id="seguridad" class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm">
                    <UpdatePasswordForm class="max-w-xl" />
                </div>

                <div id="privacidad" class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm">
                    <DeleteUserForm class="max-w-xl" />
                </div>
            </section>
        </div>
    </GeneralLayout>
</template>
