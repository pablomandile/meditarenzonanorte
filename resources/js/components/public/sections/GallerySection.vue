<script setup lang="ts">
import { img, type SectionData } from '@/lib/site';
import { computed } from 'vue';

const props = defineProps<{ section: SectionData }>();

const images = computed(() => ((props.section.content.images ?? []) as string[]).filter(Boolean));
</script>

<template>
    <section>
        <div class="mx-auto max-w-6xl px-4">
            <h2
                v-if="section.content.heading"
                class="mb-8 text-center font-heading text-3xl font-light text-brand-sky md:text-[32px]"
            >
                {{ section.content.heading }}
            </h2>

            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                <img
                    v-for="(image, i) in images"
                    :key="i"
                    :src="img(image)"
                    alt=""
                    class="aspect-square w-full rounded-lg object-cover"
                />
            </div>
        </div>
    </section>
</template>
