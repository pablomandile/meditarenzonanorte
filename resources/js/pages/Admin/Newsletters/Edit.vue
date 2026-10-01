<script setup lang="ts">
import NewsletterItemsField, { type NewsletterItem } from '@/components/admin/NewsletterItemsField.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useConfirm } from '@/composables/useConfirm';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { CalendarClock, Eye, LoaderCircle, Send, Trash2, TriangleAlert } from 'lucide-vue-next';
import { ref } from 'vue';

type Newsletter = {
    id: number;
    type: 'weekly' | 'monthly';
    subject: string;
    preview_text: string | null;
    status: 'draft' | 'scheduled' | 'sending' | 'sent' | 'failed';
    items: NewsletterItem[];
    error: string | null;
    scheduled_label: string | null;
    scheduled_input: string | null;
    preview_url: string;
};

const props = defineProps<{
    newsletter: Newsletter;
    pool: { classes: any[]; events: any[] };
    missing: string[];
    /** Si no es producción, el motivo por el que los envíos reales están bloqueados. */
    blocked: string | null;
    test_email: string | null;
}>();

const breadcrumbs = [
    { title: 'Newsletter', href: '/admin/newsletters' },
    { title: props.newsletter.subject, href: route('admin.newsletters.edit', props.newsletter.id) },
];

const { confirm } = useConfirm();

const form = useForm({
    type: props.newsletter.type,
    subject: props.newsletter.subject,
    preview_text: props.newsletter.preview_text ?? '',
    items: props.newsletter.items as NewsletterItem[],
});

function save() {
    form.put(route('admin.newsletters.update', props.newsletter.id), { preserveScroll: true });
}

const scheduledAt = ref(props.newsletter.scheduled_input ?? '');
const busy = ref(false);

function schedule() {
    router.patch(
        route('admin.newsletters.schedule', props.newsletter.id),
        { scheduled_at: scheduledAt.value },
        { preserveScroll: true, onStart: () => (busy.value = true), onFinish: () => (busy.value = false) },
    );
}

function unschedule() {
    router.patch(route('admin.newsletters.unschedule', props.newsletter.id), {}, { preserveScroll: true });
}

const testEmail = ref(props.test_email ?? '');

function sendTest() {
    router.post(
        route('admin.newsletters.test', props.newsletter.id),
        { email: testEmail.value },
        { preserveScroll: true, onStart: () => (busy.value = true), onFinish: () => (busy.value = false) },
    );
}

/**
 * El único botón que no se puede deshacer. La confirmación dice el número de
 * destinatarios y no un "¿estás seguro?": lo que hay que pensar antes de apretar es
 * a cuánta gente le llega.
 */
async function sendNow() {
    const accepted = await confirm({
        title: 'Enviar ahora',
        description: `El correo sale en este momento a toda la audiencia y no se puede cancelar. Antes de seguir, conviene haberse mandado una prueba.`,
        confirmLabel: 'Enviar ahora',
        destructive: true,
    });

    if (!accepted) return;

    router.post(
        route('admin.newsletters.send', props.newsletter.id),
        {},
        { preserveScroll: true, onStart: () => (busy.value = true), onFinish: () => (busy.value = false) },
    );
}

