<script setup lang="ts">
import { Label } from '@/components/ui/label';
import { Link } from '@inertiajs/vue3';

defineProps<{ label: string; choices: { id: number; name: string }[]; hint?: string; error?: string }>();

/** El id del maestr@ elegido, o null. */
const model = defineModel<number | null>();
</script>

<template>
    <div class="grid gap-2">
        <Label>{{ label }}</Label>
        <select
            :value="model ?? ''"
            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
            @change="model = Number(($event.target as HTMLSelectElement).value) || null"
        >
            <option value="">Ninguno (cargar nombre, biografía y foto acá)</option>
            <option v-for="choice in choices" :key="choice.id" :value="choice.id">{{ choice.name }}</option>
        </select>
        <p class="text-xs text-muted-foreground">
            <template v-if="hint">{{ hint }}</template>
            <template v-else-if="model">
                El nombre, la biografía y la foto salen de
                <Link href="/admin/datos-recurrentes" class="underline">Datos recurrentes</Link>: se editan ahí.
            </template>
            <template v-else>
                Elegí uno de <Link href="/admin/datos-recurrentes" class="underline">Datos recurrentes</Link> para mostrar su descripción y su foto.
            </template>
        </p>
        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
    </div>
</template>
