<script setup lang="ts">
import { type SectionData } from '@/lib/site';
import { computed } from 'vue';

const props = defineProps<{ section: SectionData }>();

const src = computed(() => {
    const query = props.section.content.query;
    if (!query) return undefined;

    return `https://maps.google.com/maps?q=${encodeURIComponent(query)}&t=m&z=15&output=embed&iwloc=near`;
});
</script>

<template>
    <section v-if="src" class="py-12 md:py-16">
        <div class="mx-auto max-w-6xl px-4">
            <h2
                v-if="section.content.heading"
                class="mb-8 text-center font-heading text-3xl font-light text-brand-sky md:text-[35px]"
            >
                {{ section.content.heading }}
            </h2>

            <iframe
                :src="src"
                class="h-[400px] w-full rounded-lg border-0"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                allowfullscreen
                title="Mapa de ubicación"
            ></iframe>
        </div>
    </section>
</template>
