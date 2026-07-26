<script setup lang="ts">
import { Label } from '@/components/ui/label';

defineProps<{ label: string; error?: string; rows?: number }>();

const model = defineModel<string | null>();
</script>

<template>
    <div class="grid gap-2">
        <Label>{{ label }}</Label>
        <textarea
            :value="model ?? ''"
            :rows="rows ?? 6"
            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
            @input="model = ($event.target as HTMLTextAreaElement).value || null"
        ></textarea>
        <p class="text-xs text-muted-foreground">Separá los párrafos con una línea en blanco.</p>
        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
    </div>
</template>
