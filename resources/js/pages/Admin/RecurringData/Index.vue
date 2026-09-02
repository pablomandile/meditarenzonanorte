<script setup lang="ts">
import NameListCard from '@/components/admin/NameListCard.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps<{
    teachers: { id: number; name: string }[];
    venues: { id: number; name: string }[];
}>();

const breadcrumbs = [{ title: 'Datos recurrentes', href: '/admin/datos-recurrentes' }];
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <Head title="Datos recurrentes" />

        <div class="mx-auto flex w-full max-w-3xl flex-col gap-4 p-4">
            <div>
                <h1 class="text-xl font-semibold">Datos recurrentes</h1>
                <p class="text-sm text-muted-foreground">
                    Cargá acá los maestr@s y los lugares que se repiten, y después elegilos en los campos “Maestr@” y “Lugar” de las fichas de clase
                    (en Clases semanales y Cursos y Retiros), sin volver a escribirlos.
                </p>
            </div>

            <NameListCard
                title="Maestr@s"
                description="Aparecen para elegir en el campo “Maestr@” de cada ficha de clase."
                :items="teachers"
                store-route="admin.recurring.teachers.store"
                destroy-route="admin.recurring.teachers.destroy"
                placeholder="Ej.: Kelsang Panchen"
                noun="maestr@"
            />

            <NameListCard
                title="Lugares"
                description="Aparecen para elegir en el campo “Lugar” de cada ficha de clase. Poné la dirección tal como querés que se lea."
                :items="venues"
                store-route="admin.recurring.venues.store"
                destroy-route="admin.recurring.venues.destroy"
                placeholder="Ej.: Psj. Cajaraville 173, Barrio Martin, Rosario"
                noun="lugar"
            />
        </div>
    </AdminLayout>
</template>
