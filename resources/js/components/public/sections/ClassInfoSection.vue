<script setup lang="ts">
import { img, isInternal, paragraphs, type SectionData } from '@/lib/site';
import { Link } from '@inertiajs/vue3';
import { Clock, MapPin, Ticket } from 'lucide-vue-next';

defineProps<{ section: SectionData }>();
</script>

<template>
    <section class="py-8 md:py-10">
        <div class="mx-auto max-w-6xl px-4">
            <div class="overflow-hidden rounded-xl bg-brand-cream">
                <div class="grid items-center gap-0 md:grid-cols-2">
                    <!--
                        La imagen manda su propio alto (h-auto, sin object-cover): los
                        afiches suelen ser verticales 4:5 y estirarlos al alto de la
                        columna de texto los recortaba. Si el texto queda más alto que
                        el afiche, items-center lo centra sobre el fondo crema.
                    -->
                    <div v-if="section.content.image">
                        <img :src="img(section.content.image)" :alt="section.content.heading ?? ''" class="h-auto w-full" />
                    </div>

                    <div class="p-8 md:p-10" :class="{ 'md:col-span-2': !section.content.image }">
                        <h2 class="whitespace-pre-line font-heading text-3xl font-light leading-snug text-brand-sky md:text-[32px]">
                            {{ section.content.heading }}
                        </h2>

                        <p v-for="(p, i) in paragraphs(section.content.body)" :key="i" class="mt-4 leading-relaxed text-brand-body">
                            {{ p }}
                        </p>

                        <ul class="mt-6 space-y-3 text-brand-body">
                            <li v-if="section.content.schedule" class="flex items-start gap-3">
                                <Clock class="mt-0.5 h-5 w-5 shrink-0 text-brand-orange" />
                                <span class="whitespace-pre-line font-medium">{{ section.content.schedule }}</span>
                            </li>
                            <li v-if="section.content.location" class="flex items-start gap-3">
                                <MapPin class="mt-0.5 h-5 w-5 shrink-0 text-brand-orange" />
                                <span>{{ section.content.location }}</span>
                            </li>
                            <li v-if="section.content.price" class="flex items-start gap-3">
                                <Ticket class="mt-0.5 h-5 w-5 shrink-0 text-brand-orange" />
                                <span class="font-semibold">{{ section.content.price }}</span>
                            </li>
                        </ul>

                        <div v-if="section.content.cta_label && section.content.cta_url" class="mt-7">
                            <Link
                                v-if="isInternal(section.content.cta_url)"
                                :href="section.content.cta_url"
                                class="inline-block rounded-full bg-brand-orange px-6 py-2.5 text-sm font-medium uppercase tracking-wide text-white transition hover:bg-brand-orange-dark"
                            >
                                {{ section.content.cta_label }}
                            </Link>
                            <a
                                v-else
                                :href="section.content.cta_url"
                                target="_blank"
                                rel="noopener"
                                class="inline-block rounded-full bg-brand-orange px-6 py-2.5 text-sm font-medium uppercase tracking-wide text-white transition hover:bg-brand-orange-dark"
                            >
                                {{ section.content.cta_label }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
