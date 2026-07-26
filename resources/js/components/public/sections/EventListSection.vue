<script setup lang="ts">
import { img, paragraphs, type EventData, type SectionData } from '@/lib/site';
import { Calendar, Clock, MapPin, Ticket } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{ section: SectionData; events?: EventData[] }>();

const list = computed(() => props.events ?? []);
</script>

<template>
    <section class="py-12 md:py-16">
        <div class="mx-auto max-w-6xl space-y-14 px-4">
            <h2
                v-if="section.content.heading"
                class="text-center font-heading text-3xl font-light text-brand-sky md:text-[35px]"
            >
                {{ section.content.heading }}
            </h2>

            <p v-if="!list.length" class="text-center font-display text-2xl uppercase tracking-wide text-brand-muted">
                {{ section.content.empty_text ?? 'próximamente' }}
            </p>

            <article
                v-for="(event, index) in list"
                :key="event.id"
                class="grid items-center gap-8 md:grid-cols-2"
            >
                <div v-if="event.image_path" :class="{ 'md:order-2': index % 2 === 1 }">
                    <img
                        :src="img(event.image_path)"
                        :alt="event.title"
                        class="mx-auto w-full max-w-lg rounded-lg object-cover shadow-sm"
                    />
                </div>

                <div :class="{ 'md:order-1': index % 2 === 1, 'md:col-span-2': !event.image_path }">
                    <h3 class="font-heading text-2xl font-normal leading-snug text-brand-sky md:text-[30px]">
                        {{ event.title }}
                    </h3>

                    <p v-for="(p, i) in paragraphs(event.description)" :key="i" class="mt-4 leading-relaxed text-brand-body">
                        {{ p }}
                    </p>

                    <ul class="mt-6 space-y-2.5 text-brand-body">
                        <li v-if="event.date_text" class="flex items-start gap-3">
                            <Calendar class="mt-0.5 h-5 w-5 shrink-0 text-brand-sky" />
                            <span class="font-medium">{{ event.date_text }}</span>
                        </li>
                        <li v-if="event.location" class="flex items-start gap-3">
                            <MapPin class="mt-0.5 h-5 w-5 shrink-0 text-brand-sky" />
                            <span>{{ event.location }}</span>
                        </li>
                        <li v-if="event.price" class="flex items-start gap-3">
                            <Ticket class="mt-0.5 h-5 w-5 shrink-0 text-brand-sky" />
                            <span class="font-semibold">{{ event.price }}</span>
                        </li>
                    </ul>

                    <div v-if="event.cta_label" class="mt-6">
                        <a
                            v-if="event.cta_url"
                            :href="event.cta_url"
                            target="_blank"
                            rel="noopener"
                            class="inline-block rounded-full bg-brand-sky px-6 py-2.5 text-sm font-medium uppercase tracking-wide text-white transition hover:bg-brand-sky-dark"
                        >
                            {{ event.cta_label }}
                        </a>
                        <span
                            v-else
                            class="inline-flex items-center gap-2 rounded-full bg-brand-line/50 px-6 py-2.5 text-sm font-medium uppercase tracking-wide text-brand-muted"
                        >
                            <Clock class="h-4 w-4" /> {{ event.cta_label }}
                        </span>
                    </div>
                </div>
            </article>
        </div>
    </section>
</template>
