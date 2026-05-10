<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import ClubInvitationsBell from '@/Components/ClubInvitationsBell.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const isEntrenador = computed(() => page.props.auth?.user?.rol === 'entrenador');
const workoutRoute = computed(() => isEntrenador.value ? route('exercises.library') : route('entrenamientos.index'));
const workoutActive = computed(() => isEntrenador.value ? route().current('exercises.*') : route().current('entrenamientos.*'));
const calendarRoute = computed(() => isEntrenador.value ? route('calendar.index') : route('calendario.atleta'));
const calendarActive = computed(() => route().current('calendar.*') || route().current('calendario.*'));
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <nav class="sticky top-0 z-40 border-b border-gray-100 bg-white/95 backdrop-blur-sm">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between md:h-20">

                    <div class="flex shrink-0 items-center">
                        <Link :href="route('dashboard')">
                            <ApplicationLogo variant="horizontal" class="h-9 w-auto md:h-11" />
                        </Link>
                    </div>

                    <div class="hidden md:flex items-center gap-4 lg:gap-8">
                        <Link :href="route('dashboard')"
                              class="group flex flex-col items-center gap-1 text-[11px] font-bold uppercase tracking-wider transition"
                              :class="route().current('dashboard') ? 'text-blue-600' : 'text-gray-400 hover:text-gray-600'">
                            <svg class="h-6 w-6 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10.5L12 3l9 7.5V21a1 1 0 01-1 1h-5.5a.5.5 0 01-.5-.5V16a2 2 0 00-4 0v5.5a.5.5 0 01-.5.5H4a1 1 0 01-1-1V10.5z" /></svg>
                            Inicio
                        </Link>

                        <Link
                            :href="calendarRoute"
                            class="group flex flex-col items-center gap-1 text-[11px] font-bold uppercase tracking-wider transition"
                            :class="calendarActive ? 'text-blue-600' : 'text-gray-400 hover:text-gray-600'"
                        >
                            <svg class="h-6 w-6 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10m-12 9h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v11a2 2 0 002 2z" /></svg>
                            Calendario
                        </Link>

                        <Link
                            v-if="isEntrenador"
                            :href="route('exercises.library')"
                            class="group flex flex-col items-center gap-1 text-[11px] font-bold uppercase tracking-wider transition"
                            :class="route().current('exercises.*') ? 'text-blue-600' : 'text-gray-400 hover:text-gray-600'"
                        >
                            <svg class="h-6 w-6 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                            Entrenos
                        </Link>
                        <Link
                            v-else
                            :href="route('entrenamientos.index')"
                            class="group flex flex-col items-center gap-1 text-[11px] font-bold uppercase tracking-wider transition"
                            :class="route().current('entrenamientos.*') ? 'text-blue-600' : 'text-gray-400 hover:text-gray-600'"
                        >
                            <svg class="h-6 w-6 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                            Entrenos
                        </Link>

                        <Link :href="route('profile.edit')"
                              class="group flex flex-col items-center gap-1 text-[11px] font-bold uppercase tracking-wider transition"
                              :class="route().current('profile.*') ? 'text-blue-600' : 'text-gray-400 hover:text-gray-600'">
                            <svg class="h-6 w-6 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                            Perfil
                        </Link>
                    </div>

                    <div class="flex items-center gap-3">
                        <ClubInvitationsBell />
                        <div class="hidden h-8 w-[1px] bg-gray-200 md:block"></div>
                        <div class="flex flex-col items-end">
                            <span class="hidden text-sm font-bold text-gray-700 md:block">{{ $page.props.auth.user.name }}</span>
                            <span class="text-[10px] font-medium text-blue-500 md:text-gray-400 uppercase">En línea</span>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <div class="fixed bottom-0 left-0 z-50 h-[72px] w-full border-t border-gray-200 bg-white px-4 pb-safe md:hidden">
            <div class="mx-auto flex h-full max-w-md items-center justify-between">
                <Link :href="route('dashboard')" class="flex flex-col items-center gap-1 px-3 transition active:scale-90">
                    <svg class="h-6 w-6" :class="route().current('dashboard') ? 'text-blue-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10.5L12 3l9 7.5V21a1 1 0 01-1 1h-5.5a.5.5 0 01-.5-.5V16a2 2 0 00-4 0v5.5a.5.5 0 01-.5.5H4a1 1 0 01-1-1V10.5z" /></svg>
                    <span class="text-[10px] font-bold uppercase" :class="route().current('dashboard') ? 'text-blue-600' : 'text-gray-400'">Inicio</span>
                </Link>

                <Link
                    :href="calendarRoute"
                    class="flex flex-col items-center gap-1 px-3 transition active:scale-90"
                    :class="calendarActive ? 'text-blue-600' : 'text-gray-400'"
                >
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10m-12 9h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v11a2 2 0 002 2z" /></svg>
                    <span class="text-[10px] font-bold uppercase">Calendario</span>
                </Link>

                <Link
                    v-if="isEntrenador"
                    :href="route('exercises.library')"
                    class="flex flex-col items-center gap-1 px-3 transition active:scale-90"
                    :class="route().current('exercises.*') ? 'text-blue-600' : 'text-gray-400'"
                >
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    <span class="text-[10px] font-bold uppercase">Entrenos</span>
                </Link>
                <Link
                    v-else
                    :href="route('entrenamientos.index')"
                    class="flex flex-col items-center gap-1 px-3 transition active:scale-90"
                    :class="route().current('entrenamientos.*') ? 'text-blue-600' : 'text-gray-400'"
                >
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    <span class="text-[10px] font-bold uppercase">Entrenos</span>
                </Link>

                <Link :href="route('profile.edit')" class="flex flex-col items-center gap-1 px-3 transition active:scale-90">
                    <svg class="h-6 w-6" :class="route().current('profile.*') ? 'text-blue-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    <span class="text-[10px] font-bold uppercase" :class="route().current('profile.*') ? 'text-blue-600' : 'text-gray-400'">Perfil</span>
                </Link>
            </div>
        </div>

        <main class="pb-24 md:pb-12">
            <header class="bg-white shadow-sm" v-if="$slots.header">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <slot />
            </div>
        </main>
    </div>
</template>
