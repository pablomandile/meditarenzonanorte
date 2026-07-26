<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { img, type EventData } from '@/lib/site';
import { Head, Link, router } from '@inertiajs/vue3';
import { Eye, EyeOff, Home, Pencil, Plus, Trash2 } from 'lucide-vue-next';

defineProps<{ events: EventData[] }>();

const breadcrumbs = [{ title: 'Eventos', href: '/admin/events' }];

function toggle(id: number) {
    router.patch(route('admin.events.toggle', id), {}, { preserveScroll: true });
}

function toggleHome(id: number) {
    router.patch(route('admin.events.toggle-home', id), {}, { preserveScroll: true });
}

function destroy(event: EventData) {
    if (confirm(`¿Eliminar el evento "${event.title}"? Esta acción no se puede deshacer.`)) {
        router.delete(route('admin.events.destroy', event.id), { preserveScroll: true });
    }
}
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <Head title="Eventos" />

        <div class="flex flex-col gap-4 p-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h1 class="text-xl font-semibold">Eventos especiales</h1>
                    <p class="text-sm text-muted-foreground">
                        Se muestran en la página Eventos especiales y, si los destacás, en el inicio.
                    </p>
                </div>
                <Button as-child>
                    <Link href="/admin/events/create"><Plus class="mr-1 h-4 w-4" /> Nuevo evento</Link>
                </Button>
            </div>

            <Card>
                <CardContent class="p-0">
                    <p v-if="!events.length" class="px-4 py-8 text-center text-sm text-muted-foreground">
                        No hay eventos. Creá el primero con “Nuevo evento”.
                    </p>

                    <div class="divide-y">
                        <div
                            v-for="event in events"
                            :key="event.id"
                            class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:gap-4"
                            :class="{ 'opacity-50': !event.visible }"
                        >
                            <div class="flex min-w-0 flex-1 items-center gap-3">
                                <img
                                    v-if="event.image_path"
                                    :src="img(event.image_path)"
                                    alt=""
                                    class="h-14 w-14 shrink-0 rounded-md border object-cover"
                                />
                                <div v-else class="h-14 w-14 shrink-0 rounded-md border border-dashed"></div>

                                <div class="min-w-0">
                                    <p class="truncate font-medium">{{ event.title }}</p>
                                    <p class="truncate text-sm text-muted-foreground">{{ event.date_text }}</p>
                                </div>
                            </div>

                            <div class="flex shrink-0 items-center justify-end gap-1">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    :class="{ 'text-brand-sky': event.show_on_home }"
                                    :title="event.show_on_home ? 'Quitar del inicio' : 'Destacar en el inicio'"
                                    @click="toggleHome(event.id)"
                                >
                                    <Home class="h-4 w-4" :class="event.show_on_home ? 'fill-current' : ''" />
                                </Button>

                                <Button
                                    variant="ghost"
                                    size="sm"
                                    :title="event.visible ? 'Ocultar' : 'Mostrar'"
                                    @click="toggle(event.id)"
                                >
                                    <Eye v-if="event.visible" class="h-4 w-4" />
                                    <EyeOff v-else class="h-4 w-4 text-red-500" />
                                </Button>

                                <Button as-child size="sm" variant="outline">
                                    <Link :href="`/admin/events/${event.id}/edit`"><Pencil class="h-4 w-4" /></Link>
                                </Button>

                                <Button variant="ghost" size="sm" class="text-red-600" title="Eliminar" @click="destroy(event)">
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
