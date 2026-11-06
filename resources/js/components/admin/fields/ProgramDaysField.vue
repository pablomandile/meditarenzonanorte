<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ArrowDown, ArrowUp, Plus, Trash2 } from 'lucide-vue-next';

type Activity = { time: string | null; name: string | null; highlight: boolean };
type Day = { weekday: string | null; day: string | null; featured: boolean; items: Activity[] };

defineProps<{ label: string; error?: string }>();

const model = defineModel<Day[]>({ default: () => [] });

function addDay() {
    model.value = [...(model.value ?? []), { weekday: '', day: '', featured: false, items: [{ time: '', name: '', highlight: false }] }];
}

function removeDay(index: number) {
    model.value = model.value.filter((_, i) => i !== index);
}

function moveDay(index: number, direction: -1 | 1) {
    const days = [...model.value];
    const target = index + direction;

    if (target < 0 || target >= days.length) return;

    [days[index], days[target]] = [days[target], days[index]];
    model.value = days;
}

function addItem(day: Day) {
    day.items = [...(day.items ?? []), { time: '', name: '', highlight: false }];
}

function removeItem(day: Day, index: number) {
    day.items = day.items.filter((_, i) => i !== index);
}

function moveItem(day: Day, index: number, direction: -1 | 1) {
    const items = [...day.items];
    const target = index + direction;

    if (target < 0 || target >= items.length) return;

    [items[index], items[target]] = [items[target], items[index]];
    day.items = items;
}
</script>

<template>
    <div class="grid gap-3">
        <Label>{{ label }}</Label>

        <div v-for="(day, i) in model" :key="i" class="grid gap-3 rounded-lg border p-4">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-muted-foreground">Día {{ i + 1 }}</span>
                <div class="flex items-center gap-1">
                    <Button type="button" variant="ghost" size="icon" :disabled="i === 0" title="Subir" @click="moveDay(i, -1)">
                        <ArrowUp class="h-4 w-4" />
                    </Button>
                    <Button type="button" variant="ghost" size="icon" :disabled="i === model.length - 1" title="Bajar" @click="moveDay(i, 1)">
                        <ArrowDown class="h-4 w-4" />
                    </Button>
                    <Button type="button" variant="ghost" size="icon" class="text-red-600" title="Quitar día" @click="removeDay(i)">
                        <Trash2 class="h-4 w-4" />
                    </Button>
                </div>
            </div>

            <div class="grid gap-2 sm:grid-cols-[1fr_6rem]">
                <div class="grid gap-1">
                    <Label class="text-xs">Día de la semana</Label>
                    <Input :model-value="day.weekday ?? ''" placeholder="Sábado" @update:model-value="day.weekday = ($event as string) || null" />
                </div>
                <div class="grid gap-1">
                    <Label class="text-xs">Número</Label>
                    <Input :model-value="day.day ?? ''" placeholder="05" @update:model-value="day.day = ($event as string) || null" />
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input v-model="day.featured" type="checkbox" class="h-4 w-4 rounded border-gray-300" />
                Destacar este día (borde violeta arriba)
            </label>

            <div class="grid gap-2">
                <Label class="text-xs">Actividades</Label>
                <div
                    v-for="(item, j) in day.items"
                    :key="j"
                    class="grid gap-2 rounded-md bg-muted/40 p-2 sm:grid-cols-[9rem_1fr_auto] sm:items-center"
                >
                    <Input :model-value="item.time ?? ''" placeholder="09:30 – 10:30" @update:model-value="item.time = ($event as string) || null" />
                    <Input :model-value="item.name ?? ''" placeholder="Meditación" @update:model-value="item.name = ($event as string) || null" />
                    <div class="flex items-center gap-1">
                        <label class="mr-1 flex items-center gap-1.5 text-xs" title="Se pinta con fondo violeta claro">
                            <input v-model="item.highlight" type="checkbox" class="h-4 w-4 rounded border-gray-300" />
                            Resaltar
                        </label>
                        <Button type="button" variant="ghost" size="icon" :disabled="j === 0" title="Subir" @click="moveItem(day, j, -1)">
                            <ArrowUp class="h-4 w-4" />
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            :disabled="j === day.items.length - 1"
                            title="Bajar"
                            @click="moveItem(day, j, 1)"
                        >
                            <ArrowDown class="h-4 w-4" />
                        </Button>
                        <Button type="button" variant="ghost" size="icon" class="text-red-600" title="Quitar actividad" @click="removeItem(day, j)">
                            <Trash2 class="h-4 w-4" />
                        </Button>
                    </div>
                </div>
                <Button type="button" variant="outline" size="sm" class="w-fit" @click="addItem(day)">
                    <Plus class="mr-1 h-4 w-4" /> Agregar actividad
                </Button>
            </div>
        </div>

        <Button type="button" variant="outline" size="sm" class="w-fit" @click="addDay"> <Plus class="mr-1 h-4 w-4" /> Agregar día </Button>
        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
    </div>
</template>
