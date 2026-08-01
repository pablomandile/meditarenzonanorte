<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, ChevronRight, ExternalLink, Eye, EyeOff } from 'lucide-vue-next';
import { computed } from 'vue';

type PageRow = {
    id: number;
    slug: string;
    title: string;
    menu_label: string | null;
    visible: boolean;
    sections_count: number;
};

const props = defineProps<{ pages: PageRow[] }>();

const breadcrumbs = [{ title: 'Páginas', href: '/admin/pages' }];

// La home es la raíz del sitio y no figura en el menú: no se oculta ni se mueve.
const isHome = (page: PageRow) => page.slug === 'home';
const sortable = computed(() => props.pages.filter((page) => !isHome(page)));
const isFirst = (page: PageRow) => sortable.value[0]?.id === page.id;
const isLast = (page: PageRow) => sortable.value[sortable.value.length - 1]?.id === page.id;

function toggle(id: number) {
    router.patch(route('admin.pages.toggle', id), {}, { preserveScroll: true });
}

function move(id: number, direction: 'up' | 'down') {
    router.patch(route('admin.pages.move', id), { direction }, { preserveScroll: true });
}
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <Head title="Páginas" />

        <div class="flex flex-col gap-4 p-4">
            <div>
                <h1 class="text-xl font-semibold">Páginas del sitio</h1>
                <p class="text-sm text-muted-foreground">
                    Elegí una página para editar sus secciones. Con las flechas cambiás su lugar en el menú del sitio, y al ocultarla sale del menú y
                    su dirección deja de estar disponible.
                </p>
            </div>

            <Card>
                <CardContent class="p-0">
                    <div class="divide-y">
                        <div
                            v-for="page in pages"
                            :key="page.id"
                            class="flex items-center gap-3 overflow-hidden px-4 py-3"
                            :class="{ 'opacity-50': !page.visible }"
                        >
                            <div class="flex flex-col">
                                <button
                                    class="rounded p-0.5 text-muted-foreground hover:bg-muted disabled:opacity-30"
                                    :disabled="isHome(page) || isFirst(page)"
                                    :title="isHome(page) ? 'La página de inicio no se reordena' : 'Subir en el menú'"
                                    @click="move(page.id, 'up')"
                                >
                                    <ArrowUp class="h-4 w-4" />
                                </button>
                                <button
                                    class="rounded p-0.5 text-muted-foreground hover:bg-muted disabled:opacity-30"
                                    :disabled="isHome(page) || isLast(page)"
                                    :title="isHome(page) ? 'La página de inicio no se reordena' : 'Bajar en el menú'"
                                    @click="move(page.id, 'down')"
                                >
                                    <ArrowDown class="h-4 w-4" />
                                </button>
                            </div>

                            <Link :href="`/admin/pages/${page.id}`" class="group min-w-0 flex-1">
                                <p class="truncate font-medium group-hover:underline">{{ page.title }}</p>
                                <p class="truncate text-sm text-muted-foreground">
                                    /{{ page.slug === 'home' ? '' : page.slug }} · {{ page.sections_count }} secciones
                                    <span v-if="!page.menu_label">· fuera del menú</span>
                                </p>
                            </Link>

                            <Button
                                v-if="!isHome(page)"
                                variant="ghost"
                                size="sm"
                                :title="page.visible ? 'Ocultar página' : 'Mostrar página'"
                                @click="toggle(page.id)"
                            >
                                <Eye v-if="page.visible" class="h-4 w-4" />
                                <EyeOff v-else class="h-4 w-4 text-red-500" />
                                <span class="ml-1.5 hidden sm:inline">{{ page.visible ? 'Visible' : 'Oculta' }}</span>
                            </Button>

                            <a
                                :href="page.slug === 'home' ? '/' : `/${page.slug}`"
                                target="_blank"
                                class="text-muted-foreground hover:text-foreground"
                                title="Ver en el sitio"
                            >
                                <ExternalLink class="h-4 w-4" />
                            </a>
                            <Link :href="`/admin/pages/${page.id}`" class="text-muted-foreground">
                                <ChevronRight class="h-5 w-5" />
                            </Link>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
