<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { ArrowDown, ArrowUp, Plus, Trash2, TriangleAlert } from 'lucide-vue-next';
import { computed } from 'vue';

/** Una referencia a lo elegido. Es lo único que se guarda: el resto sale del sitio. */
export type NewsletterItem = { kind: 'section' | 'event'; id: number; title: string; missing?: boolean };

type PoolEntry = { kind: 'section' | 'event'; id: number; title: string; hint: string; group: string };

const props = defineProps<{
    modelValue: NewsletterItem[];
    pool: { classes: PoolEntry[]; events: PoolEntry[] };
}>();

const emit = defineEmits<{ 'update:modelValue': [NewsletterItem[]] }>();

/**
 * Lo disponible, en grupos y en el orden en que aparece en el sitio: las fichas por
 * página y después los eventos. Que el orden sea el mismo que el del panel de Páginas
 * hace que buscar una clase acá sea buscarla donde uno ya sabe que está.
 */
const groups = computed(() => {
    const all = [...props.pool.classes, ...props.pool.events];
    const byGroup = new Map<string, PoolEntry[]>();

    for (const entry of all) {
        if (!byGroup.has(entry.group)) byGroup.set(entry.group, []);
        byGroup.get(entry.group)!.push(entry);
    }

    return [...byGroup.entries()].map(([name, entries]) => ({ name, entries }));
});

function isChosen(entry: PoolEntry) {
    return props.modelValue.some((item) => item.kind === entry.kind && item.id === entry.id);
}

function add(entry: PoolEntry) {
    if (isChosen(entry)) return;

    emit('update:modelValue', [...props.modelValue, { kind: entry.kind, id: entry.id, title: entry.title }]);
}

function remove(index: number) {
    emit(
        'update:modelValue',
        props.modelValue.filter((_, i) => i !== index),
    );
}

function move(index: number, direction: -1 | 1) {
    const next = [...props.modelValue];
    const target = index + direction;

    if (target < 0 || target >= next.length) return;

    [next[index], next[target]] = [next[target], next[index]];

    emit('update:modelValue', next);
}
</script>

<template>
    <div class="grid gap-4 lg:grid-cols-2">
        <!--
            Elegidos a la izquierda y disponibles a la derecha: lo que se está armando
            es lo importante, y en castellano se lee primero lo de la izquierda.
        -->
        <div class="grid content-start gap-2">
            <p class="text-xs font-medium text-muted-foreground">En este envío ({{ props.modelValue.length }}) — en este orden salen en el mail</p>

            <p v-if="!props.modelValue.length" class="rounded-lg border border-dashed px-3 py-6 text-center text-sm text-muted-foreground">
                Todavía no elegiste nada. Agregá clases o eventos de la lista de al lado.
            </p>

            <div
                v-for="(item, index) in props.modelValue"
                :key="`${item.kind}-${item.id}`"
                class="flex items-center gap-2 rounded-lg border px-3 py-2"
            >
                <div class="flex flex-col">
                    <button
                        type="button"
                        class="rounded p-0.5 text-muted-foreground hover:bg-muted disabled:opacity-30"
                        :disabled="index === 0"
                        @click="move(index, -1)"
                    >
                        <ArrowUp class="h-4 w-4" />
                    </button>
                    <button
                        type="button"
                        class="rounded p-0.5 text-muted-foreground hover:bg-muted disabled:opacity-30"
                        :disabled="index === props.modelValue.length - 1"
                        @click="move(index, 1)"
                    >
                        <ArrowDown class="h-4 w-4" />
                    </button>
                </div>

                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium" :class="{ 'text-red-600': item.missing }">
                        <TriangleAlert v-if="item.missing" class="mr-1 inline h-4 w-4" />
                        {{ item.title }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{ item.kind === 'event' ? 'Evento' : 'Clase' }}
                        <!--
                            Se borró del sitio después de haberla elegido. Se muestra en
                            vez de desaparecer sola, para que se pueda ver qué falta.
                        -->
                        <span v-if="item.missing" class="text-red-600">· ya no existe, sacala de la lista</span>
                    </p>
                </div>

                <Button variant="ghost" size="sm" class="text-red-600" @click="remove(index)">
                    <Trash2 class="h-4 w-4" />
                </Button>
            </div>
        </div>

        <div class="grid content-start gap-3">
            <p class="text-xs font-medium text-muted-foreground">Disponibles</p>

            <div v-for="group in groups" :key="group.name" class="grid gap-1">
                <p class="text-xs uppercase tracking-wide text-muted-foreground">{{ group.name }}</p>

                <button
                    v-for="entry in group.entries"
                    :key="`${entry.kind}-${entry.id}`"
                    type="button"
                    class="flex items-center gap-2 rounded-lg border px-3 py-2 text-left transition enabled:hover:bg-accent disabled:opacity-40"
                    :disabled="isChosen(entry)"
                    @click="add(entry)"
                >
                    <Plus class="h-4 w-4 shrink-0 text-muted-foreground" />
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-medium">{{ entry.title }}</span>
                        <span v-if="entry.hint" class="block truncate text-xs text-muted-foreground">{{ entry.hint }}</span>
                    </span>
                    <span v-if="isChosen(entry)" class="shrink-0 text-xs text-muted-foreground">ya está</span>
                </button>
            </div>

            <p v-if="!groups.length" class="rounded-lg border border-dashed px-3 py-6 text-center text-sm text-muted-foreground">
                No hay clases ni eventos publicados para elegir.
            </p>
        </div>
    </div>
</template>
