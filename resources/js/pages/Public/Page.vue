<script setup lang="ts">
import SectionRenderer from '@/components/public/SectionRenderer.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { type EventData, type FaqItem, type SectionData } from '@/lib/site';
import { Head } from '@inertiajs/vue3';

defineProps<{
    page: { slug: string; title: string; meta_description: string | null };
    sections: SectionData[];
    events?: EventData[];
    homeEvents?: EventData[];
    faqs?: Record<number, FaqItem>;
}>();
</script>

<template>
    <PublicLayout>
        <Head :title="page.title">
            <meta v-if="page.meta_description" name="description" :content="page.meta_description" />
        </Head>

        <SectionRenderer
            v-for="section in sections"
            :key="section.id"
            :section="section"
            :events="events"
            :home-events="homeEvents"
            :faqs="faqs"
        />
    </PublicLayout>
</template>
