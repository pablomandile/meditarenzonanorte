<script setup lang="ts">
import AdminSection from '@/components/admin/AdminSection.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useConfirm } from '@/composables/useConfirm';
import { router, useForm } from '@inertiajs/vue3';
import { Plus, Trash2 } from 'lucide-vue-next';
import { ref, watch, type Component } from 'vue';

type Item = { id: number; name: string; [key: string]: unknown };

const props = defineProps<{
    title: string;
    icon?: Component;
    description: string;
    items: Item[];
    /** Nombre de ruta Ziggy para crear (POST) y borrar (DELETE, recibe el id). */
    storeRoute: string;
    destroyRoute: string;
    placeholder?: string;
    /** Cómo nombrar un elemento en el diálogo de borrado ("maestr@", "lugar"). */
    noun: string;
}>();

const { confirm } = useConfirm();

const form = useForm({ name: '' });

// Arranca plegada: la página muestra las listas como un índice. Un error de
// validación la abre, para que el mensaje no quede escondido.
const open = ref(false);

watch(
    () => form.errors.name,
    (error) => {
        if (error) open.value = true;
    },
);

function add() {
    form.post(route(props.storeRoute), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

async function remove(item: Item) {
    const accepted = await confirm({
        title: `Eliminar ${props.noun}`,
        description: `Se quita “${item.name}” de la lista. Las fichas que ya lo usan no cambian.`,
        confirmLabel: 'Eliminar',
        destructive: true,
    });

    if (accepted) {
        router.delete(route(props.destroyRoute, item.id), { preserveScroll: true });
    }
}
</script>

<template>
    <AdminSection v-model:open="open" collapsible :title="title" :icon="icon" :description="description" body-class="grid grid-cols-1 gap-3">
        <template #actions>
            <span class="rounded-full bg-white/20 px-2 py-0.5 text-xs font-semibold tabular-nums" :title="`${items.length} en la lista`">
                {{ items.length }}
            </span>
        </template>

        <ul v-if="items.length" class="divide-y rounded-lg border bg-card">
            <li v-for="item in items" :key="item.id" class="flex items-center gap-2 px-3 py-2 text-sm">
                <!-- `item-before`: lo que va antes del nombre (la foto del maestr@, por ejemplo). -->
                <slot name="item-before" :item="item" />
                <!-- Baja de renglón en vez de cortarse: un precio largo se tiene que poder leer entero. -->
                <span class="min-w-0 flex-1 break-words">{{ item.name }}</span>
                <!-- `item-actions`: botones propios de la lista, antes de Eliminar. -->
                <slot name="item-actions" :item="item" />
                <Button
                    variant="ghost"
                    size="icon"
                    class="shrink-0 text-red-600 hover:bg-red-50 hover:text-red-700"
                    title="Eliminar"
                    @click="remove(item)"
                >
                    <Trash2 class="h-4 w-4" />
                </Button>
            </li>
        </ul>
        <p v-else class="text-sm text-muted-foreground">Todavía no hay nada en esta lista.</p>

        <form class="flex items-start gap-2" @submit.prevent="add">
            <div class="flex-1">
                <Input v-model="form.name" :placeholder="placeholder" />
                <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
            </div>
            <Button type="submit" :disabled="form.processing"> <Plus class="mr-1 h-4 w-4" /> Agregar </Button>
        </form>
    </AdminSection>
</template>
