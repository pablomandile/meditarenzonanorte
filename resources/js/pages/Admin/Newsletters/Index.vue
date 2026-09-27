<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Copy, Eye, Hand, Mail, Plus, ShieldCheck, TriangleAlert } from 'lucide-vue-next';

type Row = {
    id: number;
    type: 'weekly' | 'monthly';
    subject: string;
    status: 'draft' | 'scheduled' | 'sending' | 'sent' | 'failed';
    items_count: number;
    recipient_count: number | null;
    error: string | null;
    scheduled_label: string | null;
    sent_label: string | null;
    preview_url: string;
    waiting: boolean;
};

const props = defineProps<{
    newsletters: Row[];
    sent_this_month: number;
    missing: string[];
    blocked: string | null;
    /** 'auto': los programados salen solos. 'manual': no sale nada sin apretar. */
    mode: 'auto' | 'manual';
}>();

const breadcrumbs = [{ title: 'Newsletter', href: '/admin/newsletters' }];

/** El color dice el estado de un vistazo; el texto lo dice para quien no ve color. */
const estados: Record<Row['status'], { label: string; class: string }> = {
    draft: { label: 'Borrador', class: 'bg-muted text-muted-foreground' },
    scheduled: { label: 'Programado', class: 'bg-brand-sky/15 text-brand-sky-dark' },
    sending: { label: 'Enviando…', class: 'bg-amber-100 text-amber-800' },
    sent: { label: 'Enviado', class: 'bg-emerald-100 text-emerald-800' },
    failed: { label: 'Falló', class: 'bg-red-100 text-red-700' },
};

const createForm = useForm({ type: 'weekly' as 'weekly' | 'monthly', subject: '', preview_text: '', items: [] as unknown[] });

function store() {
    createForm.post(route('admin.newsletters.store'));
}

function duplicate(row: Row) {
    router.post(route('admin.newsletters.duplicate', row.id));
}
</script>

