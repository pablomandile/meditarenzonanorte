<script setup lang="ts">
import { paragraphs, type SectionData } from '@/lib/site';
import { computed } from 'vue';

const props = defineProps<{ section: SectionData }>();

const bgClass = computed(() => (props.section.content.style === 'sky' ? 'bg-brand-sky' : 'bg-brand-orange'));
</script>

<template>
    <section :class="bgClass" class="section-band py-8 md:py-10">
        <div class="mx-auto max-w-6xl px-4 text-center">
            <h1 class="font-display text-3xl font-normal uppercase tracking-[0.02em] text-white md:text-[45px] md:leading-tight">
                {{ section.content.heading }}
            </h1>
            <p v-for="(p, i) in paragraphs(section.content.intro)" :key="i" class="mx-auto mt-3 max-w-3xl text-white/90">
                {{ p }}
            </p>
        </div>
    </section>
</template>
