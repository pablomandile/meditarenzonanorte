<script setup lang="ts">
import LandingHeading from '@/components/public/sections/landing/LandingHeading.vue';
import { type SectionData } from '@/lib/site';
import { computed } from 'vue';

const props = defineProps<{ section: SectionData }>();
const c = computed(() => props.section.content);
const items = computed<string[]>(() => (c.value.items ?? []).filter(Boolean));
</script>

<template>
    <section class="lp-section lp-benefits">
        <LandingHeading :kicker="c.kicker" :heading="c.heading" :description="c.intro" />
        <div v-if="items.length" class="lp-benefit-list">
            <div v-for="(item, i) in items" :key="i" class="lp-benefit">
                <span aria-hidden="true">✓</span>
                <p>{{ item }}</p>
            </div>
        </div>
        <p v-if="c.closing" class="lp-benefits-closing">{{ c.closing }}</p>
    </section>
</template>
