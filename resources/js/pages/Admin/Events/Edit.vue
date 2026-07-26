<script setup lang="ts">
import EventForm from '@/components/admin/EventForm.vue';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type EventData } from '@/lib/site';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps<{ event: EventData }>();

const breadcrumbs = [
    { title: 'Eventos', href: '/admin/events' },
    { title: props.event.title, href: `/admin/events/${props.event.id}/edit` },
];
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Editar — ${event.title}`" />

        <div class="mx-auto flex w-full max-w-3xl flex-col gap-4 p-4">
            <h1 class="text-xl font-semibold">Editar evento</h1>

            <EventForm :event="event">
                <template #actions>
                    <Button as-child variant="ghost">
                        <Link href="/admin/events">Volver</Link>
                    </Button>
                </template>
            </EventForm>
        </div>
    </AdminLayout>
</template>
