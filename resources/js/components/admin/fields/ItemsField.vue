<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Plus, Trash2 } from 'lucide-vue-next';

defineProps<{ label: string; error?: string }>();

const model = defineModel<string[]>({ default: () => [] });

function add() {
    model.value = [...(model.value ?? []), ''];
}

function remove(index: number) {
    model.value = model.value.filter((_, i) => i !== index);
}
</script>

<template>
    <div class="grid gap-2">
        <Label>{{ label }}</Label>

        <div v-for="(item, i) in model" :key="i" class="flex items-center gap-2">
            <Input :model-value="item" class="flex-1" @update:model-value="model[i] = $event as string" />
            <Button type="button" variant="ghost" size="icon" class="text-red-600" @click="remove(i)">
                <Trash2 class="h-4 w-4" />
            </Button>
        </div>

        <Button type="button" variant="outline" size="sm" class="w-fit" @click="add">
            <Plus class="mr-1 h-4 w-4" /> Agregar ítem
        </Button>
        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
    </div>
</template>
