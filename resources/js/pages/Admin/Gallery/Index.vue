<script setup lang="ts">
import AdminSection from '@/components/admin/AdminSection.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { useConfirm } from '@/composables/useConfirm';
import { useMediaLibrary } from '@/composables/useMediaLibrary';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { AlertCircle, Check, Images, Link2, Lock, Trash2, Upload } from 'lucide-vue-next';
import { computed, ref } from 'vue';

type GalleryImage = {
    path: string;
    url: string;
    name: string;
    seeded: boolean;
    size: number;
    used_by: string[];
    deletable: boolean;
    standalone: boolean;
};

const props = defineProps<{ images: GalleryImage[] }>();

const breadcrumbs = [{ title: 'Galería', href: '/admin/gallery' }];

const page = usePage();
const error = computed(() => {
    const errors = (page.props.errors ?? {}) as Record<string, string>;

    return errors.image ?? errors.images ?? Object.entries(errors).find(([key]) => key.startsWith('images.'))?.[1];
});

const { confirm } = useConfirm();
// El selector cachea la lista en memoria: al borrar acá queda vieja.
const { invalidate } = useMediaLibrary();

const search = ref('');
const filter = ref<'todas' | 'sin-uso'>('todas');

const filtered = computed(() => {
    const term = search.value.trim().toLowerCase();

    return props.images.filter((image) => {
        if (filter.value === 'sin-uso' && image.used_by.length) return false;

        return !term || image.name.toLowerCase().includes(term);
    });
});

const inUse = computed(() => props.images.filter((image) => image.used_by.length).length);

const vacio = computed(() => {
    if (!props.images.length) return 'Todavía no hay imágenes cargadas.';
    if (search.value.trim()) return 'Ninguna imagen coincide con la búsqueda.';

    return 'No hay imágenes sin uso: todas están en alguna publicación.';
});

// Click en la miniatura: se ve en grande en un modal, sin salir de la página.
const preview = ref<GalleryImage | null>(null);

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

// Subida de imágenes sueltas: no quedan ligadas a ningún curso ni sección, se
// usan afuera (una publicidad, un posteo) copiando su enlace.
const fileInput = ref<HTMLInputElement | null>(null);
const uploading = ref(false);

function upload(event: Event) {
    const input = event.target as HTMLInputElement;
    const files = Array.from(input.files ?? []);

    if (!files.length) return;

    router.post(
        route('admin.gallery.store'),
        { images: files },
        {
            forceFormData: true,
            preserveScroll: true,
            onStart: () => (uploading.value = true),
            onFinish: () => {
                uploading.value = false;
                input.value = '';
            },
            onSuccess: () => {
                invalidate();
                filter.value = 'todas';
                search.value = '';
            },
        },
    );
}

const copied = ref<string | null>(null);
let copiedTimer: ReturnType<typeof setTimeout> | undefined;

async function copyLink(image: GalleryImage) {
    const url = new URL(image.url, window.location.origin).href;

    try {
        await navigator.clipboard.writeText(url);
    } catch {
        window.prompt('Copiá el enlace de la imagen:', url);
        return;
    }

    copied.value = image.path;
    clearTimeout(copiedTimer);
    copiedTimer = setTimeout(() => (copied.value = null), 2000);
}

