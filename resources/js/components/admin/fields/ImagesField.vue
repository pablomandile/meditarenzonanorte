<script setup lang="ts">
import ImageField from '@/components/admin/fields/ImageField.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Plus, Trash2 } from 'lucide-vue-next';

defineProps<{ label: string; error?: string }>();

const model = defineModel<(string | null)[]>({ default: () => [] });
const files = defineModel<Record<number, File | null>>('files', { default: () => ({}) });

function add() {
    model.value = [...(model.value ?? []), null];
}

function remove(index: number) {
    model.value = model.value.filter((_, i) => i !== index);

    const next: Record<number, File | null> = {};
    Object.entries(files.value ?? {}).forEach(([key, value]) => {
        const i = Number(key);
        if (i === index) return;
        next[i > index ? i - 1 : i] = value;
    });
    files.value = next;
}

function setFile(index: number, file: File | null) {
    files.value = { ...(files.value ?? {}), [index]: file };
}
</script>

<template>
    <div class="grid gap-3">
        <Label>{{ label }}</Label>

        <div class="grid gap-4 sm:grid-cols-2">
            <div v-for="(path, i) in model" :key="i" class="relative rounded-lg border p-4">
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="absolute right-2 top-2 text-red-600"
                    @click="remove(i)"
                >
                    <Trash2 class="h-4 w-4" />
                </Button>
                <ImageField
                    :label="`Imagen ${i + 1}`"
                    :model-value="path"
                    :file="files?.[i] ?? null"
                    @update:model-value="model[i] = $event ?? null"
                    @update:file="setFile(i, $event ?? null)"
                />
            </div>
        </div>

        <Button type="button" variant="outline" size="sm" class="w-fit" @click="add">
            <Plus class="mr-1 h-4 w-4" /> Agregar imagen
        </Button>
        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
    </div>
</template>
