<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Link } from '@inertiajs/vue3';
import { useId } from 'vue';

defineProps<{ label: string; error?: string; hint?: string; options?: string[] }>();

const model = defineModel<string | null>();

const listId = useId();
</script>

<template>
    <div class="grid gap-2">
        <Label>{{ label }}</Label>
        <Input
            :list="options && options.length ? listId : undefined"
            :model-value="model ?? ''"
            @update:model-value="model = ($event as string) || null"
        />
        <datalist v-if="options && options.length" :id="listId">
            <option v-for="opt in options" :key="opt" :value="opt" />
        </datalist>

        <p v-if="hint" class="text-xs text-muted-foreground">{{ hint }}</p>
        <p v-else-if="options && options.length" class="text-xs text-muted-foreground">
            Elegí de <Link href="/admin/datos-recurrentes" class="underline">Datos recurrentes</Link> o escribí otro.
        </p>
        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
    </div>
</template>
