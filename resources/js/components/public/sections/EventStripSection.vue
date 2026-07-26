<script setup lang="ts">
import EventCard from '@/components/public/EventCard.vue';
import { type EventData, type SectionData } from '@/lib/site';
import { computed } from 'vue';

const props = defineProps<{ section: SectionData; homeEvents?: EventData[] }>();

const events = computed(() => props.homeEvents ?? []);
</script>

<template>
    <section class="bg-brand-light/40 py-12 md:py-16">
        <div class="mx-auto max-w-6xl px-4">
            <h2
                v-if="section.content.heading"
                class="text-center font-heading text-3xl font-light text-brand-sky md:text-[35px]"
            >
                {{ section.content.heading }}
            </h2>
            <p class="mt-1 text-center font-display text-xl uppercase tracking-wide text-brand-orange">próximamente</p>

            <div
                v-if="events.length"
                class="mx-auto mt-10 grid gap-8"
                :class="events.length === 1 ? 'max-w-2xl grid-cols-1' : 'max-w-4xl sm:grid-cols-2'"
            >
                <EventCard v-for="event in events" :key="event.id" :event="event" :featured="events.length === 1" />
            </div>
        </div>
    </section>
</template>
