<script setup>
import GeneralLayout from '@/Layouts/GeneralLayout.vue';
import FormularioClub from '@/Pages/Clubes/Partials/FormularioClub.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    club: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    name: props.club.name ?? '',
    description: props.club.description ?? '',
    logo: null,
});

function enviarFormulario() {
    form.patch(route('clubs.update', { club: props.club.id }), {
        forceFormData: true,
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="`Editar Club - ${club.name}`" />

    <GeneralLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Editar Club Deportivo
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <FormularioClub
                    :form="form"
                    :initial-logo-url="club.logo_url"
                    intro="Actualiza los datos de tu club deportivo. Si subes un nuevo logo, el anterior se reemplazará automáticamente."
                    submit-label="Guardar cambios"
                    processing-label="Guardando..."
                    success-message="✓ Club actualizado exitosamente."
                    logo-help-text="JPG o PNG · máx. 2 MB · si subes uno nuevo se reemplazará el actual"
                    @submit="enviarFormulario"
                />
            </div>
        </div>
    </GeneralLayout>
</template>


