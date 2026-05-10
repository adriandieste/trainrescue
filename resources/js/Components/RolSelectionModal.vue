<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

defineProps({
    show: {
        type: Boolean,
        required: true,
    },
});

const selectedRol = ref(null);
const form = useForm({ rol: null });

const confirm = () => {
    if (!selectedRol.value) return;
    form.rol = selectedRol.value;
    form.post(route('onboarding.update-rol'));
};
</script>

<template>
    <Transition name="modal">
        <div
            v-if="show"
            class="fixed inset-0 z-[9999] flex items-center justify-center"
            aria-modal="true"
            role="dialog"
            aria-labelledby="modal-title"
        >
            <!-- Overlay oscuro -->
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" />

            <!-- Tarjeta modal -->
            <div class="relative z-10 w-full max-w-md mx-4 rounded-3xl bg-white shadow-2xl p-8">

                <div class="text-center mb-8">
                    <div class="mb-4 flex justify-center">
                        <span class="text-5xl">🏊</span>
                    </div>
                    <h2 id="modal-title" class="text-2xl font-bold text-slate-800">
                        ¡Bienvenido/a a Train Rescue!
                    </h2>
                    <p class="mt-2 text-slate-500 text-sm">
                        Para personalizar tu experiencia, dinos cómo vas a usar la aplicación.
                    </p>
                </div>

                <div class="flex flex-col gap-4 mb-8">
                    <button
                        type="button"
                        @click="selectedRol = 'socorrista'"
                        :class="[
                            'flex items-center gap-4 p-5 rounded-2xl border-2 text-left transition-all',
                            selectedRol === 'socorrista'
                                ? 'border-blue-500 bg-blue-50 shadow-md ring-2 ring-blue-200'
                                : 'border-slate-200 hover:border-blue-200 hover:bg-slate-50'
                        ]"
                    >
                        <div class="shrink-0 rounded-xl bg-blue-100 p-3 text-2xl">👤</div>
                        <div>
                            <p class="font-bold text-slate-800 text-lg">Soy Socorrista</p>
                            <p class="text-sm text-slate-500">Sigo mis entrenamientos y progresión personal.</p>
                        </div>
                        <div v-if="selectedRol === 'socorrista'" class="ml-auto shrink-0 text-blue-500">
                            <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </button>

                    <button
                        type="button"
                        @click="selectedRol = 'entrenador'"
                        :class="[
                            'flex items-center gap-4 p-5 rounded-2xl border-2 text-left transition-all',
                            selectedRol === 'entrenador'
                                ? 'border-orange-400 bg-orange-50 shadow-md ring-2 ring-orange-200'
                                : 'border-slate-200 hover:border-orange-200 hover:bg-slate-50'
                        ]"
                    >
                        <div class="shrink-0 rounded-xl bg-orange-100 p-3 text-2xl">📋</div>
                        <div>
                            <p class="font-bold text-slate-800 text-lg">Soy Entrenador/a</p>
                            <p class="text-sm text-slate-500">Planifico entrenamientos y gestiono mi club.</p>
                        </div>
                        <div v-if="selectedRol === 'entrenador'" class="ml-auto shrink-0 text-orange-500">
                            <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </button>
                </div>

                <button
                    type="button"
                    @click="confirm"
                    :disabled="!selectedRol || form.processing"
                    :class="[
                        'w-full py-4 rounded-2xl font-bold text-white text-lg transition-all',
                        selectedRol
                            ? 'bg-blue-600 hover:bg-blue-700 shadow-lg shadow-blue-500/30 cursor-pointer'
                            : 'bg-slate-300 cursor-not-allowed'
                    ]"
                >
                    <span v-if="form.processing">Guardando…</span>
                    <span v-else>Continuar →</span>
                </button>

                <p class="mt-4 text-center text-xs text-slate-400">
                    Podrás cambiar este ajuste más adelante desde tu perfil.
                </p>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>

