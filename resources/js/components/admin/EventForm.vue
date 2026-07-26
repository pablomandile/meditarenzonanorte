<script setup lang="ts">
import ImageField from '@/components/admin/fields/ImageField.vue';
import TextareaField from '@/components/admin/fields/TextareaField.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type EventData } from '@/lib/site';
import { useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

const props = defineProps<{ event?: EventData }>();

const form = useForm<Record<string, any>>({
    _method: props.event ? 'put' : 'post',
    title: props.event?.title ?? '',
    description: props.event?.description ?? null,
    date_text: props.event?.date_text ?? null,
    starts_at: props.event?.starts_at ? props.event.starts_at.substring(0, 10) : null,
    location: props.event?.location ?? null,
    price: props.event?.price ?? null,
    cta_label: props.event?.cta_label ?? null,
    cta_url: props.event?.cta_url ?? null,
    image_path: props.event?.image_path ?? null,
    image: null as File | null,
    visible: props.event?.visible ?? true,
    show_on_home: props.event?.show_on_home ?? false,
});

function submit() {
    const url = props.event ? route('admin.events.update', props.event.id) : route('admin.events.store');

    form.transform((data) => ({ ...data, starts_at: data.starts_at || null })).post(url, {
        forceFormData: true,
        preserveScroll: true,
    });
}
</script>

<template>
    <form @submit.prevent="submit">
        <Card>
            <CardContent class="grid gap-5 pt-6">
                <div class="grid gap-2">
                    <Label>Título *</Label>
                    <Input v-model="form.title" required />
                    <p v-if="form.errors.title" class="text-sm text-red-600">{{ form.errors.title }}</p>
                </div>

                <TextareaField v-model="form.description" label="Descripción" :error="form.errors.description" />

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label>Fecha y horario (texto que se muestra)</Label>
                        <Input v-model="form.date_text" placeholder="Sábado 8 de agosto de 17 a 19 hs" />
                        <p v-if="form.errors.date_text" class="text-sm text-red-600">{{ form.errors.date_text }}</p>
                    </div>
                    <div class="grid gap-2">
                        <Label>Fecha (para ordenar)</Label>
                        <Input v-model="form.starts_at" type="date" />
                        <p v-if="form.errors.starts_at" class="text-sm text-red-600">{{ form.errors.starts_at }}</p>
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label>Lugar</Label>
                        <Input v-model="form.location" />
                    </div>
                    <div class="grid gap-2">
                        <Label>Precio</Label>
                        <Input v-model="form.price" placeholder="$5.000 / GRATUITO" />
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label>Texto del botón</Label>
                        <Input v-model="form.cta_label" placeholder="Inscripción aquí" />
                    </div>
                    <div class="grid gap-2">
                        <Label>URL del botón</Label>
                        <Input v-model="form.cta_url" placeholder="https://forms.gle/..." />
                        <p v-if="form.errors.cta_url" class="text-sm text-red-600">{{ form.errors.cta_url }}</p>
                    </div>
                </div>

                <ImageField
                    v-model="form.image_path"
                    v-model:file="form.image"
                    label="Imagen / afiche"
                    :error="form.errors.image"
                />

                <div class="flex flex-wrap gap-6">
                    <label class="flex items-center gap-2 text-sm">
                        <input v-model="form.visible" type="checkbox" class="h-4 w-4 rounded border-gray-300" />
                        Visible en el sitio
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input v-model="form.show_on_home" type="checkbox" class="h-4 w-4 rounded border-gray-300" />
                        Destacar en la página de inicio
                    </label>
                </div>
            </CardContent>
        </Card>

        <div class="mt-4 flex items-center gap-3">
            <Button type="submit" :disabled="form.processing">
                <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                {{ props.event ? 'Guardar cambios' : 'Crear evento' }}
            </Button>
            <slot name="actions" />
        </div>
    </form>
</template>
