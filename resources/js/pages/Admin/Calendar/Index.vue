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

type CalendarCard = {
    id: number;
    page: string;
    title: string;
    dates: string;
    show_on_calendar: boolean;
    edit_url: string;
};

/** Una fila del listado, ya sea una ficha de clase o un evento. */
type Row = {
    key: string;
    page: string;
    title: string;
    detail: string;
    image: string | null;
    on: boolean;
    selectable: boolean;
    warning: string | null;
    editUrl: string;
    url: string;
};

const props = defineProps<{ cards: CalendarCard[]; events: EventData[] }>();

const breadcrumbs = [{ title: 'Calendario', href: '/admin/calendar' }];

/**
 * El tilde se guarda al instante, así que hasta que vuelve el servidor se muestra
 * el valor elegido (override) y la fila queda deshabilitada (pending).
 */
const override = ref(new Map<string, boolean>());
const pending = ref(new Set<string>());
const bulk = ref(false);

const cardRows = computed<Row[]>(() =>
    props.cards.map((card) => ({
        key: `s${card.id}`,
        page: card.page,
        title: card.title,
        detail: card.dates,
        image: null,
        on: card.show_on_calendar,
        selectable: card.dates !== '',
        warning: card.dates === '' ? 'Cargale las “Fechas para el calendario” para poder mostrarla.' : null,
        editUrl: card.edit_url,
        url: route('admin.calendar.sections.toggle', card.id),
    })),
);

const eventRows = computed<Row[]>(() =>
    props.events.map((event) => ({
        key: `e${event.id}`,
        page: 'Evento',
        title: event.title,
        detail: event.date_label ?? '',
        image: event.image_path,
        on: event.show_on_calendar,
        selectable: !!event.starts_at,
        warning: event.starts_at ? null : 'Agregale la fecha de inicio para poder mostrarlo.',
        editUrl: `/admin/events/${event.id}/edit`,
        url: route('admin.calendar.events.toggle', event.id),
    })),
);

const groups = computed(() => [
    {
        title: 'Clases, cursos y actividades',
        hint: 'Las fechas de cada una se cargan en su ficha, dentro de la página.',
        rows: cardRows.value,
        empty: 'No hay fichas de clase visibles.',
    },
    {
        title: 'Eventos',
        hint: 'Se editan desde Eventos. Los ocultos no se listan.',
        rows: eventRows.value,
        empty: 'No hay eventos visibles.',
    },
]);

const isOn = (row: Row) => override.value.get(row.key) ?? row.on;

const selectables = computed(() => [...cardRows.value, ...eventRows.value].filter((row) => row.selectable));
const checkedCount = computed(() => selectables.value.filter(isOn).length);

const masterState = computed<boolean | 'indeterminate'>(() => {
    if (!selectables.value.length || checkedCount.value === 0) return false;

    return checkedCount.value === selectables.value.length ? true : 'indeterminate';
});

function setOne(row: Row, value: boolean) {
    override.value.set(row.key, value);
    pending.value.add(row.key);

    router.patch(
        row.url,
        { show: value },
        {
            preserveScroll: true,
            // Sin preserveState el componente se vuelve a montar y se pierde el
            // marcado optimista antes de que lleguen los datos nuevos.
            preserveState: true,
            onFinish: () => {
                pending.value.delete(row.key);
                override.value.delete(row.key);
            },
        },
    );
}

function setAll(value: boolean) {
    bulk.value = true;
    selectables.value.forEach((row) => override.value.set(row.key, value));

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
                    <p class="text-sm text-muted-foreground">Elegí qué aparece en el calendario del sitio.</p>
                </div>
                <Button as-child variant="outline">
                    <a href="/calendario" target="_blank" rel="noopener"><ExternalLink class="mr-1 h-4 w-4" /> Ver el calendario</a>
                </Button>
            </div>

            <Card>
                <CardContent class="p-0">
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
                            Mostrar todo en el calendario
                        </Label>

                        <span class="shrink-0 text-sm text-muted-foreground">{{ checkedCount }} de {{ selectables.length }}</span>
                    </div>

                    <template v-for="group in groups" :key="group.title">
                        <div class="border-b bg-muted/20 px-3 py-2 sm:px-4">
                            <p class="text-sm font-medium">{{ group.title }}</p>
                            <p class="text-xs text-muted-foreground">{{ group.hint }}</p>
                        </div>

                        <p v-if="!group.rows.length" class="border-b px-4 py-4 text-sm text-muted-foreground">{{ group.empty }}</p>

                        <div v-else class="divide-y border-b">
                            <div v-for="row in group.rows" :key="row.key" class="flex items-start gap-3 px-3 py-3 sm:px-4">
                                <Checkbox
                                    :id="`cal-${row.key}`"
                                    :checked="isOn(row)"
                                    :disabled="bulk || pending.has(row.key) || !row.selectable"
                                    class="mt-1 shrink-0 focus-visible:outline-none"
                                    @update:checked="(value) => setOne(row, value)"
                                />

                                <img v-if="row.image" :src="img(row.image)" alt="" class="h-12 w-12 shrink-0 rounded-md border object-cover" />

                                <div class="min-w-0 flex-1">
                                    <p class="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">{{ row.page }}</p>
                                    <Label :for="`cal-${row.key}`" class="block truncate font-medium" :class="row.selectable ? 'cursor-pointer' : ''">
                                        {{ row.title }}
                                    </Label>

                                    <p v-if="row.warning" class="flex items-start gap-1.5 text-sm text-amber-700">
                                        <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />
                                        {{ row.warning }}
                                    </p>
                                    <p v-else class="truncate text-sm text-muted-foreground">{{ row.detail }}</p>
                                </div>

                                <LoaderCircle v-if="pending.has(row.key)" class="h-4 w-4 shrink-0 animate-spin text-muted-foreground" />

                                <Button as-child size="sm" variant="outline" class="shrink-0">
                                    <Link :href="row.editUrl" title="Editar"><Pencil class="h-4 w-4" /></Link>
                                </Button>
                            </div>
                        </div>
                    </template>
                </CardContent>
            </Card>

            <p class="text-sm text-muted-foreground">
                Lo que está oculto en el sitio no se lista acá, porque tampoco puede aparecer en el calendario.
            </p>
        </div>
    </AdminLayout>
</template>
