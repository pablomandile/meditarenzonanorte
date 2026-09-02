<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Link } from '@inertiajs/vue3';
import { onClickOutside } from '@vueuse/core';
import { Check } from 'lucide-vue-next';
import { computed, ref } from 'vue';

/**
 * Campo de texto con una lista de sugerencias que se despliega debajo. No usa
 * <datalist> a propósito: en el Safari de iOS no funciona. La lista se filtra con
 * lo que se escribe y siempre se puede escribir un valor que no está.
 */
const props = defineProps<{ label: string; error?: string; hint?: string; options?: string[] }>();

const model = defineModel<string | null>();

const root = ref<HTMLElement | null>(null);
const open = ref(false);

onClickOutside(root, () => (open.value = false));

const matches = computed(() => {
    const all = props.options ?? [];
    const q = (model.value ?? '').trim().toLowerCase();

    // Lista completa cuando no hay nada escrito o cuando lo escrito coincide
    // exacto con una opción (para poder cambiar de elección).
    if (!q || all.some((option) => option.toLowerCase() === q)) return all;

    const filtered = all.filter((option) => option.toLowerCase().includes(q));

    // Si lo escrito no coincide con ninguna (una dirección propia, o a mitad de
    // tipear una nueva), mostramos igual la lista entera: para eso se abrió.
    return filtered.length ? filtered : all;
});

function pick(option: string) {
    model.value = option;
    open.value = false;
}
</script>

<template>
    <div ref="root" class="grid gap-2">
        <Label>{{ label }}</Label>

        <div class="relative">
            <Input
                :model-value="model ?? ''"
                autocomplete="off"
                @update:model-value="model = ($event as string) || null"
                @focus="open = true"
                @click="open = true"
            />

            <ul
                v-if="open && matches.length"
                class="absolute z-20 mt-1 max-h-56 w-full overflow-auto rounded-md border bg-popover p-1 text-sm text-popover-foreground shadow-md"
            >
                <li
                    v-for="option in matches"
                    :key="option"
                    class="flex cursor-pointer items-start gap-2 rounded px-2 py-2 hover:bg-accent"
                    @click="pick(option)"
                >
                    <Check class="mt-0.5 h-4 w-4 shrink-0" :class="option === model ? 'text-primary opacity-100' : 'opacity-0'" />
                    <span class="min-w-0">{{ option }}</span>
                </li>
            </ul>
        </div>

        <p v-if="hint" class="text-xs text-muted-foreground">{{ hint }}</p>
        <p v-else-if="options && options.length" class="text-xs text-muted-foreground">
            Elegí de <Link href="/admin/datos-recurrentes" class="underline">Datos recurrentes</Link> o escribí otro.
        </p>
        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
    </div>
</template>
