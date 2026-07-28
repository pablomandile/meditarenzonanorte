<script setup lang="ts">
import { cn } from '@/lib/utils';
import { AlertDialogDescription, useForwardProps, type AlertDialogDescriptionProps } from 'radix-vue';
import { computed, type HTMLAttributes } from 'vue';

const props = defineProps<AlertDialogDescriptionProps & { class?: HTMLAttributes['class'] }>();

const delegatedProps = computed(() => {
    const { class: _, ...delegated } = props;

    return delegated;
});

const forwarded = useForwardProps(delegatedProps);
</script>

<template>
    <AlertDialogDescription v-bind="forwarded" :class="cn('text-sm text-muted-foreground', props.class)">
        <slot />
    </AlertDialogDescription>
</template>
