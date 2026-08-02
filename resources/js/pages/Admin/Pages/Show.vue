<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { useConfirm } from '@/composables/useConfirm';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, Copy, Eye, EyeOff, LoaderCircle, Pencil } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    page: { id: number; slug: string; title: string; meta_description: string | null; preview_title: string; url: string };
    sections: {
        id: number;
        type: string;
        type_label: string;
        key: string;
        title: string | null;
        position: number;
        visible: boolean;
    }[];
}>();

const breadcrumbs = [
    { title: 'Páginas', href: '/admin/pages' },
    { title: props.page.title, href: `/admin/pages/${props.page.id}` },
];

const { confirm } = useConfirm();

/**
 * Los buscadores cortan la descripción alrededor de los 160 caracteres, así que el
 * contador es una guía y no un límite: el máximo real (500) es el de la columna.
 */
const RECOMENDADOS = 160;

const meta = useForm({ meta_description: props.page.meta_description ?? '' });

const largo = computed(() => meta.meta_description.trim().length);
const cortada = computed(() => largo.value > RECOMENDADOS);

function toggle(id: number) {
    router.patch(route('admin.sections.toggle', id), {}, { preserveScroll: true });
}

function move(id: number, direction: 'up' | 'down') {
    router.patch(route('admin.sections.move', id), { direction }, { preserveScroll: true });
}

async function duplicate(section: { id: number; title: string | null; type_label: string }) {
    const name = section.title || section.type_label;

    const accepted = await confirm({
        title: 'Clonar sección',
        description: `Se agrega una copia de “${name}” justo debajo, oculta hasta que la muestres.`,
        confirmLabel: 'Clonar',
    });

    if (accepted) {
        router.post(route('admin.sections.duplicate', section.id), {}, { preserveScroll: true });
    }
}
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Secciones — ${page.title}`" />

        <div class="flex flex-col gap-4 p-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h1 class="text-xl font-semibold">{{ page.title }}</h1>
                    <p class="text-sm text-muted-foreground">Ordená, ocultá o editá las secciones. Los cambios se ven al instante en el sitio.</p>
                </div>
                <Button as-child variant="outline">
                    <a :href="page.slug === 'home' ? '/' : `/${page.slug}`" target="_blank">Ver página</a>
                </Button>
            </div>

            <!--
                Lo único de la página que se edita acá: el título, el slug y la
                etiqueta del menú siguen viniendo del archivo de datos.
            -->
            <Card>
                <CardContent class="grid gap-3 p-4">
                    <div>
                        <Label for="meta_description">Descripción para buscadores</Label>
                        <p class="text-sm text-muted-foreground">
                            Es el texto que se lee debajo del título en Google y al compartir el enlace por WhatsApp.
                        </p>
                    </div>

                    <textarea
                        id="meta_description"
                        v-model="meta.meta_description"
                        rows="3"
                        maxlength="500"
                        placeholder="Ej.: Clases de meditación kadampa en Zona Norte, abiertas a todo el mundo y sin experiencia previa."
                        class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                    ></textarea>

                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-xs" :class="cortada ? 'text-amber-700' : 'text-muted-foreground'">
                            {{ largo }} de {{ RECOMENDADOS }} caracteres recomendados<span v-if="cortada">
                                — de acá en adelante los buscadores lo cortan</span>
                        </p>

                        <Button
                            size="sm"
                            :disabled="meta.processing || !meta.isDirty"
                            @click="meta.patch(route('admin.pages.meta', page.id), { preserveScroll: true })"
                        >
                            <LoaderCircle v-if="meta.processing" class="mr-1 h-4 w-4 animate-spin" />
                            Guardar descripción
                        </Button>
                    </div>

                    <p v-if="meta.errors.meta_description" class="text-sm text-red-600">{{ meta.errors.meta_description }}</p>

                    <!-- Cómo se va a ver, con el título que acompaña a la descripción. -->
                    <div class="rounded-lg border bg-muted/30 p-3">
                        <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Vista previa</p>
                        <p class="mt-1.5 truncate text-sm text-muted-foreground">{{ page.url }}</p>
                        <p class="truncate font-medium text-blue-700 dark:text-blue-400">{{ page.preview_title }}</p>
                        <p class="line-clamp-2 text-sm text-muted-foreground">
                            {{ meta.meta_description.trim() || 'Sin descripción: se comparte sólo el título y el enlace.' }}
                        </p>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="p-0">
                    <div class="divide-y">
                        <div
                            v-for="(section, index) in sections"
                            :key="section.id"
                            class="flex items-center gap-3 overflow-hidden px-4 py-3"
                            :class="{ 'opacity-50': !section.visible }"
                        >
                            <div class="flex flex-col">
                                <button
                                    class="rounded p-0.5 text-muted-foreground hover:bg-muted disabled:opacity-30"
                                    :disabled="index === 0"
                                    title="Subir"
                                    @click="move(section.id, 'up')"
                                >
                                    <ArrowUp class="h-4 w-4" />
                                </button>
                                <button
                                    class="rounded p-0.5 text-muted-foreground hover:bg-muted disabled:opacity-30"
                                    :disabled="index === sections.length - 1"
                                    title="Bajar"
                                    @click="move(section.id, 'down')"
                                >
                                    <ArrowDown class="h-4 w-4" />
                                </button>
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium">
                                    {{ section.title || section.type_label }}
                                </p>
                                <p class="text-xs text-muted-foreground">{{ section.type_label }}</p>
                            </div>

                            <Button
                                variant="ghost"
                                size="sm"
                                :title="section.visible ? 'Ocultar sección' : 'Mostrar sección'"
                                @click="toggle(section.id)"
                            >
                                <Eye v-if="section.visible" class="h-4 w-4" />
                                <EyeOff v-else class="h-4 w-4 text-red-500" />
                                <span class="ml-1.5 hidden sm:inline">{{ section.visible ? 'Visible' : 'Oculta' }}</span>
                            </Button>

                            <Button variant="ghost" size="sm" title="Clonar sección" @click="duplicate(section)">
                                <Copy class="h-4 w-4" />
                                <span class="ml-1.5 hidden sm:inline">Clonar</span>
                            </Button>

                            <Button as-child size="sm" variant="outline">
                                <Link :href="`/admin/sections/${section.id}/edit`"> <Pencil class="mr-1 h-4 w-4" /> Editar </Link>
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
