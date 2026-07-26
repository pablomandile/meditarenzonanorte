<script setup lang="ts">
import EventForm from '@/components/admin/EventForm.vue';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const breadcrumbs = [
    { title: 'Eventos', href: '/admin/events' },
    { title: 'Nuevo evento', href: '/admin/events/create' },
];
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <Head title="Nuevo evento" />

        <div class="mx-auto flex w-full max-w-3xl flex-col gap-4 p-4">
            <h1 class="text-xl font-semibold">Nuevo evento</h1>

            <EventForm>
                <template #actions>
                    <Button as-child variant="ghost">
                        <Link href="/admin/events">Cancelar</Link>
                    </Button>
                </template>
            </EventForm>
        </div>
    </AdminLayout>
</template>
