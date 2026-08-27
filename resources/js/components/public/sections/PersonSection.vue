<script setup lang="ts">
import { img, paragraphs, type SectionData } from '@/lib/site';
import { computed } from 'vue';

const props = defineProps<{ section: SectionData }>();

const imageRight = computed(() => props.section.content.image_side !== 'left');
</script>

<template>
    <section>
        <div
            class="mx-auto flex max-w-5xl flex-col gap-8 px-4 md:flex-row md:items-center md:gap-12"
            :class="{ 'md:flex-row-reverse': imageRight }"
        >
            <!-- Foto: siempre el mismo tamaño (crece con flex-1), solo cambia el lado -->
            <div class="md:flex-1">
                <img
                    v-if="section.content.image"
                    :src="img(section.content.image)"
                    :alt="section.content.name ?? ''"
                    class="aspect-square w-full rounded-2xl object-cover shadow-md ring-1 ring-brand-line/50"
                />
            </div>

            <!-- Texto: ancho fijo, para que la foto quede consistente en ambos lados -->
            <div class="md:w-[340px] md:shrink-0">
                <p v-if="section.content.role" class="font-heading text-xs font-semibold uppercase tracking-[0.18em] text-brand-orange">
                    {{ section.content.role }}
                </p>
                <h3 v-if="section.content.name" class="mt-1 font-heading text-2xl font-normal leading-tight text-brand-ink md:text-[28px]">
                    {{ section.content.name }}
                </h3>
                <p v-if="section.content.subtitle" class="mt-1 font-medium text-brand-sky">
                    {{ section.content.subtitle }}
                </p>

                <p v-for="(p, i) in paragraphs(section.content.body)" :key="i" class="mt-4 leading-relaxed text-brand-body">
                    {{ p }}
                </p>
            </div>
        </div>
    </section>
</template>
