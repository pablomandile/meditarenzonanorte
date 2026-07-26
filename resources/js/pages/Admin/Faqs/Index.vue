<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, ChevronDown, LoaderCircle, Plus, Trash2 } from 'lucide-vue-next';
import { reactive } from 'vue';

type Faq = { id: number; question: string; answer: string; position: number; visible: boolean };

const props = defineProps<{ faqs: Faq[] }>();

const breadcrumbs = [{ title: 'Preguntas frecuentes', href: '/admin/faqs' }];

const open = reactive<Record<number, boolean>>({});
const drafts = reactive<Record<number, { question: string; answer: string; visible: boolean; saving: boolean }>>({});

function toggleOpen(faq: Faq) {
    open[faq.id] = !open[faq.id];
    if (open[faq.id] && !drafts[faq.id]) {
        drafts[faq.id] = { question: faq.question, answer: faq.answer, visible: faq.visible, saving: false };
    }
}

function save(faq: Faq) {
    const draft = drafts[faq.id];
    draft.saving = true;
    router.put(
        route('admin.faqs.update', faq.id),
        { question: draft.question, answer: draft.answer, visible: draft.visible },
        { preserveScroll: true, onFinish: () => (draft.saving = false) },
    );
}

function move(id: number, direction: 'up' | 'down') {
    router.patch(route('admin.faqs.move', id), { direction }, { preserveScroll: true });
}

function destroy(faq: Faq) {
    if (confirm(`¿Eliminar la pregunta "${faq.question}"? Se quitará de todas las páginas que la muestran.`)) {
        router.delete(route('admin.faqs.destroy', faq.id), { preserveScroll: true });
    }
}

const createForm = useForm({ question: '', answer: '', visible: true });

function store() {
    createForm.post(route('admin.faqs.store'), {
        preserveScroll: true,
        onSuccess: () => createForm.reset(),
    });
}
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <Head title="Preguntas frecuentes" />

        <div class="flex flex-col gap-4 p-4">
            <div>
                <h1 class="text-xl font-semibold">Preguntas frecuentes</h1>
                <p class="text-sm text-muted-foreground">
                    Estas preguntas se comparten entre páginas: al editarlas acá, cambian en todas las páginas que las muestran.
                </p>
            </div>

            <Card>
                <CardContent class="p-0">
                    <div class="divide-y">
                        <div v-for="(faq, index) in props.faqs" :key="faq.id" class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="flex flex-col">
                                    <button
                                        class="rounded p-0.5 text-muted-foreground hover:bg-muted disabled:opacity-30"
                                        :disabled="index === 0"
                                        @click="move(faq.id, 'up')"
                                    >
                                        <ArrowUp class="h-4 w-4" />
                                    </button>
                                    <button
                                        class="rounded p-0.5 text-muted-foreground hover:bg-muted disabled:opacity-30"
                                        :disabled="index === props.faqs.length - 1"
                                        @click="move(faq.id, 'down')"
                                    >
                                        <ArrowDown class="h-4 w-4" />
                                    </button>
                                </div>

                                <button class="flex min-w-0 flex-1 items-center gap-2 text-left" @click="toggleOpen(faq)">
                                    <span class="truncate font-medium" :class="{ 'text-muted-foreground line-through': !faq.visible }">
                                        {{ faq.question }}
                                    </span>
                                    <ChevronDown class="h-4 w-4 shrink-0 text-muted-foreground transition" :class="{ 'rotate-180': open[faq.id] }" />
                                </button>

                                <Button variant="ghost" size="sm" class="text-red-600" @click="destroy(faq)">
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>

                            <div v-if="open[faq.id] && drafts[faq.id]" class="mt-3 grid gap-3 border-t pt-3">
                                <div class="grid gap-1">
                                    <Label class="text-xs">Pregunta</Label>
                                    <Input v-model="drafts[faq.id].question" />
                                </div>
                                <div class="grid gap-1">
                                    <Label class="text-xs">Respuesta</Label>
                                    <textarea
                                        v-model="drafts[faq.id].answer"
                                        rows="4"
                                        class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                    ></textarea>
                                </div>
                                <div class="flex items-center justify-between">
                                    <label class="flex items-center gap-2 text-sm">
                                        <input v-model="drafts[faq.id].visible" type="checkbox" class="h-4 w-4 rounded border-gray-300" />
                                        Visible
                                    </label>
                                    <Button size="sm" :disabled="drafts[faq.id].saving" @click="save(faq)">
                                        <LoaderCircle v-if="drafts[faq.id].saving" class="mr-1 h-4 w-4 animate-spin" />
                                        Guardar
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="grid gap-3 pt-6">
                    <h2 class="font-medium">Agregar pregunta</h2>
                    <div class="grid gap-1">
                        <Label class="text-xs">Pregunta</Label>
                        <Input v-model="createForm.question" />
                        <p v-if="createForm.errors.question" class="text-sm text-red-600">{{ createForm.errors.question }}</p>
                    </div>
                    <div class="grid gap-1">
                        <Label class="text-xs">Respuesta</Label>
                        <textarea
                            v-model="createForm.answer"
                            rows="4"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        ></textarea>
                        <p v-if="createForm.errors.answer" class="text-sm text-red-600">{{ createForm.errors.answer }}</p>
                    </div>
                    <Button class="w-fit" :disabled="createForm.processing" @click="store">
                        <Plus class="mr-1 h-4 w-4" /> Agregar
                    </Button>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
