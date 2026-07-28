<script setup lang="ts">
import {
    AlertDialog,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { buttonVariants } from '@/components/ui/button';
import { useConfirm } from '@/composables/useConfirm';

const { open, options, settle } = useConfirm();

function onOpenChange(value: boolean) {
    // Escape o clic fuera: se resuelve como cancelado.
    if (!value) settle(false);
}
</script>

<template>
    <AlertDialog :open="open" @update:open="onOpenChange">
        <AlertDialogContent v-if="options">
            <AlertDialogHeader>
                <AlertDialogTitle>{{ options.title }}</AlertDialogTitle>
                <AlertDialogDescription>{{ options.description }}</AlertDialogDescription>
            </AlertDialogHeader>

            <AlertDialogFooter>
                <!-- Cancel de radix: recibe el foco inicial y su cierre resuelve en false. -->
                <AlertDialogCancel>
                    {{ options.cancelLabel ?? 'Cancelar' }}
                </AlertDialogCancel>

                <!--
                    Botón propio en lugar de AlertDialogAction: el de radix cierra el
                    diálogo con un handler que corre antes que el @click de acá, así que
                    la promesa se resolvía en false y la acción nunca se ejecutaba.
                -->
                <button type="button" :class="buttonVariants({ variant: options.destructive ? 'destructive' : 'default' })" @click="settle(true)">
                    {{ options.confirmLabel ?? 'Confirmar' }}
                </button>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
