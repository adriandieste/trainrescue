<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref, computed } from 'vue';
const props = defineProps({
    club: {
        type: Object,
        required: true,
    },
});
const confirmandoEliminacion = ref(false);
const inputConfirmacionNombre = ref(null);
const form = useForm({
    confirm_name: '',
});
const nombreCoincide = computed(
    () => form.confirm_name === props.club.name
);
const abrirModal = () => {
    confirmandoEliminacion.value = true;
    nextTick(() => inputConfirmacionNombre.value?.focus());
};
const disolverClub = () => {
    if (!nombreCoincide.value) return;

    form.delete(route('clubs.destroy', { club: props.club.id }), {
        preserveScroll: true,
        onSuccess: () => cerrarModal(),
        onError: () => nextTick(() => inputConfirmacionNombre.value?.focus()),
    });
};
const cerrarModal = () => {
    confirmandoEliminacion.value = false;
    form.clearErrors();
    form.reset();
};
</script>
<template>
    <section>
        <DangerButton @click="abrirModal">Eliminar club</DangerButton>
        <Modal :show="confirmandoEliminacion" max-width="lg" @close="cerrarModal">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-red-100">
                        <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>
                    <h2 class="text-lg font-medium text-gray-900">
                        ¿Eliminar "{{ club.name }}"?
                    </h2>
                </div>
                <p class="text-sm text-gray-600">
                    Esta acción eliminará permanentemente el club y desvinculará
                    a todos sus miembros. Para confirmar, escribe el nombre exacto
                    del club:
                </p>
                <p class="mt-2 font-semibold text-gray-800 text-sm">
                    {{ club.name }}
                </p>
                <div class="mt-4">
                    <InputLabel for="confirm_name" value="Nombre del club" class="sr-only" />
                    <TextInput
                        id="confirm_name"
                        ref="inputConfirmacionNombre"
                        v-model="form.confirm_name"
                        type="text"
                        class="mt-1 block w-full"
                        :placeholder="club.name"
                        @keyup.enter="disolverClub"
                    />
                    <InputError :message="form.errors.confirm_name" class="mt-2" />
                    <p v-if="form.confirm_name && !nombreCoincide" class="mt-1 text-xs text-red-500">
                        El nombre no coincide.
                    </p>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="cerrarModal">
                        Cancelar
                    </SecondaryButton>
                    <DangerButton
                        :class="{ 'opacity-25 cursor-not-allowed': !nombreCoincide || form.processing }"
                        :disabled="!nombreCoincide || form.processing"
                        @click="disolverClub"
                    >
                        {{ form.processing ? 'Eliminando...' : 'Sí, eliminar el club' }}
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </section>
</template>
