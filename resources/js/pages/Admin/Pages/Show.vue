<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, Copy, Eye, EyeOff, Pencil } from 'lucide-vue-next';

const props = defineProps<{
    page: { id: number; slug: string; title: string };
    sections: {
        id: number;
        type: string;
        type_label: string;
        key: string;
        title: string | null;
        position: number;
        visible: boolean;
    }[];
}>();

const breadcrumbs = [
    { title: 'Páginas', href: '/admin/pages' },
    { title: props.page.title, href: `/admin/pages/${props.page.id}` },
];

function toggle(id: number) {
    router.patch(route('admin.sections.toggle', id), {}, { preserveScroll: true });
}

function move(id: number, direction: 'up' | 'down') {
    router.patch(route('admin.sections.move', id), { direction }, { preserveScroll: true });
}

function duplicate(section: { id: number; title: string | null; type_label: string }) {
    const name = section.title || section.type_label;

    if (confirm(`¿Clonar "${name}"? La copia se agrega justo debajo y queda oculta hasta que la muestres.`)) {
        router.post(route('admin.sections.duplicate', section.id), {}, { preserveScroll: true });
    }
}
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Secciones — ${page.title}`" />

        <div class="flex flex-col gap-4 p-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h1 class="text-xl font-semibold">{{ page.title }}</h1>
                    <p class="text-sm text-muted-foreground">
                        Ordená, ocultá o editá las secciones. Los cambios se ven al instante en el sitio.
                    </p>
                </div>
                <Button as-child variant="outline">
                    <a :href="page.slug === 'home' ? '/' : `/${page.slug}`" target="_blank">Ver página</a>
                </Button>
            </div>

            <Card>
                <CardContent class="p-0">
                    <div class="divide-y">
                        <div
                            v-for="(section, index) in sections"
                            :key="section.id"
                            class="flex items-center gap-3 overflow-hidden px-4 py-3"
                            :class="{ 'opacity-50': !section.visible }"
                        >
                            <div class="flex flex-col">
                                <button
                                    class="rounded p-0.5 text-muted-foreground hover:bg-muted disabled:opacity-30"
                                    :disabled="index === 0"
                                    title="Subir"
                                    @click="move(section.id, 'up')"
                                >
                                    <ArrowUp class="h-4 w-4" />
                                </button>
                                <button
                                    class="rounded p-0.5 text-muted-foreground hover:bg-muted disabled:opacity-30"
                                    :disabled="index === sections.length - 1"
                                    title="Bajar"
                                    @click="move(section.id, 'down')"
                                >
                                    <ArrowDown class="h-4 w-4" />
                                </button>
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium">
                                    {{ section.title || section.type_label }}
                                </p>
                                <p class="text-xs text-muted-foreground">{{ section.type_label }}</p>
                            </div>

                            <Button
                                variant="ghost"
                                size="sm"
                                :title="section.visible ? 'Ocultar sección' : 'Mostrar sección'"
                                @click="toggle(section.id)"
                            >
                                <Eye v-if="section.visible" class="h-4 w-4" />
                                <EyeOff v-else class="h-4 w-4 text-red-500" />
                                <span class="ml-1.5 hidden sm:inline">{{ section.visible ? 'Visible' : 'Oculta' }}</span>
                            </Button>

                            <Button variant="ghost" size="sm" title="Clonar sección" @click="duplicate(section)">
                                <Copy class="h-4 w-4" />
                                <span class="ml-1.5 hidden sm:inline">Clonar</span>
                            </Button>

                            <Button as-child size="sm" variant="outline">
                                <Link :href="`/admin/sections/${section.id}/edit`">
                                    <Pencil class="mr-1 h-4 w-4" /> Editar
                                </Link>
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
