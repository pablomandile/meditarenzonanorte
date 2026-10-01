<script setup lang="ts">
import ImageField from '@/components/admin/fields/ImageField.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { CalendarDays, Check, ImageIcon, LoaderCircle } from 'lucide-vue-next';

type PopupValues = {
    enabled: boolean;
    mode: 'once' | 'until_dismissed';
    kind: 'image' | 'event';
    text: string | null;
    event_id: number | null;
    image_path: string | null;
};

const props = defineProps<{
    popup: PopupValues;
    events: { id: number; title: string; date_label: string | null }[];
}>();

const breadcrumbs = [{ title: 'Mensaje Pop-up', href: '/admin/popup' }];

const form = useForm({
    enabled: props.popup.enabled,
    mode: props.popup.mode,
    kind: props.popup.kind,
    text: props.popup.text ?? '',
    event_id: props.popup.event_id,
    image_path: props.popup.image_path,
    files: { image: null } as { image: File | null },
});

function submit() {
    form.transform((data) => {
        const files: Record<string, any> = { ...data.files };
        if (files.image === null) delete files.image;

        return { ...data, enabled: data.enabled ? 1 : 0, files };
    }).post(route('admin.popup.update'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.files = { image: null };
            form.image_path = props.popup.image_path;
            form.defaults();
        },
    });
}

/** Mismo gesto que el interruptor de "Estado del sitio" en Ajustes: tarjetas que dicen qué pasa. */
const estados = [
    { value: false, name: 'Apagado', note: 'La portada se ve como siempre.' },
    { value: true, name: 'Encendido', note: 'Las visitas ven el pop-up al entrar a la portada.' },
];

const modos = [
    { value: 'once', name: 'Mostrar 1 vez', note: 'Cada persona lo ve una sola vez, aunque no lo cierre.' },
    {
        value: 'until_dismissed',
        name: 'Hasta «No mostrar más»',
        note: 'Vuelve en cada visita hasta que la persona toque «No mostrar más».',
    },
] as const;

const tipos = [
    { value: 'image', icon: ImageIcon, name: 'Imagen y texto', note: 'Una imagen tuya con un texto opcional.' },
    { value: 'event', icon: CalendarDays, name: 'Evento especial', note: 'Uno de los eventos cargados, con su imagen y su botón.' },
] as const;

const cardClass = (active: boolean) => (active ? 'border-primary ring-1 ring-primary' : 'border-input');
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <Head title="Mensaje Pop-up" />

        <div class="mx-auto flex w-full max-w-3xl flex-col gap-4 p-4">
            <div>
                <h1 class="text-xl font-semibold">Mensaje Pop-up</h1>
                <p class="text-sm text-muted-foreground">
                    Una ventana que aparece al entrar a la portada. Cada vez que guardás, vuelve a aparecerle también a quien ya la había cerrado.
                </p>
            </div>

            <form @submit.prevent="submit">
                <Card>
                    <CardContent class="grid gap-4 pt-6">
                        <Label>Estado</Label>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <button
                                v-for="estado in estados"
                                :key="String(estado.value)"
                                type="button"
                                class="grid gap-2 rounded-lg border p-4 text-left transition hover:bg-accent"
                                :class="cardClass(form.enabled === estado.value)"
                                @click="form.enabled = estado.value"
                            >
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-sm font-medium">{{ estado.name }}</span>
                                    <Check v-if="form.enabled === estado.value" class="h-4 w-4 shrink-0 text-primary" />
                                </div>
                                <span class="text-xs text-muted-foreground">{{ estado.note }}</span>
                            </button>
                        </div>
                    </CardContent>
                </Card>

                <Card class="mt-4">
                    <CardContent class="grid gap-4 pt-6">
                        <Label>Cuántas veces se muestra</Label>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <button
                                v-for="modo in modos"
                                :key="modo.value"
                                type="button"
                                class="grid gap-2 rounded-lg border p-4 text-left transition hover:bg-accent"
                                :class="cardClass(form.mode === modo.value)"
                                @click="form.mode = modo.value"
                            >
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-sm font-medium">{{ modo.name }}</span>
                                    <Check v-if="form.mode === modo.value" class="h-4 w-4 shrink-0 text-primary" />
                                </div>
                                <span class="text-xs text-muted-foreground">{{ modo.note }}</span>
                            </button>
                        </div>
                        <p class="text-xs text-muted-foreground">En los dos casos la ventana tiene una X para cerrarla.</p>
                    </CardContent>
                </Card>

                <Card class="mt-4">
                    <CardContent class="grid gap-5 pt-6">
                        <Label>Contenido</Label>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <button
                                v-for="tipo in tipos"
                                :key="tipo.value"
                                type="button"
                                class="grid gap-2 rounded-lg border p-4 text-left transition hover:bg-accent"
                                :class="cardClass(form.kind === tipo.value)"
                                @click="form.kind = tipo.value"
                            >
                                <div class="flex items-center justify-between gap-2">
                                    <span class="flex items-center gap-2 text-sm font-medium">
                                        <component :is="tipo.icon" class="h-4 w-4 shrink-0" />
                                        {{ tipo.name }}
                                    </span>
                                    <Check v-if="form.kind === tipo.value" class="h-4 w-4 shrink-0 text-primary" />
                                </div>
                                <span class="text-xs text-muted-foreground">{{ tipo.note }}</span>
                            </button>
                        </div>

                        <ImageField
                            v-if="form.kind === 'image'"
                            v-model="form.image_path"
                            v-model:file="form.files.image"
                            label="Imagen"
                            :error="form.errors['files.image' as keyof typeof form.errors]"
                        />

                        <div v-else class="grid gap-2">
                            <Label for="popup_event">Evento</Label>
                            <select
                                id="popup_event"
                                v-model="form.event_id"
                                class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                            >
                                <option :value="null" disabled>Elegí un evento…</option>
                                <option v-for="event in events" :key="event.id" :value="event.id">
                                    {{ event.title }}{{ event.date_label ? ` — ${event.date_label}` : '' }}
                                </option>
                            </select>
                            <p class="text-xs text-muted-foreground">
                                Se muestra con su imagen, su fecha y su botón. Solo aparecen los eventos visibles; si lo ocultás después, el pop-up
                                deja de salir.
                                <Link :href="route('admin.events.index')" class="underline underline-offset-2">Ir a Eventos</Link>
                            </p>
                            <p v-if="form.errors.event_id" class="text-sm text-red-600">{{ form.errors.event_id }}</p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="popup_text">Texto <span class="font-normal text-muted-foreground">(opcional)</span></Label>
                            <textarea
                                id="popup_text"
                                v-model="form.text"
                                rows="3"
                                maxlength="1000"
                                class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                            ></textarea>
                            <p v-if="form.errors.text" class="text-sm text-red-600">{{ form.errors.text }}</p>
                        </div>
                    </CardContent>
                </Card>

                <div class="mt-4">
                    <Button type="submit" :disabled="form.processing">
                        <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                        Guardar pop-up
                    </Button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
