<script setup lang="ts">
import NameListCard from '@/components/admin/NameListCard.vue';
import TeacherDialog, { type TeacherItem } from '@/components/admin/TeacherDialog.vue';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { img } from '@/lib/site';
import { Head } from '@inertiajs/vue3';
import { MapPin, Pencil, Ticket, UserRound, Users } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    teachers: TeacherItem[];
    venues: { id: number; name: string }[];
    prices: { id: number; name: string }[];
}>();

const editingId = ref<number | null>(null);

/** Se busca en la lista cada vez: después de guardar, llega la versión nueva. */
const editing = computed(() => props.teachers.find((teacher) => teacher.id === editingId.value) ?? null);

const breadcrumbs = [{ title: 'Datos recurrentes', href: '/admin/datos-recurrentes' }];
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <Head title="Datos recurrentes" />

        <div class="admin-sections mx-auto flex w-full max-w-3xl flex-col gap-4 p-4">
            <div>
                <h1 class="text-xl font-semibold">Datos recurrentes</h1>
                <p class="text-sm text-muted-foreground">
                    Cargá acá los maestr@s, los lugares y los precios que se repiten, y después elegilos en los campos “Maestr@”, “Lugar” y “Precio”
                    de las fichas de clase (en Clases semanales y Cursos y Retiros), sin volver a escribirlos. Tocá cada lista para desplegarla.
                </p>
            </div>

            <NameListCard
                title="Maestr@s"
                :icon="Users"
                description="Aparecen para elegir en el campo “Maestr@” de cada ficha de clase. Con “Editar” les cargás una descripción y una foto, que se ven al tocar su nombre en la ficha."
                :items="teachers"
                store-route="admin.recurring.teachers.store"
                destroy-route="admin.recurring.teachers.destroy"
                placeholder="Ej.: Kelsang Panchen"
                noun="maestr@"
            >
                <template #item-before="{ item }">
                    <img v-if="item.photo" :src="img(item.photo as string)" alt="" class="h-8 w-8 shrink-0 rounded-full object-cover" />
                    <span v-else class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-muted text-muted-foreground">
                        <UserRound class="h-4 w-4" />
                    </span>
                </template>
                <template #item-actions="{ item }">
                    <span v-if="!item.description && !item.photo" class="hidden shrink-0 text-xs text-muted-foreground sm:inline"
                        >Sin descripción</span
                    >
                    <Button variant="ghost" size="sm" class="shrink-0" title="Editar descripción y foto" @click="editingId = item.id">
                        <Pencil class="h-4 w-4" />
                        <span class="ml-1.5 hidden sm:inline">Editar</span>
                    </Button>
                </template>
            </NameListCard>

            <NameListCard
                title="Lugares"
                :icon="MapPin"
                description="Aparecen para elegir en el campo “Lugar” de cada ficha de clase. Poné la dirección tal como querés que se lea."
                :items="venues"
                store-route="admin.recurring.venues.store"
                destroy-route="admin.recurring.venues.destroy"
                placeholder="Ej.: Psj. Cajaraville 173, Barrio Martin, Rosario"
                noun="lugar"
            />

            <NameListCard
                title="Precios"
                :icon="Ticket"
                description="Aparecen para elegir en el campo “Precio” de cada ficha de clase. Escribilo tal como querés que se lea."
                :items="prices"
                store-route="admin.recurring.prices.store"
                destroy-route="admin.recurring.prices.destroy"
                placeholder="Ej.: $5.000 (Bono 4 clases $15.000)"
                noun="precio"
            />
        </div>

        <TeacherDialog :teacher="editing" @close="editingId = null" />
    </AdminLayout>
</template>
