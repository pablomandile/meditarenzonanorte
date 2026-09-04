<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ArrowDown, ArrowUp, Plus, Star, Trash2 } from 'lucide-vue-next';

type Review = {
    quote?: string | null;
    author?: string | null;
    rating?: number | null;
};

defineProps<{ label: string; error?: string }>();

const model = defineModel<Review[]>({ default: () => [] });

function add() {
    model.value = [...(model.value ?? []), { quote: '', author: '', rating: 5 }];
}

function remove(index: number) {
    model.value = model.value.filter((_, i) => i !== index);
}

/**
 * El orden importa: es el orden en que el carrusel las va pasando. Acá se
 * reordena el array y listo, sin pedirle nada al servidor — a diferencia de las
 * preguntas frecuentes y los tutoriales, que sí guardan su posición en la base.
 */
function move(index: number, delta: number) {
    const next = [...model.value];
    const target = index + delta;

    if (target < 0 || target >= next.length) return;

    [next[index], next[target]] = [next[target], next[index]];
    model.value = next;
}
</script>

<template>
    <div class="grid gap-3">
        <Label>{{ label }}</Label>

        <div v-for="(review, i) in model" :key="i" class="grid gap-3 rounded-lg border p-4">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-muted-foreground">Reseña {{ i + 1 }}</span>
                <div class="flex items-center">
                    <Button type="button" variant="ghost" size="icon" :disabled="i === 0" @click="move(i, -1)">
                        <ArrowUp class="h-4 w-4" />
                    </Button>
                    <Button type="button" variant="ghost" size="icon" :disabled="i === model.length - 1" @click="move(i, 1)">
                        <ArrowDown class="h-4 w-4" />
                    </Button>
                    <Button type="button" variant="ghost" size="icon" class="text-red-600" @click="remove(i)">
                        <Trash2 class="h-4 w-4" />
                    </Button>
                </div>
            </div>

            <div class="grid gap-1">
                <Label class="text-xs">Comentario</Label>
                <textarea
                    :value="review.quote ?? ''"
                    rows="3"
                    class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                    @input="review.quote = ($event.target as HTMLTextAreaElement).value || null"
                ></textarea>
            </div>

            <div class="grid gap-2 sm:grid-cols-2">
                <div class="grid gap-1">
                    <Label class="text-xs">Quién lo escribió</Label>
                    <Input :model-value="review.author ?? ''" @update:model-value="review.author = ($event as string) || null" />
                </div>

                <div class="grid gap-1">
                    <Label class="text-xs">Puntuación</Label>
                    <div class="flex h-9 items-center gap-1">
                        <button
                            v-for="n in 5"
                            :key="n"
                            type="button"
                            class="text-brand-orange transition hover:scale-110"
                            :aria-label="`${n} ${n === 1 ? 'estrella' : 'estrellas'}`"
                            :aria-pressed="n === (review.rating ?? 5)"
                            @click="review.rating = n"
                        >
                            <Star class="h-5 w-5" :class="n <= (review.rating ?? 5) ? 'fill-current' : 'text-muted-foreground/40'" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <p v-if="!model.length" class="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">Todavía no hay reseñas cargadas.</p>

        <Button type="button" variant="outline" size="sm" class="w-fit" @click="add"> <Plus class="mr-1 h-4 w-4" /> Agregar reseña </Button>
        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
    </div>
</template>
