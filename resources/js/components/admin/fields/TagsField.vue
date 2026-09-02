<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Link } from '@inertiajs/vue3';
import { Plus, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{ label: string; error?: string; options?: string[] }>();

// Se guarda como texto separado por coma, igual que antes.
const model = defineModel<string | null>();

const tags = computed(() =>
    (model.value ?? '')
        .split(',')
        .map((tag) => tag.trim())
        .filter(Boolean),
);

const has = (name: string) => tags.value.some((tag) => tag.toLowerCase() === name.trim().toLowerCase());

const suggestions = computed(() => (props.options ?? []).filter((option) => !has(option)));

const draft = ref('');

function setTags(next: string[]) {
    model.value = next.join(', ') || null;
}

function add(name: string) {
    const clean = name.trim();
    if (clean && !has(clean)) setTags([...tags.value, clean]);
}

function addDraft() {
    add(draft.value);
    draft.value = '';
}

function remove(index: number) {
    setTags(tags.value.filter((_, i) => i !== index));
}
</script>

<template>
    <div class="grid gap-2">
        <Label>{{ label }}</Label>

        <div v-if="tags.length" class="flex flex-wrap gap-1.5">
            <span v-for="(tag, i) in tags" :key="i" class="inline-flex items-center gap-1 rounded-full bg-muted px-2.5 py-1 text-sm">
                {{ tag }}
                <button type="button" class="text-muted-foreground hover:text-foreground" title="Quitar" @click="remove(i)">
                    <X class="h-3.5 w-3.5" />
                </button>
            </span>
        </div>

        <div v-if="suggestions.length" class="flex flex-wrap gap-1.5">
            <button
                v-for="opt in suggestions"
                :key="opt"
                type="button"
                class="inline-flex items-center gap-1 rounded-full border border-dashed px-2.5 py-1 text-sm text-muted-foreground hover:border-solid hover:text-foreground"
                @click="add(opt)"
            >
                <Plus class="h-3.5 w-3.5" /> {{ opt }}
            </button>
        </div>

        <div class="flex items-center gap-2">
            <Input v-model="draft" class="flex-1" placeholder="Otro nombre" @keyup.enter.prevent="addDraft" />
            <Button type="button" variant="outline" size="sm" @click="addDraft">Agregar</Button>
        </div>

        <p class="text-xs text-muted-foreground">
            La lista sale de <Link href="/admin/datos-recurrentes" class="underline">Datos recurrentes</Link>. También podés escribir un nombre
            suelto.
        </p>
        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
    </div>
</template>
