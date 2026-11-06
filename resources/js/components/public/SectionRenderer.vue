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
import ReviewsSection from '@/components/public/sections/ReviewsSection.vue';
import TextBlockSection from '@/components/public/sections/TextBlockSection.vue';
import TextImageSection from '@/components/public/sections/TextImageSection.vue';
import LandingAboutSection from '@/components/public/sections/landing/LandingAboutSection.vue';
import LandingBenefitsSection from '@/components/public/sections/landing/LandingBenefitsSection.vue';
import LandingBookingSection from '@/components/public/sections/landing/LandingBookingSection.vue';
import LandingHeroSection from '@/components/public/sections/landing/LandingHeroSection.vue';
import LandingIntroSection from '@/components/public/sections/landing/LandingIntroSection.vue';
import LandingProgramSection from '@/components/public/sections/landing/LandingProgramSection.vue';
import LandingQuoteSection from '@/components/public/sections/landing/LandingQuoteSection.vue';
import LandingTeacherSection from '@/components/public/sections/landing/LandingTeacherSection.vue';
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
    reviews: ReviewsSection,
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
    // Landing de evento (Link interno); los estilos están en resources/css/landing.css.
    landing_hero: LandingHeroSection,
    landing_intro: LandingIntroSection,
    landing_about: LandingAboutSection,
    landing_quote: LandingQuoteSection,
    landing_benefits: LandingBenefitsSection,
    landing_teacher: LandingTeacherSection,
    landing_program: LandingProgramSection,
    landing_booking: LandingBookingSection,
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
