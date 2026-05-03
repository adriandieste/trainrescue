<script setup>
import { computed } from 'vue';

const props = defineProps({
    clubmates: {
        type: Array,
        default: () => [],
    },
});

function getInitials(name) {
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((n) => n[0])
        .join('')
        .toUpperCase();
}
</script>

<template>
    <div class="w-full">
        <div v-if="clubmates.length === 0" class="rounded-lg border border-gray-200 bg-white p-6 text-center">
            <p class="text-gray-600">No tienes compañeros en tu club aún.</p>
        </div>

        <div v-else class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr class="border-b border-gray-200">
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-700">Miembro</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-700">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-700">Rol</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="clubmate in clubmates" :key="clubmate.id" class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div v-if="clubmate.avatar_url" class="h-10 w-10 flex-shrink-0 overflow-hidden rounded-full border border-gray-200">
                                    <img :src="clubmate.avatar_url" :alt="clubmate.name" class="h-full w-full object-cover">
                                </div>
                                <div v-else class="h-10 w-10 flex-shrink-0 rounded-full bg-blue-100 flex items-center justify-center font-semibold text-blue-600 text-sm">
                                    {{ getInitials(clubmate.name) }}
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900">{{ clubmate.name }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-gray-600">
                            {{ clubmate.email }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-block rounded-full px-3 py-1 text-sm font-medium" :class="clubmate.role === 'entrenador' ? 'bg-orange-100 text-orange-700' : 'bg-blue-100 text-blue-700'">
                                {{ clubmate.role_label }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>


