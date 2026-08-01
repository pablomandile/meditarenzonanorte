<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogDescription, DialogHeader, DialogScrollContent, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { useMediaLibrary, type MediaImage } from '@/composables/useMediaLibrary';
import { Check, RefreshCw } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps<{ current?: string | null }>();

const open = defineModel<boolean>('open', { required: true });

const emit = defineEmits<{ select: [path: string] }>();

const { images, loading, error, load } = useMediaLibrary();

const search = ref('');

const filtered = computed(() => {
    const term = search.value.trim().toLowerCase();

    if (!term) return images.value;

    return images.value.filter((image) => image.name.toLowerCase().includes(term));
});

watch(open, (isOpen) => {
    if (isOpen) load();
});

function choose(image: MediaImage) {
    emit('select', image.path);
    open.value = false;
}

function kb(size: number) {
    return `${Math.round(size / 1024)} KB`;
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogScrollContent class="max-w-3xl">
            <DialogHeader>
                <DialogTitle>Elegir de la galería</DialogTitle>
                <DialogDescription>
                    Imágenes ya cargadas en el sitio. Al elegir una se guarda una copia para esta sección, así reemplazarla más adelante no afecta a
                    las demás.
                </DialogDescription>
            </DialogHeader>

            <div class="flex items-center gap-2">
                <Input v-model="search" placeholder="Buscar por nombre de archivo…" class="h-9" />
                <Button type="button" variant="outline" size="sm" :disabled="loading" title="Actualizar" @click="load(true)">
                    <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': loading }" />
                </Button>
            </div>

            <p v-if="loading && !images.length" class="py-8 text-center text-sm text-muted-foreground">Cargando imágenes…</p>
            <p v-else-if="error" class="py-8 text-center text-sm text-red-600">{{ error }}</p>
            <p v-else-if="!filtered.length" class="py-8 text-center text-sm text-muted-foreground">
                {{ images.length ? 'Ninguna imagen coincide con la búsqueda.' : 'Todavía no hay imágenes cargadas.' }}
            </p>

            <!--
                content-start es necesario, no decorativo: con max-h y el valor por
                defecto de align-content, la grilla reparte su alto entre las filas y
                las comprime (11 filas en 60vh = 33px cada una), recortando las
                miniaturas. Con start cada fila mide lo que su contenido y desborda,
                que es lo que hace aparecer el scroll.
            -->
            <div v-else class="grid max-h-[60vh] auto-rows-min grid-cols-2 content-start gap-3 overflow-y-auto sm:grid-cols-3 md:grid-cols-4">
                <button
                    v-for="image in filtered"
                    :key="image.path"
                    type="button"
                    class="group relative overflow-hidden rounded-md border text-left transition hover:border-primary hover:shadow"
                    :title="image.path"
                    @click="choose(image)"
                >
                    <img :src="image.url" :alt="image.name" loading="lazy" class="h-40 w-full bg-muted object-cover" />

                    <span
                        v-if="image.path === props.current"
                        class="absolute right-1 top-1 rounded-full bg-primary p-1 text-primary-foreground"
                        title="Es la imagen actual"
                    >
                        <Check class="h-3 w-3" />
                    </span>

                    <span class="block truncate px-2 py-1 text-xs" :title="image.name">{{ image.name }}</span>
                    <span class="block px-2 pb-1 text-[11px] text-muted-foreground">
                        {{ image.seeded ? 'Del sitio' : 'Subida' }} · {{ kb(image.size) }}
                    </span>
                </button>
            </div>
        </DialogScrollContent>
    </Dialog>
</template>
