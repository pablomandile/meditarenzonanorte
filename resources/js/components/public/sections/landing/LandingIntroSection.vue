<script setup lang="ts">
import LandingHeading from '@/components/public/sections/landing/LandingHeading.vue';
import { paragraphs, type CardItem, type SectionData } from '@/lib/site';
import { computed } from 'vue';

const props = defineProps<{ section: SectionData }>();
const c = computed(() => props.section.content);
const cards = computed<CardItem[]>(() => (c.value.cards ?? []).filter((card: CardItem) => card.title || card.text));

/** 01, 02, 03… */
const number = (i: number) => String(i + 1).padStart(2, '0');
</script>

<template>
    <section class="lp-section lp-intro">
        <LandingHeading :kicker="c.kicker" :heading="c.heading" />
        <div v-if="paragraphs(c.body).length" class="lp-intro-copy">
            <p v-for="(p, i) in paragraphs(c.body)" :key="i">{{ p }}</p>
        </div>
        <div v-if="cards.length" class="lp-points" :class="cards.length < 3 ? `lp-points-${cards.length}` : ''">
            <div v-for="(card, i) in cards" :key="i" class="lp-point">
                <span class="lp-point-number">{{ number(i) }}</span>
                <h3 v-if="card.title">{{ card.title }}</h3>
                <p v-if="card.text">{{ card.text }}</p>
            </div>
        </div>
    </section>
</template>