async function destroy() {
    const accepted = await confirm({
        title: 'Eliminar newsletter',
        description: `“${props.newsletter.subject}” se borra para siempre. No se puede deshacer.`,
        confirmLabel: 'Eliminar',
        destructive: true,
    });

    if (accepted) router.delete(route('admin.newsletters.destroy', props.newsletter.id));
}
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <Head :title="props.newsletter.subject" />

        <div class="flex flex-col gap-4 p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-semibold">{{ props.newsletter.subject }}</h1>
                    <p v-if="props.newsletter.status === 'scheduled'" class="text-sm text-brand-sky-dark">
                        Programado para el {{ props.newsletter.scheduled_label }}. Lo que se manda ya quedó fijado.
                    </p>
                    <p v-else class="text-sm text-muted-foreground">Borrador. Todavía no salió ni está programado.</p>
                </div>

                <div class="flex items-center gap-2">
                    <a :href="props.newsletter.preview_url" target="_blank" rel="noopener">
                        <Button variant="outline" size="sm"><Eye class="mr-1 h-4 w-4" /> Vista previa</Button>
                    </a>
                    <Button variant="ghost" size="sm" class="text-red-600" @click="destroy"><Trash2 class="h-4 w-4" /></Button>
                </div>
            </div>

            <div v-if="props.newsletter.error" class="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700">
                <p class="font-medium">El último intento de envío falló</p>
                <p class="mt-1 text-xs">{{ props.newsletter.error }}</p>
            </div>

            <div
                v-if="props.missing.length"
                class="grid gap-1 rounded-lg border border-brand-orange/40 bg-brand-cream px-4 py-3 text-sm text-brand-ink"
            >
                <p class="flex items-center gap-2 font-medium"><TriangleAlert class="h-4 w-4" /> Falta configurar el envío</p>
                <ul class="ml-6 list-disc text-xs">
                    <li v-for="(item, i) in props.missing" :key="i">{{ item }}</li>
                </ul>
            </div>

            <Card>
                <CardContent class="grid gap-4 pt-6">
                    <div class="grid gap-3 sm:grid-cols-[10rem_1fr]">
                        <div class="grid gap-1">
                            <Label class="text-xs">Tipo</Label>
                            <select
                                v-model="form.type"
                                class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                            >
                                <option value="weekly">Semanal</option>
                                <option value="monthly">Mensual</option>
                            </select>
                        </div>

                        <div class="grid gap-1">
                            <Label class="text-xs">Asunto</Label>
                            <Input v-model="form.subject" />
                            <p v-if="form.errors.subject" class="text-sm text-red-600">{{ form.errors.subject }}</p>
                        </div>
                    </div>

                    <div class="grid gap-1">
                        <Label class="text-xs">Renglón de vista previa (opcional)</Label>
                        <Input v-model="form.preview_text" placeholder="Lo que se lee debajo del asunto en la bandeja de entrada" />
                        <p class="text-xs text-muted-foreground">Se ve en la lista de correos, junto al asunto, y no aparece dentro del mail.</p>
                    </div>

                    <div class="grid gap-2 border-t pt-4">
                        <Label class="text-xs">Qué va en este envío</Label>
                        <NewsletterItemsField v-model="form.items" :pool="props.pool" />
                        <p v-if="form.errors.items" class="text-sm text-red-600">{{ form.errors.items }}</p>
                    </div>

                    <div class="flex items-center gap-2 border-t pt-4">
                        <Button :disabled="form.processing" @click="save">
                            <LoaderCircle v-if="form.processing" class="mr-1 h-4 w-4 animate-spin" />
                            Guardar
                        </Button>
                        <span v-if="form.isDirty" class="text-xs text-muted-foreground">Hay cambios sin guardar.</span>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="grid gap-4 pt-6">
                    <h2 class="font-medium">Probar y enviar</h2>

                    <!-- La prueba va primero a propósito: es el paso que conviene no saltearse. -->
                    <div class="grid gap-1">
                        <Label class="text-xs">Mandarme una prueba</Label>
                        <div class="flex flex-wrap gap-2">
                            <Input v-model="testEmail" type="email" placeholder="tu@correo.com" class="max-w-xs" />
                            <Button variant="outline" :disabled="busy || !testEmail" @click="sendTest">Enviar prueba</Button>
                        </div>
                        <p class="text-xs text-muted-foreground">
                            Llega sólo a esa casilla. Conviene mirarla en el celular, que es donde se lee el correo.
                        </p>
                    </div>

                    <div class="grid gap-1 border-t pt-4">
                        <Label class="text-xs">Programar el envío</Label>
                        <div class="flex flex-wrap gap-2">
                            <Input v-model="scheduledAt" type="datetime-local" class="max-w-xs" />
                            <Button variant="outline" :disabled="busy || !!props.blocked" @click="schedule">
                                <CalendarClock class="mr-1 h-4 w-4" />
                                {{ props.newsletter.status === 'scheduled' ? 'Cambiar la fecha' : 'Programar' }}
                            </Button>
                            <Button v-if="props.newsletter.status === 'scheduled'" variant="ghost" @click="unschedule">Cancelar</Button>
                        </div>
                        <p class="text-xs text-muted-foreground">
                            Hora de Argentina. Al programarlo, el mail queda fijado tal como está ahora: si después cambiás una clase en el sitio,
                            sale igual lo que se ve en la vista previa de hoy.
                        </p>
                        <p v-if="form.errors.scheduled_at" class="text-sm text-red-600">{{ form.errors.scheduled_at }}</p>
                    </div>

                    <div class="border-t pt-4">
                        <Button variant="destructive" :disabled="busy || !!props.blocked" @click="sendNow">
                            <Send class="mr-1 h-4 w-4" /> Enviar ahora
                        </Button>
                        <p class="mt-1 text-xs text-muted-foreground">Sale en este momento a toda la audiencia. No se puede deshacer.</p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
