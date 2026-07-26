<script setup lang="ts">
import { paragraphs, type SectionData } from '@/lib/site';
import { computed } from 'vue';

const props = defineProps<{ section: SectionData }>();

const items = computed(() => ((props.section.content.items ?? []) as string[]).filter(Boolean));
</script>

<template>
    <section class="py-12 md:py-14">
        <div class="mx-auto max-w-3xl px-4 text-center">
            <h2 v-if="section.content.heading" class="font-heading text-3xl font-light text-brand-sky md:text-[32px]">
                {{ section.content.heading }}
            </h2>

            <p v-for="(p, i) in paragraphs(section.content.intro)" :key="i" class="mt-4 text-brand-body">
                {{ p }}
            </p>

            <ul class="mx-auto mt-6 inline-block space-y-3 text-left">
                <li v-for="(item, i) in items" :key="i" class="flex items-start gap-3 text-brand-body">
                    <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full bg-brand-sky" aria-hidden="true"></span>
                    <span class="leading-relaxed">{{ item }}</span>
                </li>
            </ul>
        </div>
    </section>
</template>
