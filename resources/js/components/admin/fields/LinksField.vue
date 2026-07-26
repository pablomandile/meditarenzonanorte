<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type LinkItem } from '@/lib/site';
import { Plus, Trash2 } from 'lucide-vue-next';

defineProps<{ label: string; error?: string }>();

const model = defineModel<LinkItem[]>({ default: () => [] });

function add() {
    model.value = [...(model.value ?? []), { label: '', url: '' }];
}

function remove(index: number) {
    model.value = model.value.filter((_, i) => i !== index);
}
</script>

<template>
    <div class="grid gap-2">
        <Label>{{ label }}</Label>

        <div v-for="(link, i) in model" :key="i" class="flex items-center gap-2">
            <Input v-model="link.label" placeholder="Texto" class="flex-1" />
            <Input v-model="link.url" placeholder="https://... o /pagina" class="flex-1" />
            <Button type="button" variant="ghost" size="icon" class="text-red-600" @click="remove(i)">
                <Trash2 class="h-4 w-4" />
            </Button>
        </div>

        <Button type="button" variant="outline" size="sm" class="w-fit" @click="add">
            <Plus class="mr-1 h-4 w-4" /> Agregar enlace
        </Button>
        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
    </div>
</template>
