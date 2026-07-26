<script setup lang="ts">
import ImageField from '@/components/admin/fields/ImageField.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type CardItem } from '@/lib/site';
import { Plus, Trash2 } from 'lucide-vue-next';

defineProps<{ label: string; error?: string }>();

const model = defineModel<CardItem[]>({ default: () => [] });
const files = defineModel<Record<number, { image: File | null }>>('files', { default: () => ({}) });

function add() {
    model.value = [...(model.value ?? []), { image: null, title: '', text: '', url: '' }];
}

function remove(index: number) {
    model.value = model.value.filter((_, i) => i !== index);

    const next: Record<number, { image: File | null }> = {};
    Object.entries(files.value ?? {}).forEach(([key, value]) => {
        const i = Number(key);
        if (i === index) return;
        next[i > index ? i - 1 : i] = value;
    });
    files.value = next;
}

function setFile(index: number, file: File | null) {
    files.value = { ...(files.value ?? {}), [index]: { image: file } };
}
</script>

<template>
    <div class="grid gap-3">
        <Label>{{ label }}</Label>

        <div v-for="(card, i) in model" :key="i" class="grid gap-3 rounded-lg border p-4">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-muted-foreground">Tarjeta {{ i + 1 }}</span>
                <Button type="button" variant="ghost" size="icon" class="text-red-600" @click="remove(i)">
                    <Trash2 class="h-4 w-4" />
                </Button>
            </div>

            <ImageField
                label="Imagen"
                :model-value="card.image"
                :file="files?.[i]?.image ?? null"
                @update:model-value="card.image = $event ?? null"
                @update:file="setFile(i, $event ?? null)"
            />

            <div class="grid gap-2 sm:grid-cols-2">
                <div class="grid gap-1">
                    <Label class="text-xs">Título</Label>
                    <Input :model-value="card.title ?? ''" @update:model-value="card.title = ($event as string) || null" />
                </div>
                <div class="grid gap-1">
                    <Label class="text-xs">Enlace (URL)</Label>
                    <Input :model-value="card.url ?? ''" @update:model-value="card.url = ($event as string) || null" />
                </div>
            </div>
            <div class="grid gap-1">
                <Label class="text-xs">Texto</Label>
                <Input :model-value="card.text ?? ''" @update:model-value="card.text = ($event as string) || null" />
            </div>
        </div>

        <Button type="button" variant="outline" size="sm" class="w-fit" @click="add">
            <Plus class="mr-1 h-4 w-4" /> Agregar tarjeta
        </Button>
        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
    </div>
</template>
