<script setup lang="ts">
import BulletListSection from '@/components/public/sections/BulletListSection.vue';
import CardGridSection from '@/components/public/sections/CardGridSection.vue';
import ClassInfoSection from '@/components/public/sections/ClassInfoSection.vue';
import EventCalendarSection from '@/components/public/sections/EventCalendarSection.vue';
import EventListSection from '@/components/public/sections/EventListSection.vue';
import EventStripSection from '@/components/public/sections/EventStripSection.vue';
import FaqSection from '@/components/public/sections/FaqSection.vue';
import FigureSection from '@/components/public/sections/FigureSection.vue';
import GallerySection from '@/components/public/sections/GallerySection.vue';
import HeroSection from '@/components/public/sections/HeroSection.vue';
import MapSection from '@/components/public/sections/MapSection.vue';
import PageHeaderSection from '@/components/public/sections/PageHeaderSection.vue';
import PersonSection from '@/components/public/sections/PersonSection.vue';
import PricingSection from '@/components/public/sections/PricingSection.vue';
import QuoteSection from '@/components/public/sections/QuoteSection.vue';
import TextBlockSection from '@/components/public/sections/TextBlockSection.vue';
import TextImageSection from '@/components/public/sections/TextImageSection.vue';
import { type CalendarData } from '@/lib/calendar';
import { type EventData, type FaqItem, type SectionData } from '@/lib/site';
import { computed, type Component } from 'vue';

const props = defineProps<{
    section: SectionData;
    events?: EventData[];
    homeEvents?: EventData[];
    faqs?: Record<number, FaqItem>;
    calendar?: CalendarData;
}>();

const components: Record<string, Component> = {
    hero: HeroSection,
    page_header: PageHeaderSection,
    text_block: TextBlockSection,
    text_image: TextImageSection,
    quote: QuoteSection,
    card_grid: CardGridSection,
    bullet_list: BulletListSection,
    gallery: GallerySection,
    class_info: ClassInfoSection,
    pricing: PricingSection,
    figure: FigureSection,
    person: PersonSection,
    event_strip: EventStripSection,
    event_list: EventListSection,
    event_calendar: EventCalendarSection,
    map: MapSection,
    faq: FaqSection,
};

const component = computed(() => components[props.section.type]);

const extraProps = computed(() => {
    switch (props.section.type) {
        case 'event_strip':
            return { homeEvents: props.homeEvents };
        case 'event_list':
            return { events: props.events };
        case 'event_calendar':
            return { calendar: props.calendar };
        case 'faq':
            return { faqs: props.faqs };
        default:
            return {};
    }
});
</script>

<template>
    <component :is="component" v-if="component" :section="section" v-bind="extraProps" />
</template>