<template>
    <AdminLayout :breadcrumbs="breadcrumbs">
        <Head title="Newsletter" />

        <div class="flex flex-col gap-4 p-4">
            <div>
                <h1 class="text-xl font-semibold">Newsletter</h1>
                <p class="text-sm text-muted-foreground">
                    Armá el envío eligiendo clases y eventos que ya están cargados en el sitio, fijale día y hora, y sale solo.
                </p>
            </div>

            <!--
                En modo manual nada sale solo, y eso hay que verlo sin buscarlo: si no,
                se programa un envío para el lunes y el lunes no pasa nada.
            -->
            <div
                v-if="props.mode === 'manual'"
                class="flex flex-wrap items-center gap-2 rounded-lg border border-brand-sky/40 bg-brand-light px-4 py-3 text-sm text-brand-ink"
            >
                <Hand class="h-4 w-4 shrink-0" />
                <span><span class="font-medium">Modo manual:</span> ningún newsletter sale solo. Los mandás vos desde el botón.</span>
                <Link href="/admin/settings#newsletter" class="text-xs font-medium underline">Cambiar a automático</Link>
            </div>

            <!--
                No es producción: acá no se le manda a nadie. Se avisa arriba de todo
                para que quede claro por qué los botones de envío no responden.
            -->
            <div v-if="props.blocked" class="flex items-start gap-2 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <ShieldCheck class="mt-0.5 h-4 w-4 shrink-0" />
                <span>{{ props.blocked }}</span>
            </div>

            <!-- Lo que falta para poder enviar, dicho antes de que alguien lo intente. -->
            <div
                v-if="props.missing.length"
                class="grid gap-1 rounded-lg border border-brand-orange/40 bg-brand-cream px-4 py-3 text-sm text-brand-ink"
            >
                <p class="flex items-center gap-2 font-medium"><TriangleAlert class="h-4 w-4" /> Falta configurar el envío</p>
                <ul class="ml-6 list-disc text-xs">
                    <li v-for="(item, i) in props.missing" :key="i">{{ item }}</li>
                </ul>
                <Link href="/admin/settings" class="mt-1 w-fit text-xs font-medium underline">Ir a Ajustes del sitio</Link>
            </div>

            <p v-if="props.sent_this_month" class="text-xs text-muted-foreground">
                Este mes llevás <span class="font-medium text-brand-ink">{{ props.sent_this_month }}</span> correos enviados. Mailchimp cuenta lo
                mismo si mandás desde acá o desde su editor.
            </p>

            <Card>
                <CardContent class="p-0">
                    <p v-if="!props.newsletters.length" class="px-4 py-10 text-center text-sm text-muted-foreground">
                        Todavía no hay ningún newsletter. Creá el primero más abajo.
                    </p>

                    <div v-else class="divide-y">
                        <div v-for="row in props.newsletters" :key="row.id" class="flex flex-wrap items-center gap-3 px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="estados[row.status].class">
                                {{ estados[row.status].label }}
                            </span>

                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium">{{ row.subject }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ row.type === 'weekly' ? 'Semanal' : 'Mensual' }}
                                    · {{ row.items_count }} {{ row.items_count === 1 ? 'actividad' : 'actividades' }}
                                    <template v-if="row.status === 'sent'">
                                        · enviado el {{ row.sent_label }} a {{ row.recipient_count }} suscriptores
                                    </template>
                                    <template v-else-if="row.scheduled_label"> · programado para el {{ row.scheduled_label }}</template>
                                </p>
                                <!--
                                    Ya le pasó la hora y sigue ahí. No se perdió: espera
                                    a que lo manden. Se dice por qué, que es lo que uno
                                    se pregunta al verlo.
                                -->
                                <p v-if="row.waiting" class="mt-1 flex items-center gap-1 text-xs font-medium text-brand-sky-dark">
                                    <Hand class="h-3.5 w-3.5" />
                                    {{
                                        props.mode === 'manual'
                                            ? 'Le llegó la hora y está listo para que lo mandes.'
                                            : 'Le pasó la hora y todavía no salió: revisá que el cron esté corriendo.'
                                    }}
                                </p>
                                <p v-if="row.error" class="mt-1 text-xs text-red-600">{{ row.error }}</p>
                            </div>

                            <a :href="row.preview_url" target="_blank" rel="noopener">
                                <Button variant="ghost" size="sm" title="Ver el mail"><Eye class="h-4 w-4" /></Button>
                            </a>
                            <Button variant="ghost" size="sm" title="Duplicar" @click="duplicate(row)"><Copy class="h-4 w-4" /></Button>
                            <Link v-if="row.status !== 'sent'" :href="route('admin.newsletters.edit', row.id)">
                                <Button variant="outline" size="sm">Editar</Button>
                            </Link>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="grid gap-3 pt-6">
                    <h2 class="flex items-center gap-2 font-medium"><Mail class="h-4 w-4" /> Nuevo newsletter</h2>

                    <div class="grid gap-3 sm:grid-cols-[10rem_1fr]">
                        <div class="grid gap-1">
                            <Label class="text-xs">Tipo</Label>
                            <select
                                v-model="createForm.type"
                                class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                            >
                                <option value="weekly">Semanal</option>
                                <option value="monthly">Mensual</option>
                            </select>
                        </div>

                        <div class="grid gap-1">
                            <Label class="text-xs">Asunto</Label>
                            <Input v-model="createForm.subject" placeholder="Clases y actividades de esta semana" />
                            <p v-if="createForm.errors.subject" class="text-sm text-red-600">{{ createForm.errors.subject }}</p>
                        </div>
                    </div>

                    <Button class="w-fit" :disabled="createForm.processing" @click="store"> <Plus class="mr-1 h-4 w-4" /> Crear </Button>
                </CardContent>
            </Card>
        </div>
    </AdminLayout>
</template>
