<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useConfirm } from '@/composables/useConfirm';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, GraduationCap, LoaderCircle, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { reactive } from 'vue';

type Tutorial = {
    id: number;
    title: string;
    youtube_url: string;
    position: number;
    embed_url: string | null;
    thumbnail_url: string | null;
};

const props = defineProps<{ tutorials: Tutorial[] }>();

const breadcrumbs = [{ title: 'Tutoriales', href: '/admin/tutorials' }];

const { confirm } = useConfirm();

const open = reactive<Record<number, boolean>>({});
const drafts = reactive<Record<number, { title: string; youtube_url: string; saving: boolean }>>({});

function toggleEdit(tutorial: Tutorial) {
    open[tutorial.id] = !open[tutorial.id];
    if (open[tutorial.id] && !drafts[tutorial.id]) {
        drafts[tutorial.id] = { title: tutorial.title, youtube_url: tutorial.youtube_url, saving: false };
    }
}

function save(tutorial: Tutorial) {
    const draft = drafts[tutorial.id];
    draft.saving = true;
    router.put(
        route('admin.tutorials.update', tutorial.id),
        { title: draft.title, youtube_url: draft.youtube_url },
        {
            preserveScroll: true,
            onSuccess: () => (open[tutorial.id] = false),
            onFinish: () => (draft.saving = false),
        },
    );
}

function move(id: number, direction: 'up' | 'down') {
    router.patch(route('admin.tutorials.move', id), { direction }, { preserveScroll: true });
}

async function destroy(tutorial: Tutorial) {
    const accepted = await confirm({
        title: 'Eliminar tutorial',
        description: `Se elimina “${tutorial.title}”. No se puede deshacer (el video en YouTube no se toca).`,
        confirmLabel: 'Eliminar',
        destructive: true,
    });

    if (accepted) {
        router.delete(route('admin.tutorials.destroy', tutorial.id), { preserveScroll: true });
    }
}

const createForm = useForm({ title: '', youtube_url: '' });

function store() {
    createForm.post(route('admin.tutorials.store'), {
        preserveScroll: true,
        onSuccess: () => createForm.reset(),
    });
}
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <Head title="Tutoriales" />

        <div class="mx-auto flex w-full max-w-3xl flex-col gap-4 p-4">
            <div>
                <h1 class="text-xl font-semibold">Tutoriales</h1>
                <p class="text-sm text-muted-foreground">
                    Videos de YouTube que explican cómo usar el panel. Solo los ve quien administra el sitio; no aparecen en la web pública.
                </p>
            </div>

            <div v-if="!props.tutorials.length" class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground">
                <GraduationCap class="mx-auto mb-2 h-6 w-6" />
                Todavía no hay tutoriales. Agregá el primero abajo.
            </div>

            <Card v-for="(tutorial, index) in props.tutorials" :key="tutorial.id">
                <CardContent class="grid gap-3 pt-6">
                    <div class="flex items-start gap-3">
                        <div class="flex flex-col">
                            <button
                                class="rounded p-0.5 text-muted-foreground hover:bg-muted disabled:opacity-30"
                                :disabled="index === 0"
                                title="Subir"
                                @click="move(tutorial.id, 'up')"
                            >
                                <ArrowUp class="h-4 w-4" />
                            </button>
                            <button
                                class="rounded p-0.5 text-muted-foreground hover:bg-muted disabled:opacity-30"
                                :disabled="index === props.tutorials.length - 1"
                                title="Bajar"
                                @click="move(tutorial.id, 'down')"
                            >
                                <ArrowDown class="h-4 w-4" />
                            </button>
                        </div>

                        <p class="min-w-0 flex-1 truncate pt-1 font-medium">{{ tutorial.title }}</p>

                        <Button variant="ghost" size="sm" title="Editar" @click="toggleEdit(tutorial)">
                            <Pencil class="h-4 w-4" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="sm"
                            class="text-red-600 hover:bg-red-50 hover:text-red-700"
                            title="Eliminar"
                            @click="destroy(tutorial)"
                        >
                            <Trash2 class="h-4 w-4" />
                        </Button>
                    </div>

                    <div v-if="tutorial.embed_url" class="aspect-video w-full overflow-hidden rounded-md bg-black">
                        <iframe
                            :src="tutorial.embed_url"
                            :title="tutorial.title"
                            class="h-full w-full"
                            loading="lazy"
                            referrerpolicy="strict-origin-when-cross-origin"
                            allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            allowfullscreen
                        ></iframe>
                    </div>
                    <p v-else class="text-sm text-red-600">Ese enlace no es un video de YouTube. Editalo para arreglarlo.</p>

                    <div v-if="open[tutorial.id] && drafts[tutorial.id]" class="grid gap-3 border-t pt-3">
                        <div class="grid gap-1">
                            <Label class="text-xs">Nombre</Label>
                            <Input v-model="drafts[tutorial.id].title" />
                        </div>
                        <div class="grid gap-1">
                            <Label class="text-xs">Enlace del video de YouTube</Label>
                            <Input v-model="drafts[tutorial.id].youtube_url" placeholder="https://www.youtube.com/watch?v=..." />
                        </div>
                        <div class="flex justify-end">
                            <Button size="sm" :disabled="drafts[tutorial.id].saving" @click="save(tutorial)">
                                <LoaderCircle v-if="drafts[tutorial.id].saving" class="mr-1 h-4 w-4 animate-spin" />
                                Guardar
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="grid gap-3 pt-6">
                    <h2 class="font-medium">Agregar tutorial</h2>
                    <div class="grid gap-1">
                        <Label class="text-xs">Nombre</Label>
                        <Input v-model="createForm.title" placeholder="Ej.: Cómo editar una página" />
                        <p v-if="createForm.errors.title" class="text-sm text-red-600">{{ createForm.errors.title }}</p>
                    </div>
                    <div class="grid gap-1">
                        <Label class="text-xs">Enlace del video de YouTube</Label>
                        <Input v-model="createForm.youtube_url" placeholder="https://www.youtube.com/watch?v=..." />
                        <p class="text-xs text-muted-foreground">
                            Pegá el enlace de la barra del navegador o el que copiás con el botón "Compartir" del video.
                        </p>
                        <p v-if="createForm.errors.youtube_url" class="text-sm text-red-600">{{ createForm.errors.youtube_url }}</p>
                    </div>
                    <Button class="w-fit" :disabled="createForm.processing" @click="store"> <Plus class="mr-1 h-4 w-4" /> Agregar </Button>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
