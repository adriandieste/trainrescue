<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const user = computed(() => usePage().props.auth.user);
const currentClub = computed(() => user.value?.club ?? null);
const currentClubLogoUrl = computed(() => currentClub.value?.logo_path ? `/storage/${currentClub.value.logo_path}` : null);
</script>

<template>
    <Head title="Dashboard Atleta" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Dashboard Atleta
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <div v-if="currentClub" class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="flex flex-col gap-4 p-6 md:flex-row md:items-start">
                        <div v-if="currentClubLogoUrl" class="h-20 w-20 shrink-0 overflow-hidden rounded-xl border border-gray-200 bg-white">
                            <img :src="currentClubLogoUrl" :alt="currentClub.name" class="h-full w-full object-cover">
                        </div>

                        <div>
                            <p class="text-sm font-medium uppercase tracking-wide text-blue-600">Tu club deportivo</p>
                            <h3 class="mt-1 text-xl font-semibold text-gray-900">{{ currentClub.name }}</h3>
                            <p class="mt-2 text-sm text-gray-600">
                                {{ currentClub.description || 'Tu entrenador todavía no ha añadido una descripción del club.' }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h1 class="text-2xl font-bold mb-4">Bienvenido, {{ user.name }}</h1>
                        <p class="text-gray-600">
                            Aquí puedes ver tus entrenamientos, tu progreso y tus sesiones asignadas.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

