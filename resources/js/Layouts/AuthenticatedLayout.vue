<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import ClubInvitationsBell from '@/Components/ClubInvitationsBell.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div>
        <div class="min-h-screen bg-gray-100">
            <nav class="border-b border-gray-100 bg-white">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="relative flex h-24 items-center justify-center">
                        <div class="absolute left-0 flex shrink-0 items-center h-full max-h-16">
                            <Link :href="route('dashboard')" class="h-full flex items-center py-1">
                                <ApplicationLogo
                                    variant="horizontal"
                                    class="block h-12 w-auto text-gray-800"
                                />
                            </Link>
                        </div>

                        <div class="flex items-center gap-8 text-xs font-semibold text-gray-400">
                            <Link
                                :href="route('dashboard')"
                                class="flex min-w-[84px] flex-col items-center gap-1 transition"
                                :class="route().current('dashboard') ? 'text-blue-600' : 'hover:text-gray-600'"
                            >
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10.5L12 3l9 7.5V21a1 1 0 01-1 1h-5.5a.5.5 0 01-.5-.5V16a2 2 0 00-4 0v5.5a.5.5 0 01-.5.5H4a1 1 0 01-1-1V10.5z" />
                                </svg>
                                Inicio
                            </Link>

                            <span class="flex min-w-[84px] flex-col items-center gap-1 text-gray-400">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10m-12 9h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v11a2 2 0 002 2z" />
                                </svg>
                                Calendario
                            </span>

                            <span class="flex min-w-[84px] flex-col items-center gap-1 text-gray-400">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 9h2v6H5zM17 9h2v6h-2zM7 11h10M7 13h10M9 8V6M15 8V6M9 18v-2M15 18v-2" />
                                </svg>
                                Entrenamientos
                            </span>

                            <Link
                                :href="route('profile.edit')"
                                class="flex min-w-[84px] flex-col items-center gap-1 transition"
                                :class="route().current('profile.*') ? 'text-blue-600' : 'hover:text-gray-600'"
                            >
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 21a8 8 0 10-16 0M12 11a4 4 0 100-8 4 4 0 000 8z" />
                                </svg>
                                Perfil
                            </Link>
                        </div>

                        <div class="absolute right-0 hidden items-center gap-3 sm:flex">
                            <ClubInvitationsBell />
                            <span class="text-sm font-medium text-gray-500">
                                {{ $page.props.auth.user.name }}
                            </span>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header
                class="bg-white shadow"
                v-if="$slots.header"
            >
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
