<script setup lang="ts">
import { img, paragraphs, type SectionData } from '@/lib/site';

defineProps<{ section: SectionData }>();
</script>

<template>
    <section v-if="section.content.image || section.content.heading">
        <div class="mx-auto max-w-3xl px-4 text-center">
            <h2 v-if="section.content.heading" class="font-heading text-3xl font-light text-brand-sky md:text-[35px]">
                {{ section.content.heading }}
            </h2>

            <img
                v-if="section.content.image"
                :src="img(section.content.image)"
                :alt="section.content.heading ?? ''"
                class="mx-auto mt-8 w-full max-w-xl rounded-lg shadow-md ring-1 ring-brand-line/50"
            />

            <p v-for="(p, i) in paragraphs(section.content.caption)" :key="i" class="mt-4 text-sm text-brand-muted">
                {{ p }}
            </p>
        </div>
    </section>
</template>
