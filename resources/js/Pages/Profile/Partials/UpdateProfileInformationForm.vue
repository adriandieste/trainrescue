<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = computed(() => usePage().props.auth.user);

const form = useForm({
    _method: 'PATCH',
    name: user.value.name,
    email: user.value.email,
    avatar: null,
});

const previsualizacionAvatar = ref(null);

const urlAvatarActual = computed(() => {
    return user.value.avatar ? `/storage/${user.value.avatar}` : null;
});

const avatarMostrado = computed(() => previsualizacionAvatar.value || urlAvatarActual.value);

const inicialesUsuario = computed(() => {
    return user.value.name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
});

function alCambiarAvatar(event) {
    const file = event.target.files[0];
    if (!file) return;

    form.avatar = file;

    const reader = new FileReader();
    reader.onload = (e) => {
        previsualizacionAvatar.value = e.target.result;
    };
    reader.readAsDataURL(file);
}

function enviarFormulario() {
    form.post(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            previsualizacionAvatar.value = null;
            form.avatar = null;
        },
    });
}
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                Información del perfil
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Actualiza tu nombre, correo electrónico y foto de perfil.
            </p>
        </header>

        <form @submit.prevent="enviarFormulario" class="mt-6 space-y-6" enctype="multipart/form-data">

            <div>
                <InputLabel value="Foto de perfil" />

                <div class="mt-2 flex items-center gap-4">
                    <div class="h-20 w-20 shrink-0 overflow-hidden rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-semibold text-xl">
                        <img
                            v-if="avatarMostrado"
                            :src="avatarMostrado"
                            alt="Avatar"
                            class="h-full w-full object-cover"
                        />
                        <span v-else>{{ inicialesUsuario }}</span>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label
                            for="avatar"
                            class="cursor-pointer rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-700 shadow-sm hover:bg-gray-50 transition"
                        >
                            Seleccionar imagen
                        </label>
                        <input
                            id="avatar"
                            type="file"
                            class="sr-only"
                            accept="image/jpeg,image/png,image/webp"
                            @change="alCambiarAvatar"
                        />
                        <p class="text-xs text-gray-500">JPG, PNG o WebP · máx. 2 MB</p>
                    </div>
                </div>

                <InputError class="mt-2" :message="form.errors.avatar" />
            </div>

            <div>
                <InputLabel for="name" value="Nombre y Apellidos" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="Correo electrónico" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full bg-gray-300"
                    v-model="form.email"
                    required
                    autocomplete="username"
                    disabled
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div v-if="form.progress" class="w-full">
                <div class="mb-1 flex justify-between text-xs text-gray-500">
                    <span>Subiendo...</span>
                    <span>{{ form.progress.percentage }}%</span>
                </div>
                <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200">
                    <div
                        class="h-full rounded-full bg-indigo-500 transition-all duration-300"
                        :style="{ width: form.progress.percentage + '%' }"
                    />
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">
                    <span v-if="form.processing" class="flex items-center gap-1.5">
                        <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                        Guardando...
                    </span>
                    <span v-else>Guardar</span>
                </PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm text-gray-600">
                        ✓ Cambios guardados.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
