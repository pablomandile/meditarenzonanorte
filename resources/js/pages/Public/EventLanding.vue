<script setup lang="ts">
import '../../../css/landing.css';

import SectionRenderer from '@/components/public/SectionRenderer.vue';
import { useHashScroll } from '@/composables/useHashScroll';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { type SectionData } from '@/lib/site';
import { Head, Link } from '@inertiajs/vue3';
import { Eye } from 'lucide-vue-next';
import { provide } from 'vue';

// El botón del banner baja a #inscripcion.
useHashScroll();

const props = defineProps<{
    page: { slug: string; title: string; meta_description: string | null; kind: string; admin_url: string | null };
    sections: SectionData[];
    /** La plantilla, o una landing todavía sin publicar que mira alguien con sesión. */
    preview: boolean;
}>();

provide('landingPreview', props.preview);
</script>

<template>
    <PublicLayout>
        <Head :title="page.title">
            <meta v-if="page.meta_description" name="description" :content="page.meta_description" />
            <meta v-if="preview" name="robots" content="noindex" />
        </Head>

        <div
            v-if="preview"
            class="flex flex-wrap items-center justify-center gap-x-3 gap-y-1 bg-amber-100 px-4 py-2 text-center text-sm text-amber-900"
        >
            <Eye class="h-4 w-4 shrink-0" />
            <span v-if="page.kind === 'event_template'">Vista previa de la plantilla de landing. Sólo la ve quien tiene sesión.</span>
            <span v-else>Vista previa: esta landing no está publicada (el evento está oculto o tiene Link externo).</span>
            <Link v-if="page.admin_url" :href="page.admin_url" class="font-medium underline">Editar secciones</Link>
        </div>

        <div class="landing-page">
            <SectionRenderer v-for="section in sections" :key="section.id" :section="section" />
        </div>
    </PublicLayout>
</template>
