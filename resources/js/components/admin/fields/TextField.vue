<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

defineProps<{ label: string; error?: string; hint?: string }>();

const model = defineModel<string | null>();
</script>

<template>
    <div class="grid gap-2">
        <Label>{{ label }}</Label>
        <Input :model-value="model ?? ''" @update:model-value="model = ($event as string) || null" />
        <p v-if="hint" class="text-xs text-muted-foreground">{{ hint }}</p>
        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
    </div>
</template>
