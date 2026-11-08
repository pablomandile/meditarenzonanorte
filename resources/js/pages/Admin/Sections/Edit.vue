<script setup lang="ts">
import SectionForm, { type FieldDef } from '@/components/admin/SectionForm.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

const props = defineProps<{
    section: {
        id: number;
        type: string;
        type_label: string;
        key: string;
        visible: boolean;
        content: Record<string, any>;
    };
    fields: FieldDef[];
    page: { id: number; title: string; slug: string; breadcrumbs: { title: string; href: string }[] };
    faqPool?: { id: number; question: string; visible: boolean }[];
    /** Listas de "Datos recurrentes" para los campos con pool (maestro, lugar). */
    pools?: Record<string, string[]>;
    hints?: Record<string, string>;
}>();

const breadcrumbs = [...props.page.breadcrumbs, { title: props.section.type_label, href: `/admin/sections/${props.section.id}/edit` }];

const form = useForm<{ _method: string; content: Record<string, any>; files: Record<string, any> }>({
    _method: 'put',
    content: JSON.parse(JSON.stringify(props.section.content ?? {})),
    files: {},
});

function submit() {
    form.post(route('admin.sections.update', props.section.id), {
        // Al guardar, el server vuelve a la página de la sección.
        forceFormData: true,
    });
}
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Editar — ${section.type_label}`" />

        <div class="mx-auto flex w-full max-w-3xl flex-col gap-4 p-4">
            <div>
                <h1 class="text-xl font-semibold">{{ section.type_label }}</h1>
                <p class="text-sm text-muted-foreground">Página: {{ page.title }}</p>
            </div>

            <form @submit.prevent="submit">
                <Card>
                    <CardContent class="pt-6">
                        <SectionForm
                            :fields="fields"
                            v-model:content="form.content"
                            v-model:files="form.files"
                            :errors="form.errors as Record<string, string>"
                            :faq-pool="faqPool"
                            :pools="pools"
                            :hints="hints"
                        />
                    </CardContent>
                </Card>

                <div class="mt-4 flex items-center gap-3">
                    <Button type="submit" :disabled="form.processing">
                        <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                        Guardar cambios
                    </Button>
                    <Button as-child variant="ghost">
                        <Link :href="page.breadcrumbs[page.breadcrumbs.length - 1].href">Volver</Link>
                    </Button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
