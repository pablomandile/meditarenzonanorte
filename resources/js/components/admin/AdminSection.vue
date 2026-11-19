<script setup lang="ts">
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { cn } from '@/lib/utils';
import { ChevronDown } from 'lucide-vue-next';
import type { Component, HTMLAttributes } from 'vue';

/**
 * Una sección del panel: una franja de color de marca con el nombre y un ícono,
 * y debajo el contenido. Apiladas dentro de un contenedor `.admin-sections`
 * se alternan solas (impar celeste pálido, par blanca; ver app.css).
 *
 * `flush` es para las listas: sin relleno y siempre blanca, con el tinte en
 * las filas pares (ver `zebra` en lib/admin).
 *
 * `collapsible` hace de la franja un botón que pliega y despliega el contenido;
 * `v-model:open` (por defecto abierta) deja que quien la usa la abra o cierre.
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
        collapsible?: boolean;
    }>(),
    { bodyClass: 'grid gap-5' },
);

const open = defineModel<boolean>('open', { default: true });

/**
 * grid-cols-1 es minmax(0, 1fr). Con la columna implícita de un `grid` solo
 * (auto), un texto largo que no corta —un precio truncado, una URL— ensancha la
 * tarjeta y en el celular empuja los botones fuera de la pantalla. Va en la base
 * para todas; una tarjeta que pida columnas (sm:grid-cols-2) las sigue teniendo.
 */
const BODY = 'p-4 sm:p-6 grid-cols-1';
</script>

<template>
    <section
        :id="id"
        class="admin-section scroll-mt-4 overflow-hidden rounded-lg border bg-card text-card-foreground shadow-sm"
        :class="{ 'admin-section--flush': flush }"
    >
        <Collapsible v-if="collapsible" v-model:open="open">
            <header class="flex items-center gap-2 bg-section-head text-section-head-foreground">
                <CollapsibleTrigger class="flex min-w-0 flex-1 items-center gap-2 px-4 py-2 text-left">
                    <component :is="icon" v-if="icon" class="h-4 w-4 shrink-0" />
                    <h2 class="min-w-0 truncate text-sm font-semibold uppercase tracking-wide">{{ title }}</h2>
                    <ChevronDown class="ml-auto h-4 w-4 shrink-0 transition-transform" :class="{ 'rotate-180': open }" />
                </CollapsibleTrigger>
                <div v-if="$slots.actions" class="flex shrink-0 items-center gap-2 pr-4"><slot name="actions" /></div>
            </header>

            <CollapsibleContent>
                <div class="admin-section__body" :class="flush ? '' : cn(BODY, props.bodyClass)">
                    <p v-if="description || $slots.description" class="text-xs text-muted-foreground" :class="{ 'px-4 pt-4': flush }">
                        <slot name="description">{{ description }}</slot>
                    </p>
                    <slot />
                </div>
            </CollapsibleContent>
        </Collapsible>

        <template v-else>
            <header class="flex items-center gap-2 bg-section-head px-4 py-2 text-section-head-foreground">
                <component :is="icon" v-if="icon" class="h-4 w-4 shrink-0" />
                <h2 class="min-w-0 truncate text-sm font-semibold uppercase tracking-wide">{{ title }}</h2>
                <div v-if="$slots.actions" class="ml-auto flex shrink-0 items-center gap-2"><slot name="actions" /></div>
            </header>

            <div class="admin-section__body" :class="flush ? '' : cn(BODY, props.bodyClass)">
                <p v-if="description || $slots.description" class="text-xs text-muted-foreground" :class="{ 'px-4 pt-4': flush }">
                    <slot name="description">{{ description }}</slot>
                </p>
                <slot />
            </div>
        </template>
    </section>
</template>
