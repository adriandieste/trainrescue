<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import ClubInvitationsBell from '@/Components/ClubInvitationsBell.vue';
import RolSelectionModal from '@/Components/RolSelectionModal.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useTheme } from '@/Composables/useTheme';

const page = usePage();
const { isDark, toggleTheme } = useTheme();
const isEntrenador = computed(() => page.props.auth?.user?.rol === 'entrenador');
const mustSelectRole = computed(() => page.props.mustSelectRole === true);
const calendarRoute = computed(() => isEntrenador.value ? route('calendar.index') : route('calendario.atleta'));
const calendarActive = computed(() => route().current('calendar.*') || route().current('calendario.*'));
</script>

<template>
    <div class="min-h-screen bg-gray-100 dark:bg-black">
        <nav class="sticky top-0 z-40 border-b border-gray-100 dark:border-zinc-800 bg-white/95 dark:bg-black/80 backdrop-blur-sm">
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
                              :class="route().current('dashboard') ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400 dark:text-zinc-500 hover:text-gray-600 dark:hover:text-zinc-300'">
                            <svg class="h-6 w-6 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10.5L12 3l9 7.5V21a1 1 0 01-1 1h-5.5a.5.5 0 01-.5-.5V16a2 2 0 00-4 0v5.5a.5.5 0 01-.5.5H4a1 1 0 01-1-1V10.5z" /></svg>
                            Inicio
                        </Link>

                        <Link
                            :href="calendarRoute"
                            class="group flex flex-col items-center gap-1 text-[11px] font-bold uppercase tracking-wider transition"
                            :class="calendarActive ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400 dark:text-zinc-500 hover:text-gray-600 dark:hover:text-zinc-300'"
                        >
                            <svg class="h-6 w-6 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10m-12 9h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v11a2 2 0 002 2z" /></svg>
                            Calendario
                        </Link>

                        <Link
                            v-if="isEntrenador"
                            :href="route('exercises.library')"
                            class="group flex flex-col items-center gap-1 text-[11px] font-bold uppercase tracking-wider transition"
                            :class="route().current('exercises.*') ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400 dark:text-zinc-500 hover:text-gray-600 dark:hover:text-zinc-300'"
                        >
                            <svg class="h-6 w-6 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                            Entrenos
                        </Link>
                        <Link
                            v-else
                            :href="route('entrenamientos.index')"
                            class="group flex flex-col items-center gap-1 text-[11px] font-bold uppercase tracking-wider transition"
                            :class="route().current('entrenamientos.*') ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400 dark:text-zinc-500 hover:text-gray-600 dark:hover:text-zinc-300'"
                        >
                            <svg class="h-6 w-6 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                            Entrenos
                        </Link>

                        <Link :href="route('profile.edit')"
                              class="group flex flex-col items-center gap-1 text-[11px] font-bold uppercase tracking-wider transition"
                              :class="route().current('profile.*') ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400 dark:text-zinc-500 hover:text-gray-600 dark:hover:text-zinc-300'">
                            <svg class="h-6 w-6 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                            Perfil
                        </Link>

                        <!-- Botón Toggle Tema Oscuro -->
                        <button
                            @click="toggleTheme"
                            class="ml-4 p-2 rounded-lg text-gray-400 dark:text-zinc-500 hover:text-gray-600 hover:bg-gray-200 dark:hover:text-zinc-300 dark:hover:bg-zinc-900 transition"
                            :title="isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
                        >
                            <svg v-if="!isDark" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
                            <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1m-16 0H1m15.364 1.636l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                        </button>
                    </div>

                    <div class="flex items-center gap-3">
                        <ClubInvitationsBell />
                        <div class="hidden h-8 w-[1px] bg-gray-200 dark:bg-zinc-800 md:block"></div>
                        <div class="flex flex-col items-end gap-1">
                            <span class="hidden text-sm font-bold text-gray-700 dark:text-zinc-100 md:block">{{ $page.props.auth.user.name }}</span>
                            <!-- Link de cierre de sesión reemplazando "En línea" -->
                            <Link :href="route('logout')" method="post" as="button" class="text-xs font-medium text-gray-500 dark:text-zinc-400 hover:text-red-600 dark:hover:text-red-500 uppercase transition inline-flex items-center gap-1">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                                Salir
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <div class="fixed bottom-0 left-0 z-50 h-[72px] w-full border-t border-gray-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 px-4 pb-safe md:hidden">
            <div class="mx-auto flex h-full max-w-md items-center justify-between">
                <Link :href="route('dashboard')" class="flex flex-col items-center gap-1 px-3 transition active:scale-90">
                    <svg class="h-6 w-6" :class="route().current('dashboard') ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400 dark:text-zinc-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10.5L12 3l9 7.5V21a1 1 0 01-1 1h-5.5a.5.5 0 01-.5-.5V16a2 2 0 00-4 0v5.5a.5.5 0 01-.5.5H4a1 1 0 01-1-1V10.5z" /></svg>
                    <span class="text-[10px] font-bold uppercase" :class="route().current('dashboard') ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400 dark:text-zinc-500'">Inicio</span>
                </Link>

                <Link
                    :href="calendarRoute"
                    class="flex flex-col items-center gap-1 px-3 transition active:scale-90"
                    :class="calendarActive ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400 dark:text-zinc-500'"
                >
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10m-12 9h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v11a2 2 0 002 2z" /></svg>
                    <span class="text-[10px] font-bold uppercase dark:text-zinc-400">Calendario</span>
                </Link>

                <Link
                    v-if="isEntrenador"
                    :href="route('exercises.library')"
                    class="flex flex-col items-center gap-1 px-3 transition active:scale-90"
                    :class="route().current('exercises.*') ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400 dark:text-zinc-500'"
                >
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    <span class="text-[10px] font-bold uppercase">Entrenos</span>
                </Link>
                <Link
                    v-else
                    :href="route('entrenamientos.index')"
                    class="flex flex-col items-center gap-1 px-3 transition active:scale-90"
                    :class="route().current('entrenamientos.*') ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400 dark:text-zinc-500'"
                >
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    <span class="text-[10px] font-bold uppercase">Entrenos</span>
                </Link>

                <Link :href="route('profile.edit')" class="flex flex-col items-center gap-1 px-3 transition active:scale-90">
                    <svg class="h-6 w-6" :class="route().current('profile.*') ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400 dark:text-zinc-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    <span class="text-[10px] font-bold uppercase" :class="route().current('profile.*') ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400 dark:text-zinc-500'">Perfil</span>
                </Link>
            </div>
        </div>

        <main class="pb-24 md:pb-12">
            <header class="bg-white dark:bg-zinc-950 dark:border-b dark:border-zinc-800 shadow-sm" v-if="$slots.header">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <slot />
            </div>
        </main>

        <!-- [TRA-397/398] Modal obligatorio de selección de rol para usuarios nuevos -->
        <RolSelectionModal :show="mustSelectRole" />
    </div>
</template>
