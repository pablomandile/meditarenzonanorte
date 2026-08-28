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
import { Check, LoaderCircle } from 'lucide-vue-next';
import { computed } from 'vue';

/** Una fuente del catálogo del servidor. Ver App\Support\Typography. */
type FontOption = { key: string; name: string; family: string; stack: string; pageTitleStack: string; url: string };

const props = defineProps<{ settings: Record<string, any>; fonts: FontOption[] }>();

const breadcrumbs = [{ title: 'Ajustes del sitio', href: '/admin/settings' }];

const form = useForm<Record<string, any>>({
    site_name: props.settings.site_name ?? '',
    phone_display: props.settings.phone_display ?? '',
    phone_link: props.settings.phone_link ?? '',
    whatsapp_url: props.settings.whatsapp_url ?? '',
    email: props.settings.email ?? '',
    instagram_url: props.settings.instagram_url ?? '',
    address: props.settings.address ?? '',
    heading_font: (props.settings.heading_font ?? null) as string | null,
    footer_resources: JSON.parse(JSON.stringify(props.settings.footer_resources ?? [])) as CardItem[],
    logo_path: props.settings.logo_path ?? null,
    footer_logo_path: props.settings.footer_logo_path ?? null,
    files: {} as Record<string, any>,
});

function submit() {
    form.transform((data) => {
        const files: Record<string, any> = { ...data.files };
        if (files.logo === null) delete files.logo;
        if (files.footer_logo === null) delete files.footer_logo;

        return { ...data, files };
    }).post(route('admin.settings.update'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            // Re-sync with the values the server just persisted so the logo and
            // footer images refresh in the panel without needing a page reload.
            form.files = {};
            form.logo_path = props.settings.logo_path ?? null;
            form.footer_logo_path = props.settings.footer_logo_path ?? null;
            form.footer_resources = JSON.parse(JSON.stringify(props.settings.footer_resources ?? []));
            form.defaults();
        },
    });
}

/**
 * Las tarjetas del selector: primero la salida para volver al aspecto de siempre
 * —mientras esté elegida, el sitio no descarga ninguna fuente— y después las del
 * catálogo. El ejemplo se dibuja en font-light porque es el peso que usan los
 * títulos de sección, que es donde más se nota el cambio.
 */
const fontCards = computed(() => [
    { key: null, name: 'Como está ahora', family: 'Helvetica, Arial, sans-serif', note: 'Del sistema, sin descargar nada' },
    ...props.fonts.map((font) => ({ key: font.key, name: font.name, family: font.stack, note: 'Google Fonts' })),
]);

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
        <!-- Las fuentes se cargan sólo acá, para la vista previa: el resto del panel no las descarga. -->
        <Head title="Ajustes del sitio">
            <link v-for="font in fonts" :key="font.key" rel="stylesheet" :href="font.url" />
        </Head>

        <div class="mx-auto flex w-full max-w-3xl flex-col gap-4 p-4">
            <div>
                <h1 class="text-xl font-semibold">Ajustes del sitio</h1>
                <p class="text-sm text-muted-foreground">Datos de contacto, logo y recursos del pie de página.</p>
            </div>

            <form @submit.prevent="submit">
                <Card>
                    <CardContent class="grid gap-5 pt-6">
                        <ImageField
                            v-model="form.logo_path"
                            v-model:file="form.files.logo"
                            label="Logo del menú"
                            :error="form.errors['files.logo']"
                        />

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
                    <CardContent class="grid gap-4 pt-6">
                        <div class="grid gap-1">
                            <Label>Fuente de los títulos</Label>
                            <p class="text-xs text-muted-foreground">
                                Cambia los títulos de sección del sitio público y el de la banda que encabeza cada
                                página. El texto de los párrafos no se toca.
                            </p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <button
                                v-for="card in fontCards"
                                :key="card.key ?? 'actual'"
                                type="button"
                                class="grid gap-2 rounded-lg border p-4 text-left transition hover:bg-accent"
                                :class="form.heading_font === card.key ? 'border-primary ring-1 ring-primary' : 'border-input'"
                                @click="form.heading_font = card.key"
                            >
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-sm font-medium">{{ card.name }}</span>
                                    <Check v-if="form.heading_font === card.key" class="h-4 w-4 shrink-0 text-primary" />
                                </div>
                                <span class="text-2xl font-light leading-tight" :style="{ fontFamily: card.family }">
                                    Clases semanales
                                </span>
                                <span class="text-xs text-muted-foreground">{{ card.note }}</span>
                            </button>
                        </div>

                        <p v-if="form.errors.heading_font" class="text-sm text-red-600">{{ form.errors.heading_font }}</p>
                    </CardContent>
                </Card>

                <Card class="mt-4">
                    <CardContent class="grid gap-6 pt-6">
                        <div class="grid gap-1">
                            <ImageField
                                v-model="form.footer_logo_path"
                                v-model:file="form.files.footer_logo"
                                label="Logo del pie de página"
                                :error="form.errors['files.footer_logo']"
                            />
                            <p class="text-xs text-muted-foreground">
                                Si lo dejás vacío, el pie usa el logo del menú. Con "Quitar" volvés a ese comportamiento.
                            </p>
                        </div>

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
