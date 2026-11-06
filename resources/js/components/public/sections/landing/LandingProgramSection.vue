<script setup lang="ts">
import LandingHeading from '@/components/public/sections/landing/LandingHeading.vue';
import { type SectionData } from '@/lib/site';
import { computed } from 'vue';

type Day = {
    weekday: string | null;
    day: string | null;
    featured: boolean;
    items: { time: string | null; name: string | null; highlight: boolean }[];
};

const props = defineProps<{ section: SectionData }>();
const c = computed(() => props.section.content);
const days = computed<Day[]>(() => c.value.days ?? []);

/** Tres por fila como en el ejemplo; con 1, 2 o 4 días la grilla se acomoda. */
const gridClass = computed(() => ([1, 2, 4].includes(days.value.length) ? `lp-program-${days.value.length}` : ''));
</script>

<template>
    <section class="lp-section lp-program-section">
        <LandingHeading :kicker="c.kicker" :heading="c.heading" :description="c.intro" />
        <div v-if="days.length" class="lp-program" :class="gridClass">
            <div v-for="(day, i) in days" :key="i" class="lp-program-day" :class="{ 'lp-program-day-featured': day.featured }">
                <div class="lp-day-label">
                    <span>{{ day.weekday }}</span
                    ><strong>{{ day.day }}</strong>
                </div>
                <div v-for="(item, j) in day.items ?? []" :key="j" class="lp-schedule-item" :class="{ 'lp-schedule-highlight': item.highlight }">
                    <time v-if="item.time">{{ item.time }}</time>
                    <span v-if="item.name">{{ item.name }}</span>
                </div>
            </div>
        </div>
    </section>
</template>
