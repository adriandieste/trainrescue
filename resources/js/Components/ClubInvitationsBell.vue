<script setup>
import Dropdown from '@/Components/Dropdown.vue';
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const processingAction = ref(null);

const invitations = computed(() => page.props.notifications?.clubInvitations ?? []);
const invitationsCount = computed(() => page.props.notifications?.clubInvitationsCount ?? 0);

function processInvitation(invitationId, action) {
    processingAction.value = `${action}-${invitationId}`;

    router.post(route(`club-invitations.${action}`, { clubInvitation: invitationId }), {}, {
        preserveScroll: true,
        onFinish: () => {
            processingAction.value = null;
        },
    });
}
</script>

<template>
    <Dropdown align="right" width="72" content-classes="py-2 bg-white">
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
                    v-if="invitationsCount > 0"
                    class="absolute -right-1 -top-1 inline-flex min-h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1 text-[11px] font-semibold text-white"
                >
                    {{ invitationsCount > 9 ? '9+' : invitationsCount }}
                </span>
            </button>
        </template>

        <template #content>
            <div class="max-h-[28rem] overflow-y-auto px-4">
                <p class="px-1 pb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">
                    Notificaciones
                </p>

                <div v-if="invitations.length === 0" class="rounded-lg bg-gray-50 px-3 py-2 text-sm text-gray-600">
                    No tienes notificaciones pendientes.
                </div>

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
        </template>
    </Dropdown>
</template>


