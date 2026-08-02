<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Plus, Trash2 } from 'lucide-vue-next';

type Occurrence = {
    type?: 'weekly' | 'date' | null;
    weekday?: number | string | null;
    date?: string | null;
    from?: string | null;
    until?: string | null;
    start?: string | null;
    end?: string | null;
    label?: string | null;
};

defineProps<{ label: string; error?: string }>();

const model = defineModel<Occurrence[]>({ default: () => [] });

// ISO: lunes = 1 … domingo = 7, igual que dayOfWeekIso en el servidor.
const DIAS = [
    [1, 'Lunes'],
    [2, 'Martes'],
    [3, 'Miércoles'],
    [4, 'Jueves'],
    [5, 'Viernes'],
    [6, 'Sábado'],
    [7, 'Domingo'],
] as const;

const SELECT_CLASS =
    'flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring';

function add() {
    model.value = [
        ...(model.value ?? []),
        { type: 'weekly', weekday: 3, date: null, from: null, until: null, start: null, end: null, label: null },
    ];
}

function remove(index: number) {
    model.value = model.value.filter((_, i) => i !== index);
}
</script>

<template>
    <div class="grid gap-3">
        <div>
            <Label>{{ label }}</Label>
            <p class="mt-1 text-xs text-muted-foreground">
                Ubican esta actividad en el calendario del sitio. El campo “Horario” de arriba es el texto que se lee en la tarjeta y no cambia.
            </p>
        </div>

        <div v-for="(row, i) in model" :key="i" class="grid gap-3 rounded-lg border p-4">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-muted-foreground">Fecha {{ i + 1 }}</span>
                <Button type="button" variant="ghost" size="icon" class="text-red-600" title="Quitar" @click="remove(i)">
                    <Trash2 class="h-4 w-4" />
                </Button>
            </div>

            <div class="grid gap-2 sm:grid-cols-2">
                <div class="grid gap-1">
                    <Label class="text-xs">Repetición</Label>
                    <select
                        :value="row.type ?? 'weekly'"
                        :class="SELECT_CLASS"
                        @change="row.type = ($event.target as HTMLSelectElement).value as 'weekly' | 'date'"
                    >
                        <option value="weekly">Todas las semanas</option>
                        <option value="date">Una sola fecha</option>
                    </select>
                </div>

                <div v-if="(row.type ?? 'weekly') === 'weekly'" class="grid gap-1">
                    <Label class="text-xs">Día de la semana</Label>
                    <select
                        :value="String(row.weekday ?? '')"
                        :class="SELECT_CLASS"
                        @change="row.weekday = Number(($event.target as HTMLSelectElement).value) || null"
                    >
                        <option value="">Elegí un día</option>
                        <option v-for="[value, text] in DIAS" :key="value" :value="value">{{ text }}</option>
                    </select>
                </div>

                <div v-else class="grid gap-1">
                    <Label class="text-xs">Fecha</Label>
                    <Input :model-value="row.date ?? ''" type="date" @update:model-value="row.date = ($event as string) || null" />
                </div>
            </div>

            <div class="grid gap-2 sm:grid-cols-2">
                <div class="grid gap-1">
                    <Label class="text-xs">Desde (hora)</Label>
                    <Input :model-value="row.start ?? ''" type="time" @update:model-value="row.start = ($event as string) || null" />
                </div>
                <div class="grid gap-1">
                    <Label class="text-xs">Hasta (hora)</Label>
                    <Input :model-value="row.end ?? ''" type="time" @update:model-value="row.end = ($event as string) || null" />
                </div>
            </div>

            <div class="grid gap-2 sm:grid-cols-2">
                <div v-if="(row.type ?? 'weekly') === 'weekly'" class="grid gap-1">
                    <Label class="text-xs">Solo desde (opcional)</Label>
                    <Input :model-value="row.from ?? ''" type="date" @update:model-value="row.from = ($event as string) || null" />
                </div>
                <div class="grid gap-1">
                    <Label class="text-xs">
                        {{ (row.type ?? 'weekly') === 'weekly' ? 'Solo hasta (opcional)' : 'Hasta (si dura varios días)' }}
                    </Label>
                    <Input :model-value="row.until ?? ''" type="date" @update:model-value="row.until = ($event as string) || null" />
                </div>
            </div>

            <div class="grid gap-1">
                <Label class="text-xs">Nombre en el calendario (opcional)</Label>
                <Input
                    :model-value="row.label ?? ''"
                    placeholder="Si se deja vacío se usa el título de la ficha"
                    @update:model-value="row.label = ($event as string) || null"
                />
            </div>
        </div>

        <Button type="button" variant="outline" size="sm" class="w-fit" @click="add">
            <Plus class="mr-1 h-4 w-4" /> Agregar fecha
        </Button>
        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
    </div>
</template>
