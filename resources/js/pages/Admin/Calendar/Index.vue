<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { img, type EventData } from '@/lib/site';
import { Head, Link, router } from '@inertiajs/vue3';
import { AlertCircle, Check, ExternalLink, LoaderCircle, Minus, Pencil } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{ events: EventData[] }>();

const breadcrumbs = [{ title: 'Calendario', href: '/admin/calendar' }];

/**
 * El tilde se guarda al instante, así que hasta que vuelve el servidor se
 * muestra el valor elegido (override) y la fila queda deshabilitada (pending).
 */
const override = ref(new Map<number, boolean>());
const pending = ref(new Set<number>());
const bulk = ref(false);

/** Sin fecha de inicio no hay día donde ubicar el evento. */
function selectable(event: EventData): boolean {
    return !!event.starts_at;
}

function isOn(event: EventData): boolean {
    return override.value.get(event.id) ?? event.show_on_calendar;
}

const selectables = computed(() => props.events.filter(selectable));
const checkedCount = computed(() => selectables.value.filter(isOn).length);

const masterState = computed<boolean | 'indeterminate'>(() => {
    if (!selectables.value.length || checkedCount.value === 0) return false;

    return checkedCount.value === selectables.value.length ? true : 'indeterminate';
});

function setOne(event: EventData, value: boolean) {
    override.value.set(event.id, value);
    pending.value.add(event.id);

    router.patch(
        route('admin.calendar.toggle', event.id),
        { show: value },
        {
            preserveScroll: true,
            // Sin preserveState el componente se vuelve a montar y se pierde el
            // marcado optimista antes de que lleguen los datos nuevos.
            preserveState: true,
            onFinish: () => {
                pending.value.delete(event.id);
                override.value.delete(event.id);
            },
        },
    );
}

function setAll(value: boolean) {
    bulk.value = true;
    selectables.value.forEach((event) => override.value.set(event.id, value));

    router.patch(
        route('admin.calendar.bulk'),
        { show: value },
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                bulk.value = false;
                override.value.clear();
            },
        },
    );
}
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <Head title="Calendario" />

        <div class="flex flex-col gap-4 p-4">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <h1 class="text-xl font-semibold">Calendario</h1>
                    <p class="text-sm text-muted-foreground">Elegí qué eventos aparecen en el calendario del sitio.</p>
                    <p class="text-sm text-muted-foreground">
                        Las clases semanales se agregan solas, con las “Fechas para el calendario” de cada ficha de clase.
                    </p>
                </div>
                <Button as-child variant="outline">
                    <a href="/calendario" target="_blank" rel="noopener"><ExternalLink class="mr-1 h-4 w-4" /> Ver el calendario</a>
                </Button>
            </div>

            <Card>
                <CardContent class="p-0">
                    <p v-if="!events.length" class="px-4 py-8 text-center text-sm text-muted-foreground">
                        No hay eventos visibles. Creá uno desde <Link href="/admin/events" class="underline">Eventos</Link>.
                    </p>

                    <template v-else>
                        <div class="flex items-center gap-3 border-b bg-muted/40 px-3 py-3 sm:px-4">
                            <Checkbox
                                id="calendar-all"
                                :checked="masterState"
                                :disabled="bulk || !selectables.length"
                                class="focus-visible:outline-none data-[state=indeterminate]:border-accent-foreground data-[state=indeterminate]:bg-primary data-[state=indeterminate]:text-primary-foreground"
                                @update:checked="setAll"
                            >
                                <Minus v-if="masterState === 'indeterminate'" class="size-3.5 stroke-[3]" />
                                <Check v-else class="size-3.5 stroke-[3]" />
                            </Checkbox>

                            <Label for="calendar-all" class="min-w-0 flex-1 cursor-pointer text-sm font-medium">
                                Mostrar todos en el calendario
                            </Label>

                            <span class="shrink-0 text-sm text-muted-foreground">{{ checkedCount }} de {{ selectables.length }}</span>
                        </div>

                        <div class="divide-y">
                            <div v-for="event in events" :key="event.id" class="flex items-start gap-3 px-3 py-3 sm:items-center sm:px-4">
                                <Checkbox
                                    :id="`calendar-${event.id}`"
                                    :checked="isOn(event)"
                                    :disabled="bulk || pending.has(event.id) || !selectable(event)"
                                    class="mt-1 shrink-0 focus-visible:outline-none sm:mt-0"
                                    @update:checked="(value) => setOne(event, value)"
                                />

                                <img
                                    v-if="event.image_path"
                                    :src="img(event.image_path)"
                                    alt=""
                                    class="h-12 w-12 shrink-0 rounded-md border object-cover"
                                />
                                <div v-else class="h-12 w-12 shrink-0 rounded-md border border-dashed"></div>

                                <div class="min-w-0 flex-1">
                                    <Label :for="`calendar-${event.id}`" class="block truncate font-medium" :class="selectable(event) ? 'cursor-pointer' : ''">
                                        {{ event.title }}
                                    </Label>

                                    <p v-if="selectable(event)" class="truncate text-sm text-muted-foreground">
                                        {{ event.date_text ?? 'Sin texto de fecha' }}
                                    </p>
                                    <p v-else class="flex items-start gap-1.5 text-sm text-amber-700">
                                        <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />
                                        Agregá la fecha de inicio para poder mostrarlo en el calendario.
                                    </p>
                                </div>

                                <LoaderCircle v-if="pending.has(event.id)" class="h-4 w-4 shrink-0 animate-spin text-muted-foreground" />

                                <Button as-child size="sm" variant="outline" class="shrink-0">
                                    <Link :href="`/admin/events/${event.id}/edit`" title="Editar el evento"><Pencil class="h-4 w-4" /></Link>
                                </Button>
                            </div>
                        </div>
                    </template>
                </CardContent>
            </Card>

            <p class="text-sm text-muted-foreground">
                Los eventos ocultos no se listan acá. Se muestran u ocultan desde <Link href="/admin/events" class="underline">Eventos</Link>.
            </p>
        </div>
    </AdminLayout>
</template>
