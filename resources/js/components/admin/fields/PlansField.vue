<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Plus, Trash2 } from 'lucide-vue-next';

type Plan = {
    name?: string | null;
    price?: string | null;
    period?: string | null;
    features?: string | null;
    note?: string | null;
    highlighted?: boolean | null;
};

defineProps<{ label: string; error?: string }>();

const model = defineModel<Plan[]>({ default: () => [] });

function add() {
    model.value = [...(model.value ?? []), { name: '', price: '', period: '/mes', features: '', note: '', highlighted: false }];
}

function remove(index: number) {
    model.value = model.value.filter((_, i) => i !== index);
}
</script>

<template>
    <div class="grid gap-3">
        <Label>{{ label }}</Label>

        <div v-for="(plan, i) in model" :key="i" class="grid gap-3 rounded-lg border p-4">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-muted-foreground">Plan {{ i + 1 }}</span>
                <Button type="button" variant="ghost" size="icon" class="text-red-600" @click="remove(i)">
                    <Trash2 class="h-4 w-4" />
                </Button>
            </div>

            <div class="grid gap-2 sm:grid-cols-3">
                <div class="grid gap-1 sm:col-span-1">
                    <Label class="text-xs">Nombre</Label>
                    <Input :model-value="plan.name ?? ''" @update:model-value="plan.name = ($event as string) || null" />
                </div>
                <div class="grid gap-1">
                    <Label class="text-xs">Precio</Label>
                    <Input :model-value="plan.price ?? ''" placeholder="$45.000" @update:model-value="plan.price = ($event as string) || null" />
                </div>
                <div class="grid gap-1">
                    <Label class="text-xs">Período</Label>
                    <Input :model-value="plan.period ?? ''" placeholder="/mes" @update:model-value="plan.period = ($event as string) || null" />
                </div>
            </div>

            <div class="grid gap-1">
                <Label class="text-xs">Beneficios (uno por línea)</Label>
                <textarea
                    :value="plan.features ?? ''"
                    rows="3"
                    class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                    @input="plan.features = ($event.target as HTMLTextAreaElement).value || null"
                ></textarea>
            </div>

            <div class="grid gap-2 sm:grid-cols-2">
                <div class="grid gap-1">
                    <Label class="text-xs">Nota (ej. permanencia)</Label>
                    <Input :model-value="plan.note ?? ''" @update:model-value="plan.note = ($event as string) || null" />
                </div>
                <label class="mt-6 flex items-center gap-2 text-sm">
                    <input type="checkbox" class="h-4 w-4 rounded border-gray-300" :checked="!!plan.highlighted" @change="plan.highlighted = ($event.target as HTMLInputElement).checked" />
                    Destacar como "Más elegida"
                </label>
            </div>
        </div>

        <Button type="button" variant="outline" size="sm" class="w-fit" @click="add">
            <Plus class="mr-1 h-4 w-4" /> Agregar plan
        </Button>
        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
    </div>
</template>
