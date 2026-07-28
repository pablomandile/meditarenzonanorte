<script setup lang="ts">
import { buttonVariants } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { AlertDialogCancel, useForwardProps, type AlertDialogCancelProps } from 'radix-vue';
import { computed, type HTMLAttributes } from 'vue';

const props = defineProps<AlertDialogCancelProps & { class?: HTMLAttributes['class'] }>();

const delegatedProps = computed(() => {
    const { class: _, ...delegated } = props;

    return delegated;
});

const forwarded = useForwardProps(delegatedProps);
</script>

<template>
    <AlertDialogCancel v-bind="forwarded" :class="cn(buttonVariants({ variant: 'outline' }), props.class)">
        <slot />
    </AlertDialogCancel>
</template>
