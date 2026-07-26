<script setup lang="ts">
import { Card, CardContent } from '@/components/ui/card';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ChevronRight, ExternalLink } from 'lucide-vue-next';

defineProps<{
    pages: {
        id: number;
        slug: string;
        title: string;
        menu_label: string | null;
        visible: boolean;
        sections_count: number;
    }[];
}>();

const breadcrumbs = [{ title: 'Páginas', href: '/admin/pages' }];
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <Head title="Páginas" />

        <div class="flex flex-col gap-4 p-4">
            <div>
                <h1 class="text-xl font-semibold">Páginas del sitio</h1>
                <p class="text-sm text-muted-foreground">
                    Elegí una página para editar sus secciones, cambiar imágenes u ocultar contenido.
                </p>
            </div>

            <Card>
                <CardContent class="p-0">
                    <div class="divide-y">
                        <div v-for="page in pages" :key="page.id" class="flex items-center justify-between gap-4 px-4 py-3">
                            <Link :href="`/admin/pages/${page.id}`" class="group flex flex-1 items-center gap-3">
                                <div>
                                    <p class="font-medium group-hover:underline">{{ page.title }}</p>
                                    <p class="text-sm text-muted-foreground">
                                        /{{ page.slug === 'home' ? '' : page.slug }} · {{ page.sections_count }} secciones
                                    </p>
                                </div>
                            </Link>

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
