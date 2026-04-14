<script setup>
import ClubForm from '@/Pages/Clubs/Partials/ClubForm.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    description: '',
    logo: null,
});

function submit() {
    form.post(route('clubs.store'), {
        forceFormData: true,
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
                <ClubForm
                    :form="form"
                    intro="Completa el formulario para registrar tu club deportivo. Podrás gestionar tus socorristas y planificar sus entrenamientos."
                    submit-label="Crear Club"
                    processing-label="Creando..."
                    success-message="✓ Club creado exitosamente."
                    @submit="submit"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>

