<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useConfirm } from '@/composables/useConfirm';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, Copy, Eye, EyeOff, LoaderCircle, Lock, Pencil, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    page: {
        id: number;
        slug: string;
        title: string;
        menu_label: string | null;
        meta_description: string | null;
        site_name: string;
        url: string;
    };
    sections: {
        id: number;
        type: string;
        type_label: string;
        key: string;
        title: string | null;
        position: number;
        visible: boolean;
        is_template: boolean;
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

const esHome = props.page.slug === 'home';

const datos = useForm({
    title: props.page.title,
    menu_label: props.page.menu_label ?? '',
    meta_description: props.page.meta_description ?? '',
});

const largo = computed(() => datos.meta_description.trim().length);
const cortada = computed(() => largo.value > RECOMENDADOS);

// La vista previa sigue el título mientras se escribe, sin esperar a guardar.
const previewTitle = computed(() => `${datos.title.trim() || props.page.title} - ${props.page.site_name}`);

function guardar() {
    datos.patch(route('admin.pages.update', props.page.id), {
        preserveScroll: true,
        onSuccess: () => {
            // El servidor recorta los espacios y vacía el nombre de menú a null:
            // re-sincronizar deja el formulario "sin cambios" con lo guardado.
            datos.title = props.page.title;
            datos.menu_label = props.page.menu_label ?? '';
            datos.meta_description = props.page.meta_description ?? '';
            datos.defaults();
        },
    });
}

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

async function destroy(section: { id: number; title: string | null; type_label: string }) {
    const name = section.title || section.type_label;

    const accepted = await confirm({
        title: 'Eliminar sección',
        description: `Se elimina “${name}” para siempre, junto con las imágenes que hayas subido para ella. No se puede deshacer.`,
        confirmLabel: 'Eliminar',
        destructive: true,
    });

    if (accepted) {
        router.delete(route('admin.sections.destroy', section.id), { preserveScroll: true });
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
                Datos de la página en sí. El slug no se edita —cambiarlo rompería
                los enlaces— y el orden del menú se cambia con las flechas del listado.
            -->
            <Card>
                <!-- [&>*]:min-w-0: sin esto, la vista previa con el enlace largo (truncate) desborda en mobile. -->
                <CardContent class="grid gap-4 p-4 [&>*]:min-w-0">
                    <div class="grid gap-1.5">
                        <Label for="title">Título de la página</Label>
                        <p class="text-sm text-muted-foreground">El nombre que se ve en la pestaña del navegador y como título en Google.</p>
                        <Input id="title" v-model="datos.title" maxlength="255" />
                        <p v-if="datos.errors.title" class="text-sm text-red-600">{{ datos.errors.title }}</p>
                    </div>

                    <div v-if="!esHome" class="grid gap-1.5">
                        <Label for="menu_label">Nombre en el menú</Label>
                        <p class="text-sm text-muted-foreground">
                            Cómo aparece esta página en el menú del sitio. Si lo dejás vacío sale del menú, aunque su dirección siga funcionando.
                        </p>
                        <Input id="menu_label" v-model="datos.menu_label" maxlength="255" placeholder="Ej.: Gratis" />
                        <p v-if="datos.errors.menu_label" class="text-sm text-red-600">{{ datos.errors.menu_label }}</p>
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="meta_description">Descripción para buscadores</Label>
                        <p class="text-sm text-muted-foreground">
                            Es el texto que se lee debajo del título en Google y al compartir el enlace por WhatsApp.
                        </p>

                        <textarea
                            id="meta_description"
                            v-model="datos.meta_description"
                            rows="3"
                            maxlength="500"
                            placeholder="Ej.: Clases de meditación kadampa en Zona Norte, abiertas a todo el mundo y sin experiencia previa."
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        ></textarea>

                        <p class="text-xs" :class="cortada ? 'text-amber-700' : 'text-muted-foreground'">
                            {{ largo }} de {{ RECOMENDADOS }} caracteres recomendados<span v-if="cortada">
                                — de acá en adelante los buscadores lo cortan</span
                            >
                        </p>
                        <p v-if="datos.errors.meta_description" class="text-sm text-red-600">{{ datos.errors.meta_description }}</p>
                    </div>

                    <!-- Cómo se va a ver, con el título que acompaña a la descripción. -->
                    <div class="rounded-lg border bg-muted/30 p-3">
                        <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Vista previa</p>
                        <p class="mt-1.5 truncate text-sm text-muted-foreground">{{ page.url }}</p>
                        <p class="truncate font-medium text-blue-700 dark:text-blue-400">{{ previewTitle }}</p>
                        <p class="line-clamp-2 text-sm text-muted-foreground">
                            {{ datos.meta_description.trim() || 'Sin descripción: se comparte sólo el título y el enlace.' }}
                        </p>
                    </div>

                    <div>
                        <Button :disabled="datos.processing || !datos.isDirty" @click="guardar">
                            <LoaderCircle v-if="datos.processing" class="mr-1 h-4 w-4 animate-spin" />
                            Guardar cambios
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <!--
                    En mobile cada sección es una ficha: el nombre arriba y los botones
                    debajo. De lg para arriba vuelve a ser una fila de la lista.
                -->
                <CardContent class="p-3 lg:p-0">
                    <div class="flex flex-col gap-3 lg:block lg:divide-y">
                        <div
                            v-for="(section, index) in sections"
                            :key="section.id"
                            class="flex flex-col gap-3 rounded-lg border p-3 lg:flex-row lg:items-center lg:rounded-none lg:border-0 lg:px-4 lg:py-3"
                            :class="{ 'opacity-60': !section.visible }"
                        >
                            <div class="flex min-w-0 flex-1 items-center gap-3">
                                <div class="flex shrink-0 flex-col">
                                    <button
                                        class="rounded p-1 text-muted-foreground hover:bg-muted disabled:opacity-30"
                                        :disabled="index === 0"
                                        title="Subir"
                                        @click="move(section.id, 'up')"
                                    >
                                        <ArrowUp class="h-4 w-4" />
                                    </button>
                                    <button
                                        class="rounded p-1 text-muted-foreground hover:bg-muted disabled:opacity-30"
                                        :disabled="index === sections.length - 1"
                                        title="Bajar"
                                        @click="move(section.id, 'down')"
                                    >
                                        <ArrowDown class="h-4 w-4" />
                                    </button>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-medium">{{ section.title || section.type_label }}</p>
                                    <p class="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                        {{ section.type_label }}
                                        <span
                                            v-if="section.is_template"
                                            class="inline-flex items-center gap-1 rounded bg-muted px-1.5 py-0.5 font-medium"
                                        >
                                            <Lock class="h-3 w-3" /> Plantilla
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <!--
                                En mobile los botones bajan de línea si no entran; en desktop
                                van a la derecha en una sola fila.
                            -->
                            <div class="flex flex-wrap items-center gap-2 border-t pt-3 lg:shrink-0 lg:border-0 lg:pt-0">
                                <!-- La plantilla no se muestra ni se elimina: es solo el molde para clonar. -->
                                <span
                                    v-if="section.is_template"
                                    class="inline-flex items-center gap-1.5 px-2 text-xs text-muted-foreground"
                                    title="La plantilla queda siempre oculta"
                                >
                                    <EyeOff class="h-4 w-4" /> Siempre oculta
                                </span>
                                <Button
                                    v-else
                                    variant="ghost"
                                    size="sm"
                                    :title="section.visible ? 'Ocultar sección' : 'Mostrar sección'"
                                    @click="toggle(section.id)"
                                >
                                    <Eye v-if="section.visible" class="h-4 w-4" />
                                    <EyeOff v-else class="h-4 w-4 text-red-500" />
                                    <span class="ml-1.5">{{ section.visible ? 'Visible' : 'Oculta' }}</span>
                                </Button>

                                <Button variant="ghost" size="sm" title="Clonar sección" @click="duplicate(section)">
                                    <Copy class="h-4 w-4" />
                                    <span class="ml-1.5">Clonar</span>
                                </Button>

                                <Button as-child size="sm" variant="outline">
                                    <Link :href="`/admin/sections/${section.id}/edit`"> <Pencil class="mr-1 h-4 w-4" /> Editar </Link>
                                </Button>

                                <Button
                                    v-if="!section.is_template"
                                    variant="ghost"
                                    size="sm"
                                    class="text-red-600 hover:bg-red-50 hover:text-red-700"
                                    title="Eliminar sección"
                                    @click="destroy(section)"
                                >
                                    <Trash2 class="h-4 w-4" />
                                    <span class="ml-1.5">Eliminar</span>
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
