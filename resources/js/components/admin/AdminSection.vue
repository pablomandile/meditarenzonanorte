<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { Component, HTMLAttributes } from 'vue';

/**
 * Una sección del panel: una franja de color de marca con el nombre y un ícono,
 * y debajo el contenido. Apiladas dentro de un contenedor `.admin-sections`
 * se alternan solas (impar celeste pálido, par blanca; ver app.css).
 *
 * `flush` es para las listas: sin relleno y siempre blanca, con el tinte en
 * las filas pares (ver `zebra` en lib/admin).
 */
const props = withDefaults(
    defineProps<{
        title: string;
        icon?: Component;
        /** Texto gris bajo la franja; para HTML, el slot `description`. */
        description?: string;
        flush?: boolean;
        /** Para los anclas (#newsletter) y los saltos desde los avisos. */
        id?: string;
        bodyClass?: HTMLAttributes['class'];
    }>(),
    { bodyClass: 'grid gap-5' },
);
</script>

<template>
    <section
        :id="id"
        class="admin-section scroll-mt-4 overflow-hidden rounded-lg border bg-card text-card-foreground shadow-sm"
        :class="{ 'admin-section--flush': flush }"
    >
        <header class="flex items-center gap-2 bg-section-head px-4 py-2 text-section-head-foreground">
            <component :is="icon" v-if="icon" class="h-4 w-4 shrink-0" />
            <h2 class="min-w-0 truncate text-sm font-semibold uppercase tracking-wide">{{ title }}</h2>
            <div v-if="$slots.actions" class="ml-auto flex shrink-0 items-center gap-2"><slot name="actions" /></div>
        </header>

        <div class="admin-section__body" :class="flush ? '' : cn('p-4 sm:p-6', props.bodyClass)">
            <p v-if="description || $slots.description" class="text-xs text-muted-foreground" :class="{ 'px-4 pt-4': flush }">
                <slot name="description">{{ description }}</slot>
            </p>
            <slot />
        </div>
    </section>
</template>
