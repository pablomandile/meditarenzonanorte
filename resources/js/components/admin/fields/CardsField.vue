<script setup lang="ts">
import ImageField from '@/components/admin/fields/ImageField.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type CardItem } from '@/lib/site';
import { ArrowDown, ArrowUp, Plus, Trash2 } from 'lucide-vue-next';

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

/**
 * El orden del array es el que usa la web, así que mover una tarjeta acá cambia
 * su lugar en la página. El archivo pendiente de subida viaja con su tarjeta:
 * está indexado por posición y si no se mueve, terminaría en la de al lado.
 */
function move(index: number, direction: -1 | 1) {
    const cards = [...(model.value ?? [])];
    const target = index + direction;

    if (target < 0 || target >= cards.length) return;

    [cards[index], cards[target]] = [cards[target], cards[index]];
    model.value = cards;

    const next = { ...(files.value ?? {}) };
    const moved = next[index];
    const displaced = next[target];

    if (displaced === undefined) delete next[index];
    else next[index] = displaced;

    if (moved === undefined) delete next[target];
    else next[target] = moved;

    files.value = next;
}
</script>

<template>
    <div class="grid gap-3">
        <Label>{{ label }}</Label>

        <div v-for="(card, i) in model" :key="i" class="grid gap-3 rounded-lg border p-4">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-muted-foreground">Tarjeta {{ i + 1 }}</span>
                <div class="flex items-center gap-1">
                    <Button type="button" variant="ghost" size="icon" :disabled="i === 0" title="Subir" @click="move(i, -1)">
                        <ArrowUp class="h-4 w-4" />
                    </Button>
                    <Button type="button" variant="ghost" size="icon" :disabled="i === model.length - 1" title="Bajar" @click="move(i, 1)">
                        <ArrowDown class="h-4 w-4" />
                    </Button>
                    <Button type="button" variant="ghost" size="icon" class="text-red-600" title="Quitar tarjeta" @click="remove(i)">
                        <Trash2 class="h-4 w-4" />
                    </Button>
                </div>
            </div>

            <ImageField
                label="Imagen"
                gallery
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

        <Button type="button" variant="outline" size="sm" class="w-fit" @click="add"> <Plus class="mr-1 h-4 w-4" /> Agregar tarjeta </Button>
        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
    </div>
</template>
