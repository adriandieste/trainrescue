<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const logoPreview = ref(null);

const form = useForm({
    name: '',
    description: '',
    logo: null,
});

function onLogoChange(event) {
    const file = event.target.files[0];
    if (!file) return;

    form.logo = file;

    const reader = new FileReader();
    reader.onload = (e) => {
        logoPreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
}

function submit() {
    form.post(route('clubs.store'), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Crear Club" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Crear Club Deportivo
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <p class="mb-6 text-sm text-gray-600">
                            Completa el formulario para registrar tu club deportivo. Podrás gestionar tus atletas y planificar sus entrenamientos.
                        </p>

                        <form @submit.prevent="submit" enctype="multipart/form-data" class="space-y-6">
                            <div>
                                <InputLabel for="name" value="Nombre del Club *" />
                                <TextInput
                                    id="name"
                                    type="text"
                                    v-model="form.name"
                                    class="mt-1 block w-full"
                                    placeholder="ej. Club de Salvamento Madrid"
                                    required
                                    autofocus
                                />
                                <InputError :message="form.errors.name" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="description" value="Descripción" />
                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    rows="4"
                                    placeholder="Describe tu club, historia, especialidades..."
                                />
                                <InputError :message="form.errors.description" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="logo" value="Logo del Club" />

                                <div class="mt-2 flex items-center gap-4">
                                    <div v-if="logoPreview || form.logo" class="h-20 w-20 shrink-0 overflow-hidden rounded-lg border-2 border-gray-300">
                                        <img
                                            :src="logoPreview"
                                            alt="Logo preview"
                                            class="h-full w-full object-cover"
                                        />
                                    </div>

                                    <div class="flex flex-col gap-2">
                                        <label
                                            for="logo"
                                            class="cursor-pointer rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-700 shadow-sm hover:bg-gray-50 transition"
                                        >
                                            Seleccionar logo
                                        </label>
                                        <input
                                            id="logo"
                                            type="file"
                                            class="sr-only"
                                            accept="image/jpeg,image/png"
                                            @change="onLogoChange"
                                        />
                                        <p class="text-xs text-gray-500">JPG o PNG · máx. 2 MB</p>
                                    </div>
                                </div>

                                <InputError :message="form.errors.logo" class="mt-2" />
                            </div>

                            <div class="flex items-center gap-4">
                                <PrimaryButton :disabled="form.processing">
                                    <span v-if="form.processing" class="flex items-center gap-1.5">
                                        <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                        </svg>
                                        Creando...
                                    </span>
                                    <span v-else>Crear Club</span>
                                </PrimaryButton>

                                <Transition
                                    enter-active-class="transition ease-in-out"
                                    enter-from-class="opacity-0"
                                    leave-active-class="transition ease-in-out"
                                    leave-to-class="opacity-0"
                                >
                                    <p v-if="form.recentlySuccessful" class="text-sm text-green-600">
                                        ✓ Club creado exitosamente.
                                    </p>
                                </Transition>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

