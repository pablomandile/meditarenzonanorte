<script setup lang="ts">
import { Label } from '@/components/ui/label';

/** empty: el texto de la opción vacía, para cuando vacío es una elección (el valor por defecto). */
defineProps<{ label: string; options: Record<string, string>; empty?: string; error?: string }>();

const model = defineModel<string | null>();
</script>

<template>
    <div class="grid gap-2">
        <Label>{{ label }}</Label>
        <select
            :value="model ?? ''"
            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
            @change="model = ($event.target as HTMLSelectElement).value || null"
        >
            <option value="">{{ empty ?? '' }}</option>
            <option v-for="(text, value) in options" :key="value" :value="value">{{ text }}</option>
        </select>
        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
    </div>
</template>
