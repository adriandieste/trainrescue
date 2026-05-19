<script setup>
import Dropdown from '@/Components/Dropdown.vue';
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const processingAction = ref(null);

const invitations        = computed(() => page.props.notifications?.clubInvitations ?? []);
const workoutNotifs      = computed(() => page.props.notifications?.workoutNotifications ?? []);
const totalCount         = computed(() => page.props.notifications?.totalCount ?? 0);

function processInvitation(invitationId, action) {
    processingAction.value = `${action}-${invitationId}`;
    router.post(route(`club-invitations.${action}`, { clubInvitation: invitationId }), {}, {
        preserveScroll: true,
        onFinish: () => { processingAction.value = null; },
    });
}

function markAsRead(id) {
    router.patch(route('notifications.mark-read', id), {}, { preserveScroll: true });
}

function markAllAsRead() {
    router.patch(route('notifications.mark-all-read'), {}, { preserveScroll: true });
}
</script>

<template>
    <Dropdown align="right" width="96" content-classes="py-2 bg-white">
        <template #trigger>
            <button
                type="button"
                class="relative inline-flex h-10 w-10 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50 hover:text-gray-700"
                aria-label="Notificaciones"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V4a2 2 0 10-4 0v1.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>

                <span
                    v-if="totalCount > 0"
                    class="absolute -right-1 -top-1 inline-flex min-h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1 text-[11px] font-semibold text-white"
                >
                    {{ totalCount > 9 ? '9+' : totalCount }}
                </span>
            </button>
        </template>

        <template #content>
            <div class="max-h-[48rem] overflow-y-auto">
                <!-- Cabecera -->
                <div class="flex items-center justify-between border-b border-gray-100 px-4 py-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Notificaciones</p>
                    <button
                        v-if="workoutNotifs.length > 0"
                        type="button"
                        class="text-[11px] font-medium text-orange-600 underline hover:text-orange-800"
                        @click="markAllAsRead"
                    >
                        Marcar todas como leídas
                    </button>
                </div>

                <div class="px-3 py-2 space-y-2">
                    <!-- Sin notificaciones -->
                    <div v-if="totalCount === 0" class="rounded-lg bg-gray-50 px-3 py-3 text-sm text-gray-500 text-center">
                        No tienes notificaciones pendientes.
                    </div>

                    <!-- Notificaciones de nuevos entrenamientos -->
                    <div v-if="workoutNotifs.length > 0">
                        <p class="px-1 pt-1 pb-1 text-[11px] font-bold uppercase tracking-wide text-orange-600">
                            Nuevos entrenamientos
                        </p>
                        <div
                            v-for="notif in workoutNotifs"
                            :key="notif.id"
                            class="mb-2 flex items-start justify-between gap-3 rounded-lg border border-orange-100 bg-orange-50 p-3"
                        >
                            <div class="flex items-start gap-2">
                                <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-orange-100 text-orange-600">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </span>
                                <div>
                                    <p class="text-xs font-semibold text-gray-900">{{ notif.workout_title }}</p>
                                    <p class="mt-0.5 text-[11px] text-gray-500">
                                        <span v-if="notif.workout_date_formatted">📅 {{ notif.workout_date_formatted }} · </span>
                                        {{ notif.created_at }}
                                    </p>
                                </div>
                            </div>
                            <button
                                type="button"
                                class="shrink-0 rounded border border-orange-200 bg-white px-2 py-0.5 text-[11px] font-medium text-orange-700 hover:bg-orange-50"
                                @click="markAsRead(notif.id)"
                            >
                                ✓ Leído
                            </button>
                        </div>
                    </div>

                    <!-- Invitaciones a clubs -->
                    <div v-if="invitations.length > 0">
                        <p class="px-1 pt-1 pb-1 text-[11px] font-bold uppercase tracking-wide text-blue-600">
                            Invitaciones a clubs
                        </p>
                        <div
                            v-for="invitation in invitations"
                            :key="invitation.id"
                            class="mb-2 rounded-lg border border-gray-200 p-3"
                        >
                            <p class="text-sm font-semibold text-gray-900">Invitación a {{ invitation.club.name }}</p>
                            <p class="mt-1 text-xs text-gray-600">{{ invitation.trainer.name }} ({{ invitation.trainer.email }})</p>
                            <p class="mt-1 text-xs text-gray-500">{{ invitation.created_at }}</p>

                            <div class="mt-3 flex gap-2">
                                <button
                                    type="button"
                                    class="inline-flex items-center rounded-md bg-blue-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                                    :disabled="processingAction !== null"
                                    @click="processInvitation(invitation.id, 'accept')"
                                >
                                    {{ processingAction === `accept-${invitation.id}` ? 'Aceptando...' : 'Aceptar' }}
                                </button>

                                <button
                                    type="button"
                                    class="inline-flex items-center rounded-md bg-red-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
                                    :disabled="processingAction !== null"
                                    @click="processInvitation(invitation.id, 'reject')"
                                >
                                    {{ processingAction === `reject-${invitation.id}` ? 'Ignorando...' : 'Ignorar' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </Dropdown>
</template>