function kb(size: number) {
    return `${Math.round(size / 1024)} KB`;
}
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <Head title="Galería" />

        <div class="admin-sections flex flex-col gap-4 p-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <h1 class="text-xl font-semibold">Galería</h1>
                    <p class="text-sm text-muted-foreground">
                        Todas las imágenes cargadas al sitio: {{ images.length }} en total, {{ inUse }} en uso. Las que están en uso o vienen del
                        contenido del sitio no se pueden borrar.
                    </p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        ¿Necesitás una imagen para una publicidad o un posteo? Subila acá y usá «Copiar enlace»: queda alojada en el sitio sin estar
                        ligada a ningún curso.
                    </p>
                </div>

                <input ref="fileInput" type="file" accept="image/jpeg,image/png,image/webp,image/gif" multiple class="hidden" @change="upload" />
                <Button :disabled="uploading" @click="fileInput?.click()">
                    <Upload class="mr-1 h-4 w-4" />
                    {{ uploading ? 'Subiendo…' : 'Subir imágenes' }}
                </Button>
            </div>

            <div v-if="error" class="flex items-start gap-2 rounded-md border border-red-300 bg-red-50 p-3 text-sm text-red-700">
                <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />
                <span>{{ error }}</span>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <Input v-model="search" placeholder="Buscar por nombre de archivo…" class="h-9 max-w-sm" />

                <select
                    v-model="filter"
                    aria-label="Filtrar por uso"
                    class="flex h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                >
                    <option value="todas">Mostrar todas</option>
                    <option value="sin-uso">Sin uso</option>
                </select>

                <span class="text-sm text-muted-foreground">{{ filtered.length }} de {{ images.length }}</span>
            </div>

            <AdminSection title="Imágenes" :icon="Images" flush>
                <div class="p-4">
                    <p v-if="!filtered.length" class="py-8 text-center text-sm text-muted-foreground">{{ vacio }}</p>

                    <div v-else class="grid auto-rows-min grid-cols-2 content-start gap-4 sm:grid-cols-3 lg:grid-cols-4">
                        <div v-for="image in filtered" :key="image.path" class="overflow-hidden rounded-md border bg-card">
                            <button type="button" class="block w-full" :title="`Ver «${image.name}» en grande`" @click="preview = image">
                                <img :src="image.url" :alt="image.name" loading="lazy" class="h-40 w-full bg-muted object-cover" />
                            </button>

                            <div class="space-y-1 p-2">
                                <p class="truncate text-xs font-medium" :title="image.name">{{ image.name }}</p>
                                <p class="text-[11px] text-muted-foreground">
                                    {{ image.seeded ? 'Del sitio' : image.standalone ? 'Suelta' : 'Subida' }} · {{ kb(image.size) }}
                                </p>

                                <p v-if="image.used_by.length" class="text-[11px] leading-snug text-amber-700">
                                    <span class="font-medium">En uso:</span>
                                    {{ image.used_by.join(' · ') }}
                                </p>
                                <p v-else-if="image.seeded" class="text-[11px] leading-snug text-muted-foreground">
                                    Sin usar, pero el seeder la restaura.
                                </p>
                                <p v-else-if="image.standalone" class="text-[11px] leading-snug text-muted-foreground">
                                    Subida a la galería para usar por su enlace.
                                </p>
                                <p v-else class="text-[11px] text-muted-foreground">Sin usar en ninguna parte.</p>

                                <Button variant="ghost" size="sm" class="w-full" @click="copyLink(image)">
                                    <Check v-if="copied === image.path" class="mr-1 h-4 w-4 text-emerald-600" />
                                    <Link2 v-else class="mr-1 h-4 w-4" />
                                    {{ copied === image.path ? '¡Enlace copiado!' : 'Copiar enlace' }}
                                </Button>

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
                </div>
            </AdminSection>
        </div>

        <Dialog :open="!!preview" @update:open="(open) => !open && (preview = null)">
            <DialogContent v-if="preview" class="max-w-4xl">
                <DialogHeader>
                    <DialogTitle class="truncate">{{ preview.name }}</DialogTitle>
                    <DialogDescription>
                        {{ preview.seeded ? 'Del sitio' : 'Subida' }} · {{ kb(preview.size) }} ·
                        <span v-if="preview.used_by.length">en uso: {{ preview.used_by.join(' · ') }}</span>
                        <span v-else>sin usar en ninguna parte</span>
                    </DialogDescription>
                </DialogHeader>

                <img :src="preview.url" :alt="preview.name" class="max-h-[70vh] w-full rounded-md bg-muted object-contain" />

                <div class="flex items-center gap-2">
                    <p class="min-w-0 flex-1 truncate text-xs text-muted-foreground" :title="preview.url">{{ preview.url }}</p>
                    <Button variant="outline" size="sm" class="shrink-0" @click="copyLink(preview)">
                        <Check v-if="copied === preview.path" class="mr-1 h-4 w-4 text-emerald-600" />
                        <Link2 v-else class="mr-1 h-4 w-4" />
                        {{ copied === preview.path ? 'Copiado' : 'Copiar enlace' }}
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>
