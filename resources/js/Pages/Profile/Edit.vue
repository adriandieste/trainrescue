<script setup>
import { computed, ref } from 'vue';
import GeneralLayout from '@/Layouts/GeneralLayout.vue';
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
});

const activeTab = ref('perfil');
const isEntrenador = computed(() => props.userProfile.rol === 'entrenador');
const roleLabel = computed(() => props.userProfile.role_label ?? (isEntrenador.value ? 'Entrenador' : 'Socorrista'));
const darkModeEnabled = ref(false);
const notificationsEnabled = ref(true);

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

const goToSetting = (id) => {
    const section = document.getElementById(id);
    if (section) {
        section.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};
</script>

<template>
    <Head title="Perfil" />

    <GeneralLayout>
        <div class="w-full min-h-[100dvh] flex flex-col">
            <div class="bg-white p-3 ">
                <div class="w-full max-w-6xl mx-auto  bg-white p-6 ">
                    <div class="flex flex-col items-center text-center">
                        <!-- Avatar reactivo: muestra foto o iniciales [CA-4] -->
                        <div class="mb-4 h-20 w-20 shrink-0 overflow-hidden rounded-full border-4 border-neutral-200 bg-indigo-100 flex items-center justify-center text-indigo-600 font-semibold text-2xl">
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

                    <div class="mt-6 flex justify-center gap-4">
                        <div class="rounded-xl bg-neutral-100 p-3 px-6 text-center min-w-[140px] shadow-sm">
                            <p class="text-xs uppercase tracking-wider text-neutral-500 font-medium">Rol</p>
                            <p class="mt-0.5 text-lg font-bold text-neutral-900">
                                {{ roleLabel }}
                            </p>
                        </div>

                        <div class="rounded-xl bg-neutral-100 p-3 px-6 text-center min-w-[140px] shadow-sm">
                            <p class="text-xs uppercase tracking-wider text-neutral-500 font-medium">Club</p>
                            <p class="mt-0.5 text-lg font-bold text-neutral-900">
                                {{ userProfile.club ?? 'Sin club' }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="max-w-md mx-auto grid grid-cols-2 gap-1 p-1 bg-neutral-100/80 rounded-xl border border-neutral-200/50">
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
            <section v-if="activeTab === 'perfil'" class="space-y-6 m-6">


                <div class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm">
                    <h4 class="text-2xl font-bold text-neutral-900">
                        {{ isEntrenador ? 'Perfil de Entrenador' : 'Perfil de Socorrista' }}
                    </h4>
                    <p class="mt-2 text-neutral-600">
                        {{
                            isEntrenador
                                ? 'Aquí mostraremos tus clubes, grupos y planificación semanal.'
                                : 'Aquí mostraremos tus estadísticas, entrenamientos y progresión personal.'
                        }}
                    </p>
                    <div class="mt-4 rounded-xl border border-dashed border-neutral-300 p-4 text-sm text-neutral-500">
                        Próximamente añadiremos los módulos específicos de este rol.
                    </div>
                </div>
            </section>

            <section v-else class="space-y-6 m-6">
                <div class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm">
                    <h4 class="flex items-center gap-2 text-3xl font-bold text-neutral-900">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317a1 1 0 011.35-.936l.89.445a1 1 0 00.87 0l.89-.445a1 1 0 011.35.936l.102.987a1 1 0 00.594.804l.91.392a1 1 0 01.57 1.267l-.3.948a1 1 0 00.204.978l.678.725a1 1 0 010 1.366l-.678.725a1 1 0 00-.204.978l.3.948a1 1 0 01-.57 1.267l-.91.392a1 1 0 00-.594.804l-.102.987a1 1 0 01-1.35.936l-.89-.445a1 1 0 00-.87 0l-.89.445a1 1 0 01-1.35-.936l-.102-.987a1 1 0 00-.594-.804l-.91-.392a1 1 0 01-.57-1.267l.3-.948a1 1 0 00-.204-.978l-.678-.725a1 1 0 010-1.366l.678-.725a1 1 0 00.204-.978l-.3-.948a1 1 0 01.57-1.267l.91-.392a1 1 0 00.594-.804l.102-.987z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15a3 3 0 100-6 3 3 0 000 6z" />
                        </svg>
                        Configuración
                    </h4>

                    <div class="mt-5 overflow-hidden rounded-2xl border border-neutral-200">
                        <div class="flex items-center justify-between border-b border-neutral-200 bg-white px-5 py-4">
                            <div>
                                <p class="text-2xl font-semibold text-neutral-900">Modo Oscuro</p>
                                <p class="text-lg text-neutral-500">Tema de la aplicación</p>
                            </div>
                            <button
                                type="button"
                                class="h-9 w-16 rounded-full p-1 transition"
                                :class="darkModeEnabled ? 'bg-blue-600' : 'bg-neutral-300'"
                                @click="darkModeEnabled = !darkModeEnabled"
                            >
                                <span class="block h-7 w-7 rounded-full bg-white transition" :class="darkModeEnabled ? 'translate-x-7' : 'translate-x-0'" />
                            </button>
                        </div>

                        <div class="flex items-center justify-between border-b border-neutral-200 bg-white px-5 py-4">
                            <div>
                                <p class="text-2xl font-semibold text-neutral-900">Notificaciones</p>
                                <p class="text-lg text-neutral-500">Alertas y recordatorios</p>
                            </div>
                            <button
                                type="button"
                                class="h-9 w-16 rounded-full p-1 transition"
                                :class="notificationsEnabled ? 'bg-blue-600' : 'bg-neutral-300'"
                                @click="notificationsEnabled = !notificationsEnabled"
                            >
                                <span class="block h-7 w-7 rounded-full bg-white transition" :class="notificationsEnabled ? 'translate-x-7' : 'translate-x-0'" />
                            </button>
                        </div>

                        <button
                            type="button"
                            class="flex w-full items-center justify-between border-b border-neutral-200 bg-white px-5 py-4 text-left transition hover:bg-neutral-50"
                            @click="goToSetting('editar-perfil')"
                        >
                            <div>
                                <p class="text-2xl font-semibold text-neutral-900">Editar Perfil</p>
                                <p class="text-lg text-neutral-500">Cambiar foto y datos personales</p>
                            </div>
                        </button>

                        <button
                            type="button"
                            class="flex w-full items-center justify-between border-b border-neutral-200 bg-white px-5 py-4 text-left transition hover:bg-neutral-50"
                            @click="goToSetting('privacidad')"
                        >
                            <div>
                                <p class="text-2xl font-semibold text-neutral-900">Privacidad</p>
                                <p class="text-lg text-neutral-500">Gestionar datos y permisos</p>
                            </div>
                        </button>

                        <div class="bg-white px-5 py-4">
                            <Link
                                :href="route('logout')"
                                method="post"
                                as="button"
                                class="text-2xl font-semibold text-red-600 transition hover:text-red-700"
                            >
                                Cerrar Sesión
                            </Link>
                        </div>
                    </div>
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
