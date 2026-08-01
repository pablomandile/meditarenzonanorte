<script setup lang="ts">
import ImagePickerDialog from '@/components/admin/ImagePickerDialog.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { useMediaLibrary } from '@/composables/useMediaLibrary';
import { img } from '@/lib/site';
import { Images, Trash2, Upload } from 'lucide-vue-next';
import { computed, ref } from 'vue';

// gallery: habilita "Elegir de galería". Solo lo usan los campos de secciones,
// porque su guardado se apropia de la imagen elegida (ImageStorage::adopt).
withDefaults(defineProps<{ label: string; error?: string; gallery?: boolean }>(), { gallery: false });

const model = defineModel<string | null>();
const file = defineModel<File | null>('file');

const input = ref<HTMLInputElement>();
const picking = ref(false);

const { invalidate } = useMediaLibrary();

const preview = computed(() => {
    if (file.value) return URL.createObjectURL(file.value);

    return img(model.value);
});

function onFile(event: Event) {
    const selected = (event.target as HTMLInputElement).files?.[0] ?? null;
    if (selected) {
        file.value = selected;
        invalidate();
    }
}

/** Elegir de la galería descarta el archivo pendiente: gana la ruta elegida. */
function onPick(path: string) {
    file.value = null;
    model.value = path;
    if (input.value) input.value.value = '';
}

function clear() {
    file.value = null;
    model.value = null;
    if (input.value) input.value.value = '';
}
</script>

<template>
    <div class="grid gap-2">
        <Label>{{ label }}</Label>

        <div class="flex items-start gap-4">
            <img v-if="preview" :src="preview" alt="" class="h-24 w-24 rounded-md border object-cover" />
            <div v-else class="flex h-24 w-24 items-center justify-center rounded-md border border-dashed text-xs text-muted-foreground">
                Sin imagen
            </div>

            <div class="flex flex-col gap-2">
                <Button type="button" variant="outline" size="sm" @click="input?.click()">
                    <Upload class="mr-1 h-4 w-4" /> {{ preview ? 'Reemplazar' : 'Subir imagen' }}
                </Button>
                <Button v-if="gallery" type="button" variant="outline" size="sm" @click="picking = true">
                    <Images class="mr-1 h-4 w-4" /> Elegir de galería
                </Button>
                <Button v-if="preview" type="button" variant="ghost" size="sm" class="text-red-600" @click="clear">
                    <Trash2 class="mr-1 h-4 w-4" /> Quitar
                </Button>
                <p class="text-xs text-muted-foreground">JPG, PNG o WebP. Máx. 4 MB.</p>
            </div>
        </div>

        <input ref="input" type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden" @change="onFile" />
        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>

        <ImagePickerDialog v-if="gallery" v-model:open="picking" :current="model" @select="onPick" />
    </div>
</template>
