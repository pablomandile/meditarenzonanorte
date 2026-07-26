<script setup lang="ts">
import CardsField from '@/components/admin/fields/CardsField.vue';
import ImageField from '@/components/admin/fields/ImageField.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { type CardItem } from '@/lib/site';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

const props = defineProps<{ settings: Record<string, any> }>();

const breadcrumbs = [{ title: 'Ajustes del sitio', href: '/admin/settings' }];

const form = useForm<Record<string, any>>({
    site_name: props.settings.site_name ?? '',
    phone_display: props.settings.phone_display ?? '',
    phone_link: props.settings.phone_link ?? '',
    whatsapp_url: props.settings.whatsapp_url ?? '',
    email: props.settings.email ?? '',
    instagram_url: props.settings.instagram_url ?? '',
    address: props.settings.address ?? '',
    footer_resources: JSON.parse(JSON.stringify(props.settings.footer_resources ?? [])) as CardItem[],
    logo_path: props.settings.logo_path ?? null,
    files: {} as Record<string, any>,
});

function submit() {
    form.transform((data) => {
        const files: Record<string, any> = { ...data.files };
        if (files.logo === null) delete files.logo;

        return { ...data, files };
    }).post(route('admin.settings.update'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => (form.files = {}),
    });
}

const textFields: { key: string; label: string; placeholder?: string }[] = [
    { key: 'site_name', label: 'Nombre del sitio' },
    { key: 'phone_display', label: 'Teléfono (como se muestra)', placeholder: '341 6 989430' },
    { key: 'phone_link', label: 'Enlace del teléfono', placeholder: 'tel:+543416989430' },
    { key: 'whatsapp_url', label: 'Enlace de WhatsApp', placeholder: 'https://wa.me/549341...' },
    { key: 'email', label: 'Email' },
    { key: 'instagram_url', label: 'Instagram (URL)' },
    { key: 'address', label: 'Dirección' },
];
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <Head title="Ajustes del sitio" />

        <div class="mx-auto flex w-full max-w-3xl flex-col gap-4 p-4">
            <div>
                <h1 class="text-xl font-semibold">Ajustes del sitio</h1>
                <p class="text-sm text-muted-foreground">Datos de contacto, logo y recursos del pie de página.</p>
            </div>

            <form @submit.prevent="submit">
                <Card>
                    <CardContent class="grid gap-5 pt-6">
                        <ImageField v-model="form.logo_path" v-model:file="form.files.logo" label="Logo" :error="form.errors['files.logo']" />

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div v-for="field in textFields" :key="field.key" class="grid gap-2">
                                <Label>{{ field.label }}</Label>
                                <Input v-model="form[field.key]" :placeholder="field.placeholder" />
                                <p v-if="form.errors[field.key]" class="text-sm text-red-600">{{ form.errors[field.key] }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="mt-4">
                    <CardContent class="pt-6">
                        <CardsField
                            v-model="form.footer_resources"
                            v-model:files="form.files.footer_resources"
                            label="Recursos del pie de página (libros / descargas)"
                            :error="form.errors.footer_resources"
                        />
                    </CardContent>
                </Card>

                <div class="mt-4">
                    <Button type="submit" :disabled="form.processing">
                        <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                        Guardar ajustes
                    </Button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
