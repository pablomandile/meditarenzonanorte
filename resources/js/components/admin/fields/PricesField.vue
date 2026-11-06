<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Plus, Trash2 } from 'lucide-vue-next';

type Price = { note: string | null; amount: string | null };

defineProps<{ label: string; error?: string }>();

const model = defineModel<Price[]>({ default: () => [] });

function add() {
    model.value = [...(model.value ?? []), { note: '', amount: '' }];
}

function remove(index: number) {
    model.value = model.value.filter((_, i) => i !== index);
}
</script>

<template>
    <div class="grid gap-3">
        <Label>{{ label }}</Label>

        <div v-for="(price, i) in model" :key="i" class="grid gap-2 rounded-lg border p-3 sm:grid-cols-[1fr_10rem_auto] sm:items-end">
            <div class="grid gap-1">
                <Label class="text-xs">Aclaración (ej. “Precio promocional hasta el 26 de noviembre”)</Label>
                <Input :model-value="price.note ?? ''" @update:model-value="price.note = ($event as string) || null" />
            </div>
            <div class="grid gap-1">
                <Label class="text-xs">Precio</Label>
                <Input :model-value="price.amount ?? ''" placeholder="$ 20.000" @update:model-value="price.amount = ($event as string) || null" />
            </div>
            <Button type="button" variant="ghost" size="icon" class="text-red-600" title="Quitar precio" @click="remove(i)">
                <Trash2 class="h-4 w-4" />
            </Button>
        </div>

        <Button type="button" variant="outline" size="sm" class="w-fit" @click="add"> <Plus class="mr-1 h-4 w-4" /> Agregar precio </Button>
        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
    </div>
</template>
