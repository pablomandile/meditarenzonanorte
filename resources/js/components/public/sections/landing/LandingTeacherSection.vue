<script setup lang="ts">
import { img, paragraphs, type SectionData } from '@/lib/site';
import { computed } from 'vue';

const props = defineProps<{ section: SectionData }>();
const c = computed(() => props.section.content);
</script>

<template>
    <section class="lp-teacher">
        <div v-if="c.image" class="lp-teacher-photo">
            <img :src="img(c.image)" :alt="c.heading ?? ''" loading="lazy" />
        </div>
        <div class="lp-teacher-copy">
            <p v-if="c.kicker" class="lp-kicker">{{ c.kicker }}</p>
            <h2 v-if="c.heading">{{ c.heading }}</h2>
            <p v-for="(p, i) in paragraphs(c.body)" :key="i">{{ p }}</p>
        </div>
    </section>
</template>
