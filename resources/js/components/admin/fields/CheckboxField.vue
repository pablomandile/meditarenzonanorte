<script setup lang="ts">
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { useId } from 'vue';

defineProps<{ label: string; error?: string; hint?: string }>();

/** Se guarda booleano; las fichas viejas no tienen la clave (null = destildada). */
const model = defineModel<boolean | null>();

const id = useId();
</script>

<template>
    <div class="grid gap-2">
        <div class="flex items-center gap-3">
            <Checkbox :id="id" :checked="model === true" @update:checked="model = $event === true" />
            <Label :for="id" class="cursor-pointer">{{ label }}</Label>
        </div>
        <p v-if="hint" class="text-xs text-muted-foreground">{{ hint }}</p>
        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
    </div>
</template>
