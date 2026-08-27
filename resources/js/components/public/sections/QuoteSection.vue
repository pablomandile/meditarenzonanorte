<script setup lang="ts">
import { type SectionData } from '@/lib/site';
import { Star } from 'lucide-vue-next';

defineProps<{ section: SectionData }>();
</script>

<template>
    <section class="section-band bg-brand-light/50 py-14">
        <div class="mx-auto max-w-3xl px-4 text-center">
            <div class="mb-4 flex justify-center gap-1 text-brand-orange">
                <Star v-for="i in 5" :key="i" class="h-5 w-5 fill-current" />
            </div>
            <blockquote class="text-xl font-light italic leading-relaxed text-brand-body md:text-2xl">
                “{{ section.content.quote }}”
            </blockquote>
            <p v-if="section.content.author" class="mt-5 font-medium text-brand-sky">— {{ section.content.author }}</p>
        </div>
    </section>
</template>
