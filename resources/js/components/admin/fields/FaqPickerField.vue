<script setup lang="ts">
import { Label } from '@/components/ui/label';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    label: string;
    pool: { id: number; question: string; visible: boolean }[];
    error?: string;
}>();

const model = defineModel<number[]>({ default: () => [] });

const selected = computed(() => new Set(model.value ?? []));

function toggle(id: number) {
    // Keep the pool's order regardless of click order.
    const next = props.pool.filter((faq) => (faq.id === id ? !selected.value.has(id) : selected.value.has(faq.id)));
    model.value = next.map((faq) => faq.id);
}
</script>

<template>
    <div class="grid gap-2">
        <Label>{{ label }}</Label>

        <div class="divide-y rounded-lg border">
            <label
                v-for="faq in pool"
                :key="faq.id"
                class="flex cursor-pointer items-center gap-3 px-4 py-3 text-sm hover:bg-muted/50"
            >
                <input
                    type="checkbox"
                    class="h-4 w-4 rounded border-gray-300"
                    :checked="selected.has(faq.id)"
                    @change="toggle(faq.id)"
                />
                <span :class="{ 'text-muted-foreground line-through': !faq.visible }">{{ faq.question }}</span>
            </label>
        </div>

        <p class="text-xs text-muted-foreground">
            Las preguntas se administran desde
            <Link href="/admin/faqs" class="underline">Preguntas frecuentes</Link>.
        </p>
        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
    </div>
</template>
