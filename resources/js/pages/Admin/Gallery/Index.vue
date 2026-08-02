<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { useConfirm } from '@/composables/useConfirm';
import { useMediaLibrary } from '@/composables/useMediaLibrary';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { AlertCircle, Lock, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

type GalleryImage = {
    path: string;
    url: string;
    name: string;
    seeded: boolean;
    size: number;
    used_by: string[];
    deletable: boolean;
};

const props = defineProps<{ images: GalleryImage[] }>();

const breadcrumbs = [{ title: 'Galería', href: '/admin/gallery' }];

const page = usePage();
const error = computed(() => (page.props.errors as Record<string, string>)?.image);

const { confirm } = useConfirm();
// El selector cachea la lista en memoria: al borrar acá queda vieja.
const { invalidate } = useMediaLibrary();

const search = ref('');

const filtered = computed(() => {
    const term = search.value.trim().toLowerCase();

    if (!term) return props.images;

    return props.images.filter((image) => image.name.toLowerCase().includes(term));
});

const inUse = computed(() => props.images.filter((image) => image.used_by.length).length);

async function remove(image: GalleryImage) {
    const accepted = await confirm({
        title: 'Eliminar imagen',
        description: `Se borra «${image.name}» del servidor. No se puede deshacer.`,
        confirmLabel: 'Eliminar',
        destructive: true,
    });

    if (!accepted) return;

    router.delete(route('admin.gallery.destroy'), {
        data: { path: image.path },
        preserveScroll: true,
        onSuccess: () => invalidate(),
    });
}

function kb(size: number) {
    return `${Math.round(size / 1024)} KB`;
}
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <Head title="Galería" />

        <div class="flex flex-col gap-4 p-4">
            <div>
                <h1 class="text-xl font-semibold">Galería</h1>
                <p class="text-sm text-muted-foreground">
                    Todas las imágenes cargadas al sitio: {{ images.length }} en total, {{ inUse }} en uso. Las que están en uso o vienen del
                    contenido del sitio no se pueden borrar.
                </p>
            </div>

            <div v-if="error" class="flex items-start gap-2 rounded-md border border-red-300 bg-red-50 p-3 text-sm text-red-700">
                <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />
                <span>{{ error }}</span>
            </div>

            <Input v-model="search" placeholder="Buscar por nombre de archivo…" class="h-9 max-w-sm" />

            <Card>
                <CardContent class="p-4">
                    <p v-if="!filtered.length" class="py-8 text-center text-sm text-muted-foreground">
                        {{ images.length ? 'Ninguna imagen coincide con la búsqueda.' : 'Todavía no hay imágenes cargadas.' }}
                    </p>

                    <div v-else class="grid auto-rows-min grid-cols-2 content-start gap-4 sm:grid-cols-3 lg:grid-cols-4">
                        <div v-for="image in filtered" :key="image.path" class="overflow-hidden rounded-md border">
                            <a :href="image.url" target="_blank" rel="noopener" :title="image.path">
                                <img :src="image.url" :alt="image.name" loading="lazy" class="h-40 w-full bg-muted object-cover" />
                            </a>

                            <div class="space-y-1 p-2">
                                <p class="truncate text-xs font-medium" :title="image.name">{{ image.name }}</p>
                                <p class="text-[11px] text-muted-foreground">{{ image.seeded ? 'Del sitio' : 'Subida' }} · {{ kb(image.size) }}</p>

                                <p v-if="image.used_by.length" class="text-[11px] leading-snug text-amber-700">
                                    <span class="font-medium">En uso:</span>
                                    {{ image.used_by.join(' · ') }}
                                </p>
                                <p v-else-if="image.seeded" class="text-[11px] leading-snug text-muted-foreground">
                                    Sin usar, pero el seeder la restaura.
                                </p>
                                <p v-else class="text-[11px] text-muted-foreground">Sin usar en ninguna parte.</p>

                                <Button v-if="image.deletable" variant="ghost" size="sm" class="w-full text-red-600" @click="remove(image)">
                                    <Trash2 class="mr-1 h-4 w-4" /> Eliminar
                                </Button>
                                <p
                                    v-else
                                    class="flex items-center justify-center gap-1 py-1.5 text-[11px] text-muted-foreground"
                                    :title="
                                        image.used_by.length
                                            ? 'Primero cambiá o quitá la imagen donde se usa'
                                            : 'Las imágenes del contenido sembrado del sitio no se borran'
                                    "
                                >
                                    <Lock class="h-3.5 w-3.5" />
                                    No se puede eliminar
                                </p>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
