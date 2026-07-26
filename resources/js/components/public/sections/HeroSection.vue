<script setup lang="ts">
import { img, type SectionData } from '@/lib/site';

defineProps<{ section: SectionData }>();
</script>

<template>
    <section class="relative">
        <img
            v-if="section.content.image"
            :src="img(section.content.image)"
            :alt="section.content.heading ?? ''"
            class="h-auto w-full object-cover"
        />
        <div
            v-if="section.content.heading || section.content.subheading"
            class="absolute inset-0 flex flex-col items-center justify-center bg-black/25 px-4 text-center"
        >
            <h1 v-if="section.content.heading" class="font-display text-4xl tracking-wide text-white drop-shadow md:text-6xl">
                {{ section.content.heading }}
            </h1>
            <p v-if="section.content.subheading" class="mt-4 max-w-2xl text-lg text-white drop-shadow md:text-xl">
                {{ section.content.subheading }}
            </p>
        </div>
    </section>
</template>
