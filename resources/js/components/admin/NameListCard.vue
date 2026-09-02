<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { useConfirm } from '@/composables/useConfirm';
import { router, useForm } from '@inertiajs/vue3';
import { Plus, Trash2 } from 'lucide-vue-next';

type Item = { id: number; name: string };

const props = defineProps<{
    title: string;
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
    <Card>
        <CardContent class="grid gap-3 pt-6">
            <div>
                <h2 class="font-medium">{{ title }}</h2>
                <p class="text-sm text-muted-foreground">{{ description }}</p>
            </div>

            <ul v-if="items.length" class="divide-y rounded-lg border">
                <li v-for="item in items" :key="item.id" class="flex items-center justify-between gap-2 px-3 py-2 text-sm">
                    <span class="min-w-0 truncate">{{ item.name }}</span>
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
        </CardContent>
    </Card>
</template>
